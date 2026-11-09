<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\RentBill;
use App\Models\Room;
use App\Models\StockPrediction;
use App\Models\TenantProfile;
use App\Support\SqlDate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan Executive Dashboard untuk Pengelola Kos Tiana Putra 1 (FR-11).
     */
    public function index()
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // 1. Metrik Okupansi & Penghuni: satu query dikelompokkan per status (bukan 4 query count terpisah)
        $roomsByStatus = Room::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $roomsCount = (int) $roomsByStatus->sum();
        $occupiedRoomsCount = (int) ($roomsByStatus['terisi'] ?? 0);
        $availableRoomsCount = (int) ($roomsByStatus['tersedia'] ?? 0);
        $repairRoomsCount = (int) ($roomsByStatus['perbaikan'] ?? 0);
        $tenantsCount = TenantProfile::where('is_active', true)->count();

        // 2. Pendapatan 6 bulan terakhir: satu query sewa + satu query penjualan, dikelompokkan per bulan
        $months = collect(range(5, 0))->map(fn ($i) => $now->copy()->startOfMonth()->subMonths($i));
        $windowStart = $months->first();

        $rentByMonth = RentBill::selectRaw('year, month, SUM(amount) as total')
            ->where('status', 'lunas')
            ->where('year', '>=', $windowStart->year)
            ->groupBy('year', 'month')
            ->get()
            ->mapWithKeys(fn ($r) => [sprintf('%04d-%02d', $r->year, $r->month) => (int) $r->total]);

        $salesExpr = SqlDate::yearMonth('created_at');
        $salesByMonth = Order::selectRaw("{$salesExpr} as ym, SUM(total_amount) as total")
            ->where('status', 'selesai')
            ->where('created_at', '>=', $windowStart)
            ->groupBy('ym')
            ->pluck('total', 'ym');

        $chartLabels = [];
        $rentSeries = [];
        $salesSeries = [];
        foreach ($months as $monthDate) {
            $key = $monthDate->format('Y-m');
            $chartLabels[] = $monthDate->translatedFormat('M Y');
            $rentSeries[] = (int) ($rentByMonth[$key] ?? 0);
            $salesSeries[] = (int) ($salesByMonth[$key] ?? 0);
        }

        $thisMonth = $now->format('Y-m');
        $rentIncomeThisMonth = $rentByMonth[$thisMonth] ?? 0;
        $serviceSalesThisMonth = (int) ($salesByMonth[$thisMonth] ?? 0);
        $totalIncomeThisMonth = $rentIncomeThisMonth + $serviceSalesThisMonth;

        $pendingRentThisMonth = (int) RentBill::where('year', $currentYear)
            ->where('month', $currentMonth)
            ->whereIn('status', ['menunggu', 'menunggak'])
            ->sum('amount');

        // 3. Metrik Operasional Harian
        $activeOrdersCount = Order::whereIn('status', ['menunggu', 'diproses', 'diantar'])->count();
        $stockAlerts = CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->get();
        $overdueBills = RentBill::with(['tenantProfile.user', 'room'])
            ->whereIn('status', ['menunggu', 'menunggak'])
            ->where('due_date', '<', $now->toDateString())
            ->get();

        // 5. Pesanan Layanan Terbaru
        $recentOrders = Order::with(['items', 'user'])
            ->latest('created_at')
            ->take(6)
            ->get();

        // 6. Prediksi Kebutuhan Stok AI Terkini
        $predictions = StockPrediction::with('catalogItem')
            ->orderByRaw("CASE WHEN status = 'kritis' THEN 1 WHEN status = 'waspada' THEN 2 ELSE 3 END")
            ->take(4)
            ->get();

        return view('pengelola.dashboard', compact(
            'roomsCount',
            'occupiedRoomsCount',
            'availableRoomsCount',
            'repairRoomsCount',
            'tenantsCount',
            'rentIncomeThisMonth',
            'pendingRentThisMonth',
            'serviceSalesThisMonth',
            'totalIncomeThisMonth',
            'activeOrdersCount',
            'stockAlerts',
            'overdueBills',
            'chartLabels',
            'rentSeries',
            'salesSeries',
            'recentOrders',
            'predictions'
        ));
    }
}
