<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderHistoryController extends Controller
{
    /**
     * Tampilkan daftar pesanan aktif dan riwayat pesanan penghuni kos.
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $tab = $request->query('tab', 'aktif');

        $activeOrders = Order::with('items')
            ->where('user_id', $userId)
            ->whereIn('status', ['menunggu', 'diproses', 'diantar'])
            ->latest()
            ->get();

        $completedOrders = Order::with('items')
            ->where('user_id', $userId)
            ->whereIn('status', ['selesai', 'batal'])
            ->latest()
            ->paginate(15);

        return view('penghuni.orders', compact('activeOrders', 'completedOrders', 'tab'));
    }

    /**
     * Batalkan pesanan jika statusnya masih 'menunggu' (FR-08).
     * Menerapkan NFR-05 Integritas Data dengan DB::transaction, pengembalian stok & stock_logs.
     */
    public function cancel(Request $request, $id)
    {
        $userId = Auth::id();
        $order = Order::with('items')->where('user_id', $userId)->where('id', $id)->firstOrFail();

        if (!$order->isCancellable()) {
            return redirect()->back()->with('error', 'Pesanan tidak dapat dibatalkan karena sedang disiapkan atau sedang dalam perjalanan diantar oleh petugas.');
        }

        DB::transaction(function () use ($order, $userId) {
            $order->update([
                'status' => 'batal',
                'notes'  => trim(($order->notes ? $order->notes . ' • ' : '') . 'Dibatalkan oleh penghuni ' . now()->format('d/m/Y H:i')),
            ]);

            // Kembalikan stok item yang dipesan ke catalog_items & catat di stock_logs
            foreach ($order->items as $item) {
                $catalogItem = CatalogItem::where('id', $item->catalog_item_id)->lockForUpdate()->first();
                if ($catalogItem) {
                    $beforeStock = $catalogItem->stock;
                    $afterStock = $beforeStock + $item->quantity;

                    $catalogItem->increment('stock', $item->quantity);

                    StockLog::create([
                        'catalog_item_id' => $catalogItem->id,
                        'user_id'         => $userId,
                        'type'            => 'in',
                        'quantity'        => $item->quantity,
                        'before_stock'    => $beforeStock,
                        'after_stock'     => $afterStock,
                        'reference_type'  => 'order_cancellation',
                        'reference_id'    => $order->id,
                        'notes'           => "Pengembalian stok pembatalan pesanan #{$order->order_code}",
                    ]);
                }
            }

            // Notifikasi untuk Penghuni
            AppNotification::create([
                'user_id'    => $userId,
                'title'      => 'Pesanan Dibatalkan',
                'message'    => "Pesanan #{$order->order_code} telah berhasil Anda batalkan.",
                'type'       => 'order',
                'action_url' => route('penghuni.pesanan'),
                'is_read'    => false,
            ]);

            // Notifikasi untuk Petugas Layanan
            $petugasList = User::where('role', 'petugas')->get();
            foreach ($petugasList as $petugas) {
                AppNotification::create([
                    'user_id'    => $petugas->id,
                    'title'      => "Pesanan #{$order->order_code} Dibatalkan",
                    'message'    => "Penghuni Kamar {$order->room_number} telah membatalkan pesanan ini.",
                    'type'       => 'order',
                    'action_url' => route('petugas.antrean'),
                    'is_read'    => false,
                ]);
            }
        });

        return redirect()->route('penghuni.pesanan')
            ->with('success', "Pesanan #{$order->order_code} berhasil dibatalkan dan stok telah dikembalikan.");
    }
}
