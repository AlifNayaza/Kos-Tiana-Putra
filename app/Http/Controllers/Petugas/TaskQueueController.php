<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskQueueController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

    /**
     * Tampilkan antrean tugas operasional harian petugas (FR-08).
     */
    public function index()
    {
        $today = Carbon::today();

        // Metrik Ringkasan Beban Kerja Hari Ini
        $active = Order::selectRaw('status, COUNT(*) as total')
            ->whereIn('status', ['menunggu', 'diproses', 'diantar'])
            ->groupBy('status')
            ->pluck('total', 'status');

        $metrics = [
            'menunggu'   => (int) ($active['menunggu'] ?? 0),
            'diproses'   => (int) ($active['diproses'] ?? 0),
            'diantar'    => (int) ($active['diantar'] ?? 0),
            'selesai'    => Order::where('status', 'selesai')->where('completed_at', '>=', $today)->where('completed_at', '<', $today->copy()->addDay())->count(),
        ];

        Carbon::setLocale('id');

        // Daftar Pesanan Aktif: Urut Menunggu -> Diproses -> Diantar; di tiap status yang terlama paling atas
        $orders = Order::with(['items.catalogItem', 'user'])
            ->whereIn('status', ['menunggu', 'diproses', 'diantar'])
            ->orderByRaw("CASE 
                WHEN status = 'menunggu' THEN 1 
                WHEN status = 'diproses' THEN 2 
                WHEN status = 'diantar' THEN 3 
                ELSE 4 END")
            ->orderBy('created_at', 'asc')
            ->get();

        // Tambahkan metadata apakah pesanan mencakup galon
        $orders->transform(function ($order) {
            $hasGalon = $order->order_type === 'quick_galon' || $order->items->contains(function ($item) {
                return ($item->catalogItem && $item->catalogItem->category === 'galon') 
                    || str_contains(strtolower($item->item_name), 'galon');
            });
            $order->has_galon = $hasGalon;
            return $order;
        });

        $signature = Order::activitySignature();

        return view('petugas.dashboard', compact('orders', 'metrics', 'signature'));
    }

    /**
     * Tanda perubahan antrean (JSON super kecil) untuk polling hemat: lihat Order::activitySignature().
     */
    public function signal()
    {
        return response()->json(['sig' => Order::activitySignature()])->header('Cache-Control', 'no-store');
    }

    /**
     * Perbarui status pesanan dengan tombol One-Tap Contextual Action (FR-08 & FR-12).
     * Hanya transisi berurutan yang diizinkan; lihat OrderService::NEXT_STATUS.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['nullable', 'in:diproses,diantar,selesai'],
        ]);

        try {
            $updated = $this->orders->advance($order->id, Auth::user(), $request->input('status'));
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $statusLabels = [
            'diproses' => 'Diproses (Sedang Disiapkan)',
            'diantar'  => 'Sedang Diantar ke Kamar',
            'selesai'  => 'Selesai & Lunas',
        ];

        return redirect()->back()->with('success', "Status pesanan #{$updated->order_code} ({$updated->room_number}) berhasil diperbarui menjadi {$statusLabels[$updated->status]}.");
    }

    /**
     * Tampilkan riwayat pesanan yang telah diselesaikan oleh petugas.
     */
    public function history(Request $request)
    {
        Carbon::setLocale('id');
        // Tanggal hanya diterima bila valid (Y-m-d); nilai rusak diabaikan agar halaman tidak error 500.
        $dateFilter = $request->query('date');
        $day = rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $dateFilter), null, false);
        if (!$day) {
            $dateFilter = null;
        }
        $search = $request->query('q');

        $query = Order::with(['items', 'user', 'processor'])
            ->whereIn('status', ['selesai', 'batal']);

        if ($dateFilter) {
            // Rentang waktu (bukan DATE(kolom)) agar indeks completed_at dapat dipakai.
            $query->where('completed_at', '>=', $day)->where('completed_at', '<', $day->copy()->addDay());
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('completed_at')->latest('updated_at')->paginate(15)->withQueryString();

        // Rekapitulasi pesanan selesai hari ini berdasarkan metode pembayaran (Tunai vs Non-Tunai/QRIS)
        $todayAggregates = Order::where('status', 'selesai')
            ->where('completed_at', '>=', Carbon::today())
            ->where('completed_at', '<', Carbon::tomorrow())
            ->select('payment_method', DB::raw('SUM(total_amount) as total_sum'), DB::raw('COUNT(*) as total_count'))
            ->groupBy('payment_method')
            ->get();

        $todayCash = 0;
        $todayNonCash = 0;
        $todayCompletedCount = 0;

        foreach ($todayAggregates as $agg) {
            $todayCompletedCount += (int) $agg->total_count;
            if (Order::isCashMethod($agg->payment_method)) {
                $todayCash += (int) $agg->total_sum;
            } else {
                $todayNonCash += (int) $agg->total_sum;
            }
        }

        $totalCompletedAll = Order::where('status', 'selesai')->count();

        return view('petugas.history', compact(
            'orders',
            'dateFilter',
            'search',
            'todayCash',
            'todayNonCash',
            'todayCompletedCount',
            'totalCompletedAll'
        ));
    }
}
