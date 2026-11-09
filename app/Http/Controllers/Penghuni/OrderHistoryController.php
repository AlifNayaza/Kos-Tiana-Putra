<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderHistoryController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

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
            ->paginate(15)
            ->withQueryString();

        $signature = Order::activitySignature($userId);

        return view('penghuni.orders', compact('activeOrders', 'completedOrders', 'tab', 'signature'));
    }

    /**
     * Tanda perubahan pesanan aktif milik penghuni (JSON super kecil) untuk polling hemat.
     */
    public function signal()
    {
        return response()->json(['sig' => Order::activitySignature(Auth::id())])->header('Cache-Control', 'no-store');
    }

    /**
     * Batalkan pesanan jika statusnya masih 'menunggu' (FR-08).
     * Status dicek ulang di dalam transaksi setelah baris pesanan dikunci,
     * sehingga tidak bisa bertabrakan dengan petugas yang sedang memproses.
     */
    public function cancel(Request $request, $id)
    {
        try {
            $order = $this->orders->cancel((int) $id, Auth::user());
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('penghuni.pesanan')
            ->with('success', "Pesanan #{$order->order_code} berhasil dibatalkan dan stok telah dikembalikan.");
    }
}
