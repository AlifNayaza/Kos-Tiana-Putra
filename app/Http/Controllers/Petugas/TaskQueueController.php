<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskQueueController extends Controller
{
    /**
     * Tampilkan antrean tugas operasional harian petugas (FR-08).
     */
    public function index()
    {
        $today = Carbon::today();

        // Metrik Ringkasan Beban Kerja Hari Ini
        $metrics = [
            'menunggu'   => Order::where('status', 'menunggu')->count(),
            'diproses'   => Order::where('status', 'diproses')->count(),
            'diantar'    => Order::where('status', 'diantar')->count(),
            'selesai'    => Order::where('status', 'selesai')->whereDate('completed_at', $today)->count(),
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

        return view('petugas.dashboard', compact('orders', 'metrics'));
    }

    /**
     * Perbarui status pesanan dengan tombol One-Tap Contextual Action (FR-08 & FR-12).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $currentStatus = $order->status;
        $petugas = Auth::user();

        // Tentukan transisi status berikutnya
        $targetStatus = $request->input('status');
        if (!$targetStatus) {
            $targetStatus = match ($currentStatus) {
                'menunggu' => 'diproses',
                'diproses' => 'diantar',
                'diantar'  => 'selesai',
                default    => null,
            };
        }

        if (!$targetStatus || !in_array($targetStatus, ['diproses', 'diantar', 'selesai'])) {
            return redirect()->back()->with('error', 'Transisi status tidak valid untuk pesanan ini.');
        }

        // Simpan pembaruan status
        $updateData = [
            'status'       => $targetStatus,
            'processed_by' => $petugas->id,
        ];

        if ($targetStatus === 'selesai') {
            $updateData['completed_at'] = now();
            $updateData['payment_status'] = 'lunas';
        }

        $order->update($updateData);

        // Otomatis buat notifikasi ke penghuni pemilik pesanan (FR-12)
        $notifMap = [
            'diproses' => [
                'title'   => 'Pesanan Sedang Diproses',
                'message' => "Pesanan #{$order->order_code} sedang disiapkan oleh petugas ({$petugas->name}).",
            ],
            'diantar'  => [
                'title'   => 'Pesanan Sedang Diantar!',
                'message' => "Pesanan #{$order->order_code} sedang diantar menuju ke {$order->room_number}. Mohon bersiap menerima.",
            ],
            'selesai'  => [
                'title'   => 'Pesanan Telah Selesai',
                'message' => "Pesanan #{$order->order_code} telah selesai diantar ke {$order->room_number}. Terima kasih!",
            ],
        ];

        if (isset($notifMap[$targetStatus])) {
            AppNotification::create([
                'user_id'    => $order->user_id,
                'title'      => $notifMap[$targetStatus]['title'],
                'message'    => $notifMap[$targetStatus]['message'],
                'type'       => 'order',
                'action_url' => route('penghuni.pesanan'),
                'is_read'    => false,
            ]);
        }

        $statusLabels = [
            'diproses' => 'Diproses (Sedang Disiapkan)',
            'diantar'  => 'Sedang Diantar ke Kamar',
            'selesai'  => 'Selesai & Lunas',
        ];

        return redirect()->back()->with('success', "Status pesanan #{$order->order_code} ({$order->room_number}) berhasil diperbarui menjadi {$statusLabels[$targetStatus]}.");
    }

    /**
     * Tampilkan riwayat pesanan yang telah diselesaikan oleh petugas.
     */
    public function history(Request $request)
    {
        Carbon::setLocale('id');
        $dateFilter = $request->query('date');
        $search = $request->query('q');

        $query = Order::with(['items', 'user', 'processor'])
            ->whereIn('status', ['selesai', 'batal']);

        if ($dateFilter) {
            $query->whereDate('completed_at', $dateFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhere('room_number', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest('completed_at')->latest('updated_at')->paginate(15);

        // Rekapitulasi pesanan selesai hari ini berdasarkan metode pembayaran (Tunai vs Non-Tunai/QRIS)
        $todayAggregates = Order::where('status', 'selesai')
            ->whereDate('completed_at', Carbon::today())
            ->select('payment_method', DB::raw('SUM(total_amount) as total_sum'), DB::raw('COUNT(*) as total_count'))
            ->groupBy('payment_method')
            ->get();

        $todayCash = 0;
        $todayNonCash = 0;
        $todayCompletedCount = 0;

        foreach ($todayAggregates as $agg) {
            $todayCompletedCount += (int) $agg->total_count;
            $method = strtolower($agg->payment_method ?? '');
            if (str_contains($method, 'tunai') || str_contains($method, 'cash') || empty($agg->payment_method)) {
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
