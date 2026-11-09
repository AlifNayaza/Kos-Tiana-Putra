<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\RentBill;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman utama (Beranda) penghuni kos.
     */
    public function index()
    {
        $user = Auth::user();
        $tenantProfile = $user->tenantProfile()->with('room')->first();
        $room = $tenantProfile?->room;

        // Ambil tagihan aktif bulan berjalan atau tagihan terakhir yang belum lunas
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $activeBill = null;
        if ($tenantProfile) {
            $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
                ->where('year', $currentYear)
                ->where('month', $currentMonth)
                ->first();

            // Jika tidak ada tagihan bulan ini, ambil tagihan yang belum lunas tertua/terbaru
            if (!$activeBill) {
                $activeBill = RentBill::where('tenant_profile_id', $tenantProfile->id)
                    ->whereIn('status', ['menunggu', 'menunggak'])
                    ->latest('due_date')
                    ->first();
            }
        }

        // Pesanan aktif saat ini (Menunggu, Diproses, Diantar)
        $activeOrders = Order::with('items')
            ->where('user_id', $user->id)
            ->whereIn('status', ['menunggu', 'diproses', 'diantar'])
            ->latest()
            ->get();

        // Daftar produk kategori 'galon' untuk widget Quick Order 1-Click (FR-09)
        $galonItems = CatalogItem::where('category', 'galon')
            ->where('is_available', true)
            ->orderBy('price', 'asc')
            ->get();

        // Cari pesanan galon terakhir penghuni untuk fitur "Pesan ulang galon terakhir"
        $lastGalonItem = null;
        $lastGalonQty = 1;
        $lastGalonOrder = Order::with('items.catalogItem')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'batal')
            ->whereHas('items.catalogItem', function ($q) {
                $q->where('category', 'galon');
            })
            ->latest()
            ->first();

        if ($lastGalonOrder) {
            $galonOrderItem = $lastGalonOrder->items->first(function ($item) {
                return $item->catalogItem && $item->catalogItem->category === 'galon';
            });
            if ($galonOrderItem && $galonOrderItem->catalogItem) {
                $lastGalonItem = $galonOrderItem->catalogItem;
                $lastGalonQty = max(1, (int) $galonOrderItem->quantity);
            }
        }

        if (!$lastGalonItem) {
            $lastGalonItem = $galonItems->first();
            $lastGalonQty = 1;
        }

        return view('penghuni.dashboard', compact(
            'user',
            'tenantProfile',
            'room',
            'activeBill',
            'activeOrders',
            'galonItems',
            'lastGalonItem',
            'lastGalonQty'
        ));
    }
}
