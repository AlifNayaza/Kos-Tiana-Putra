<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentBill;
use App\Models\StockPrediction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\RunStockPrediction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\StockLog;
use Illuminate\Support\Facades\Auth;

class ReportPredictionController extends Controller
{
    /**
     * Tampilkan halaman rekapitulasi laporan keuangan & grafik Chart.js (FR-11).
     */
    public function reportsIndex(Request $request)
    {
        $now = Carbon::now();

        // Periode tidak valid (mis. month=13) kembali ke bulan berjalan, bukan error 500.
        $validated = validator($request->query(), [
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year'  => ['nullable', 'integer', 'between:2020,2100'],
        ])->valid();

        $selectedMonth = (int) ($validated['month'] ?? $now->month);
        $selectedYear = (int) ($validated['year'] ?? $now->year);

        $periodStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // 1. Pemasukan Sewa Kamar pada Periode Terpilih
        $totalRentPaid = RentBill::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->where('status', 'lunas')
            ->sum('amount');

        $totalRentPending = RentBill::where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->whereIn('status', ['menunggu', 'menunggak'])
            ->sum('amount');

        // 2. Penjualan Layanan Warkop & Galon pada Periode Terpilih (rentang tanggal agar memakai indeks)
        $completedOrders = fn () => Order::where('status', 'selesai')
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        $totalServiceSales = $completedOrders()->sum('total_amount');
        $totalOrdersCount = $completedOrders()->count();

        // 3. Rincian Penjualan per Kategori (Galon, Makanan, Minuman)
        $salesByCategory = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('catalog_items', 'catalog_items.id', '=', 'order_items.catalog_item_id')
            ->where('orders.status', 'selesai')
            ->whereBetween('orders.created_at', [$periodStart, $periodEnd])
            ->select('catalog_items.category', DB::raw('SUM(order_items.subtotal) as total_nominal'), DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('catalog_items.category')
            ->get()
            ->keyBy('category');

        $galonSales = $salesByCategory['galon']->total_nominal ?? 0;
        $foodSales = $salesByCategory['makanan']->total_nominal ?? 0;
        $drinkSales = $salesByCategory['minuman']->total_nominal ?? 0;

        // 4. Data Grafik Tren Harian: satu query dikelompokkan per hari (bukan satu query per tanggal)
        $salesPerDay = $completedOrders()
            ->selectRaw('DATE(created_at) as sale_date, SUM(total_amount) as total')
            ->groupBy('sale_date')
            ->pluck('total', 'sale_date');

        $dailyLabels = [];
        $dailyServiceSales = [];

        for ($d = 1; $d <= $periodStart->daysInMonth; $d++) {
            $dailyLabels[] = (string) $d;
            $dailyServiceSales[] = (int) ($salesPerDay[$periodStart->copy()->day($d)->toDateString()] ?? 0);
        }

        // 5. Item Terlaris (Top Selling Items)
        $topItems = OrderItem::join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'selesai')
            ->whereBetween('orders.created_at', [$periodStart, $periodEnd])
            ->select('order_items.item_name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.subtotal) as total_omset'))
            ->groupBy('order_items.item_name')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        $grandTotalRevenue = $totalRentPaid + $totalServiceSales;

        return view('pengelola.laporan.index', compact(
            'selectedMonth',
            'selectedYear',
            'totalRentPaid',
            'totalRentPending',
            'totalServiceSales',
            'totalOrdersCount',
            'galonSales',
            'foodSales',
            'drinkSales',
            'dailyLabels',
            'dailyServiceSales',
            'topItems',
            'grandTotalRevenue'
        ));
    }

    /**
     * Tampilkan halaman rekomendasi Prediksi Kebutuhan Stok Machine Learning (FR-13).
     */
    public function predictionsIndex()
    {
        $predictions = StockPrediction::with('catalogItem')
            ->whereIn('catalog_item_id', CatalogItem::select('id')) // item yang sudah dihapus tidak ikut
            ->orderByRaw("CASE WHEN status = 'kritis' THEN 1 WHEN status = 'waspada' THEN 2 ELSE 3 END")
            ->orderByDesc('recommended_restock_qty')
            ->get();

        $lastCalculatedAt = $predictions->max('calculated_at');
        $isCalculating = (bool) Cache::get(RunStockPrediction::RUNNING_CACHE_KEY);
        $lastError = Cache::get(RunStockPrediction::LAST_ERROR_CACHE_KEY);

        // Hitung estimasi modal belanja total (Saran Restock * Harga Satuan Katalog)
        $totalEstimatedBudget = $predictions->sum(fn($p) => $p->estimated_cost);

        // Kebutuhan Galon Khusus (Aqua, Le Minerale, Isi Ulang RO)
        $galonItems = $predictions->filter(fn($p) => $p->catalogItem?->category === 'galon');
        $galonProjectedDemand = $galonItems->sum('predicted_demand_qty');
        $galonCurrentStock = $galonItems->sum('current_stock');
        $galonNeededRestock = $galonItems->sum('recommended_restock_qty');

        // Smart Shopping Checklist (Hanya item yang perlu belanja: Saran Restock > 0)
        $shoppingChecklist = $predictions->filter(fn($p) => $p->recommended_restock_qty > 0)
            ->sortBy(fn($p) => $p->status === 'kritis' ? 0 : 1);

        // Format teks WhatsApp siap salin ke agen galon / staf warkop
        $waLines = [];
        $waLines[] = "*DAFTAR BELANJA KOS TIANA PUTRA 1*";
        $waLines[] = "Tanggal: " . Carbon::now()->translatedFormat('d F Y');
        $waLines[] = "Perkiraan Total Biaya: Rp " . number_format($totalEstimatedBudget, 0, ',', '.');
        $waLines[] = "";

        $kritisList = $shoppingChecklist->filter(fn($p) => $p->status === 'kritis');
        if ($kritisList->isNotEmpty()) {
            $waLines[] = "*🔴 PERLU SEGERA DIBELI (Stok Hampir Habis):*";
            foreach ($kritisList as $item) {
                $waLines[] = "• {$item->catalogItem?->name}: pesan +{$item->recommended_restock_qty} {$item->catalogItem?->unit} (sisa di kos: {$item->current_stock})";
            }
            $waLines[] = "";
        }

        $waspadaList = $shoppingChecklist->filter(fn($p) => $p->status === 'waspada');
        if ($waspadaList->isNotEmpty()) {
            $waLines[] = "*🟡 PERLU DITAMBAH (Kebutuhan 7 Hari ke Depan):*";
            foreach ($waspadaList as $item) {
                $waLines[] = "• {$item->catalogItem?->name}: pesan +{$item->recommended_restock_qty} {$item->catalogItem?->unit} (sisa di kos: {$item->current_stock})";
            }
            $waLines[] = "";
        }

        $waLines[] = "_Dihitung otomatis berdasarkan rata-rata pesanan penghuni Kos Tiana Putra 1_";
        $waShoppingText = implode("\n", $waLines);

        // Data Grafik Horizontal Bar Chart (Chart.js)
        $chartPayload = [
            'labels'         => $predictions->map(fn($p) => $p->catalogItem?->name ?? 'Item')->values(),
            'currentStock'   => $predictions->map(fn($p) => $p->current_stock)->values(),
            'targetStock'    => $predictions->map(fn($p) => $p->target_stock)->values(),
            'predictedDemand'=> $predictions->map(fn($p) => $p->predicted_demand_qty)->values(),
            'restockQty'     => $predictions->map(fn($p) => $p->recommended_restock_qty)->values(),
            'daysOfSupply'   => $predictions->map(fn($p) => $p->days_of_supply)->values(),
            'units'          => $predictions->map(fn($p) => $p->catalogItem?->unit ?? '')->values(),
            'statuses'       => $predictions->map(fn($p) => $p->status)->values(),
        ];

        $stats = [
            'total'            => $predictions->count(),
            'kritis'           => $predictions->where('status', 'kritis')->count(),
            'waspada'          => $predictions->where('status', 'waspada')->count(),
            'aman'             => $predictions->where('status', 'aman')->count(),
            'need_restock'     => $shoppingChecklist->count(),
            'estimated_budget' => $totalEstimatedBudget,
            'galon_demand'     => $galonProjectedDemand,
            'galon_stock'      => $galonCurrentStock,
            'galon_restock'    => $galonNeededRestock,
        ];

        return view('pengelola.prediksi.index', compact(
            'predictions',
            'lastCalculatedAt',
            'isCalculating',
            'lastError',
            'stats',
            'shoppingChecklist',
            'waShoppingText',
            'chartPayload'
        ));
    }

    /**
     * Tombol Aksi "Hitung Ulang Prediksi": model Python dijalankan setelah respons dikirim
     * (RunStockPrediction), sehingga halaman tidak menggantung menunggu proses selesai.
     */
    public function calculatePredictions()
    {
        if (Cache::get(RunStockPrediction::RUNNING_CACHE_KEY)) {
            return redirect()->route('pengelola.prediksi.index')
                ->with('info', 'Perhitungan prediksi sedang berjalan. Muat ulang halaman beberapa saat lagi.');
        }

        Cache::put(RunStockPrediction::RUNNING_CACHE_KEY, true, now()->addMinutes(5));
        RunStockPrediction::dispatchAfterResponse();

        return redirect()->route('pengelola.prediksi.index')
            ->with('success', 'Prediksi stok sedang dihitung di latar belakang. Muat ulang halaman dalam beberapa detik untuk melihat hasilnya.');
    }

    /**
     * Terapkan rekomendasi restock langsung (1-klik) dari halaman prediksi ke gudang fisik.
     * Menerapkan NFR-05 Integritas Data via DB::transaction & Audit Log StockLog.
     */
    public function applyRestock(Request $request, StockPrediction $prediction)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes'    => ['nullable', 'string', 'max:255'],
        ]);

        $qty = (int) $request->input('quantity', $prediction->recommended_restock_qty);
        $userNotes = $request->input('notes') ? ' - ' . trim($request->input('notes')) : '';
        $note = "Restock sesuai rekomendasi AI Prediksi (+{$qty} {$prediction->catalogItem?->unit}){$userNotes}";

        DB::transaction(function () use ($prediction, $qty, $note) {
            $catalogItem = CatalogItem::where('id', $prediction->catalog_item_id)
                ->lockForUpdate()
                ->firstOrFail();

            $beforeStock = $catalogItem->stock;
            $afterStock = $beforeStock + $qty;
            $catalogItem->increment('stock', $qty);

            StockLog::create([
                'catalog_item_id' => $catalogItem->id,
                'user_id'         => Auth::id(),
                'type'            => 'in',
                'quantity'        => $qty,
                'before_stock'    => $beforeStock,
                'after_stock'     => $afterStock,
                'reference_type'  => 'restock_ai',
                'notes'           => $note,
            ]);

            // Perbarui data prediksi di tabel stock_predictions
            $target = $prediction->target_stock > 0 ? $prediction->target_stock : ($prediction->predicted_demand_qty + $prediction->safety_stock);
            $newRestock = max(0, $target - $afterStock);
            $newStatus = $afterStock >= $target ? 'aman' : ($afterStock <= $prediction->safety_stock ? 'kritis' : 'waspada');

            $prediction->update([
                'current_stock'           => $afterStock,
                'recommended_restock_qty' => $newRestock,
                'status'                  => $newStatus,
                'recommendation_note'     => "Stok telah ditambah {$qty} unit oleh pengelola. Persediaan terkini: {$afterStock} {$catalogItem->unit} (Target aman 7H: {$target} unit).",
            ]);
        });

        return redirect()->route('pengelola.prediksi.index')
            ->with('success', "Stok {$prediction->catalogItem?->name} berhasil ditambah sebanyak {$qty} {$prediction->catalogItem?->unit}! Sisa kebutuhan restock telah diperbarui otomatis.");
    }
}
