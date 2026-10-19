<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\CatalogItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\StockLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    /**
     * Tampilkan katalog menu warkop & galon untuk penghuni kos.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $room = $user->tenantProfile?->room;
        $roomNumber = $room ? $room->room_number : 'Kamar Penghuni';

        $category = $request->query('category', 'semua');
        $search = $request->query('q');

        $query = CatalogItem::query()->where('is_available', true);

        if ($category && $category !== 'semua') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('category')->orderBy('name')->get();

        // Hitung total item per kategori untuk badge tab
        $counts = [
            'semua'   => CatalogItem::where('is_available', true)->count(),
            'makanan' => CatalogItem::where('is_available', true)->where('category', 'makanan')->count(),
            'minuman' => CatalogItem::where('is_available', true)->where('category', 'minuman')->count(),
            'galon'   => CatalogItem::where('is_available', true)->where('category', 'galon')->count(),
        ];

        return view('penghuni.catalog', compact('items', 'category', 'search', 'counts', 'roomNumber'));
    }

    /**
     * Simpan pesanan makanan/minuman/galon dari keranjang belanja Alpine.js (FR-07).
     * Menerapkan NFR-05 Integritas Data dengan DB::transaction & lockForUpdate.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $room = $user->tenantProfile?->room;
        $roomNumber = $room ? $room->room_number : 'Kamar Penghuni';

        // Validasi input
        $request->validate([
            'items_json'      => ['required', 'string'],
            'payment_method'  => ['nullable', 'string'],
            'order_notes'     => ['nullable', 'string', 'max:500'],
        ]);

        $rawItems = json_decode($request->input('items_json'), true);

        if (!is_array($rawItems) || empty($rawItems)) {
            throw ValidationException::withMessages([
                'items_json' => 'Keranjang pesanan masih kosong.',
            ]);
        }

        // Eksekusi transaksi database dengan integritas stok
        $order = DB::transaction(function () use ($user, $roomNumber, $rawItems, $request) {
            $itemIds = array_column($rawItems, 'id');

            // Kunci baris produk dengan lockForUpdate untuk mencegah race-condition
            $catalogItems = CatalogItem::whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');

            $orderTotal = 0;
            $orderItemsToInsert = [];
            $stockLogsToInsert = [];
            $hasNonGalon = false;

            foreach ($rawItems as $cartItem) {
                $itemId = $cartItem['id'] ?? null;
                $qty = (int) ($cartItem['quantity'] ?? 0);
                $note = trim($cartItem['notes'] ?? '');

                if ($qty <= 0) {
                    continue;
                }

                $catalogItem = $catalogItems->get($itemId);

                if (!$catalogItem) {
                    throw ValidationException::withMessages([
                        'items_json' => 'Salah satu item yang Anda pilih sudah tidak tersedia.',
                    ]);
                }

                if (!$catalogItem->is_available) {
                    throw ValidationException::withMessages([
                        'items_json' => "Menu {$catalogItem->name} saat ini sedang dinonaktifkan.",
                    ]);
                }

                // Cek ketersediaan stok aktual
                if ($catalogItem->stock < $qty) {
                    throw ValidationException::withMessages([
                        'items_json' => "Maaf, stok untuk \"{$catalogItem->name}\" tersisa {$catalogItem->stock} {$catalogItem->unit}. Tidak mencukupi jumlah pesanan ({$qty}).",
                    ]);
                }

                if ($catalogItem->category !== 'galon') {
                    $hasNonGalon = true;
                }

                $subtotal = $qty * $catalogItem->price;
                $orderTotal += $subtotal;

                $beforeStock = $catalogItem->stock;
                $afterStock = $beforeStock - $qty;

                // Kurangi stok aktual
                $catalogItem->decrement('stock', $qty);

                $orderItemsToInsert[] = [
                    'catalog_item_id' => $catalogItem->id,
                    'item_name'       => $catalogItem->name,
                    'quantity'        => $qty,
                    'unit_price'      => $catalogItem->price,
                    'subtotal'        => $subtotal,
                    'notes'           => $note ?: null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];

                $stockLogsToInsert[] = [
                    'catalog_item_id' => $catalogItem->id,
                    'user_id'         => $user->id,
                    'type'            => 'out',
                    'quantity'        => $qty,
                    'before_stock'    => $beforeStock,
                    'after_stock'     => $afterStock,
                    'reference_type'  => 'order',
                    'notes'           => 'Pesanan ' . $roomNumber,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }

            if (empty($orderItemsToInsert)) {
                throw ValidationException::withMessages([
                    'items_json' => 'Tidak ada item valid dalam keranjang.',
                ]);
            }

            // Buat kode pesanan unik
            $orderCode = sprintf('ORD-%s-%04d', Carbon::now()->format('Ymd'), rand(1000, 9999));
            $orderType = (!$hasNonGalon && count($orderItemsToInsert) === 1) ? 'quick_galon' : 'regular';
            $paymentMethod = $request->input('payment_method') ?: 'Tunai / Bayar di Kamar';
            $orderNotes = $request->input('order_notes');

            $order = Order::create([
                'order_code'     => $orderCode,
                'user_id'        => $user->id,
                'room_number'    => $roomNumber,
                'order_type'     => $orderType,
                'total_amount'   => $orderTotal,
                'status'         => 'menunggu',
                'payment_status' => 'belum_bayar',
                'payment_method' => $paymentMethod,
                'notes'          => $orderNotes,
            ]);

            // Simpan detail item dan log mutasi stok
            foreach ($orderItemsToInsert as &$item) {
                $item['order_id'] = $order->id;
            }
            OrderItem::insert($orderItemsToInsert);

            foreach ($stockLogsToInsert as &$log) {
                $log['reference_id'] = $order->id;
                $log['notes'] = "Pesanan #{$order->order_code} oleh Kamar {$roomNumber}";
            }
            StockLog::insert($stockLogsToInsert);

            // Buat notifikasi untuk Penghuni
            AppNotification::create([
                'user_id'    => $user->id,
                'title'      => 'Pesanan Berhasil Masuk Antrean!',
                'message'    => "Pesanan #{$order->order_code} sebesar Rp " . number_format($order->total_amount, 0, ',', '.') . " telah diterima. Petugas akan segera memproses.",
                'type'       => 'order',
                'action_url' => route('penghuni.pesanan'),
                'is_read'    => false,
            ]);

            // Buat notifikasi untuk semua Petugas Layanan
            $petugasList = User::where('role', 'petugas')->get();
            foreach ($petugasList as $petugas) {
                AppNotification::create([
                    'user_id'    => $petugas->id,
                    'title'      => "Pesanan Baru dari {$roomNumber}!",
                    'message'    => "Pesanan #{$order->order_code} ({$order->order_type}) menunggu untuk diproses.",
                    'type'       => 'order',
                    'action_url' => route('petugas.antrean'),
                    'is_read'    => false,
                ]);
            }

            return $order;
        });

        return redirect()->route('penghuni.pesanan')
            ->with('success', "Pesanan #{$order->order_code} berhasil dibuat! Petugas akan mengantarkannya ke {$roomNumber}.");
    }

    /**
     * Fitur Pesan Galon Cepat 1-Click Order (FR-09).
     */
    public function quickGalon(Request $request)
    {
        $user = Auth::user();
        $room = $user->tenantProfile?->room;
        $roomNumber = $room ? $room->room_number : 'Kamar Penghuni';

        $request->validate([
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'quantity'        => ['nullable', 'integer', 'min:1', 'max:5'],
            'quick_note'      => ['nullable', 'string', 'max:255'],
        ]);

        $itemId = (int) $request->input('catalog_item_id');
        $qty = (int) ($request->input('quantity', 1) ?: 1);
        $note = $request->input('quick_note', 'Tolong pasang di dispenser kamar.');

        $order = DB::transaction(function () use ($user, $roomNumber, $itemId, $qty, $note) {
            $galonItem = CatalogItem::where('id', $itemId)->lockForUpdate()->first();

            if (!$galonItem || $galonItem->category !== 'galon' || !$galonItem->is_available) {
                throw ValidationException::withMessages([
                    'catalog_item_id' => 'Pilihan galon tidak valid atau sedang tidak tersedia.',
                ]);
            }

            if ($galonItem->stock < $qty) {
                throw ValidationException::withMessages([
                    'catalog_item_id' => "Stok {$galonItem->name} habis/tidak cukup (tersisa {$galonItem->stock} galon).",
                ]);
            }

            $beforeStock = $galonItem->stock;
            $afterStock = $beforeStock - $qty;
            $totalAmount = $galonItem->price * $qty;

            $galonItem->decrement('stock', $qty);

            $orderCode = sprintf('ORD-%s-%04d', Carbon::now()->format('Ymd'), rand(1000, 9999));

            $order = Order::create([
                'order_code'     => $orderCode,
                'user_id'        => $user->id,
                'room_number'    => $roomNumber,
                'order_type'     => 'quick_galon',
                'total_amount'   => $totalAmount,
                'status'         => 'menunggu',
                'payment_status' => 'belum_bayar',
                'payment_method' => 'Tunai / Bayar di Kamar',
                'notes'          => $note,
            ]);

            OrderItem::create([
                'order_id'        => $order->id,
                'catalog_item_id' => $galonItem->id,
                'item_name'       => $galonItem->name,
                'quantity'        => $qty,
                'unit_price'      => $galonItem->price,
                'subtotal'        => $totalAmount,
                'notes'           => $note,
            ]);

            StockLog::create([
                'catalog_item_id' => $galonItem->id,
                'user_id'         => $user->id,
                'type'            => 'out',
                'quantity'        => $qty,
                'before_stock'    => $beforeStock,
                'after_stock'     => $afterStock,
                'reference_type'  => 'order',
                'reference_id'    => $order->id,
                'notes'           => "Quick Order Galon #{$order->order_code} oleh Kamar {$roomNumber}",
            ]);

            // Notifikasi Penghuni
            AppNotification::create([
                'user_id'    => $user->id,
                'title'      => 'Quick Order Galon Diterima',
                'message'    => "Pesanan #{$order->order_code} ({$galonItem->name}) telah dikirim ke petugas layanan.",
                'type'       => 'order',
                'action_url' => route('penghuni.pesanan'),
                'is_read'    => false,
            ]);

            // Notifikasi Petugas
            $petugasList = User::where('role', 'petugas')->get();
            foreach ($petugasList as $petugas) {
                AppNotification::create([
                    'user_id'    => $petugas->id,
                    'title'      => "Quick Order Galon dari {$roomNumber}!",
                    'message'    => "Permintaan antar {$qty}x {$galonItem->name} ke {$roomNumber}.",
                    'type'       => 'order',
                    'action_url' => route('petugas.antrean'),
                    'is_read'    => false,
                ]);
            }

            return $order;
        });

        return redirect()->route('penghuni.pesanan')
            ->with('success', "Quick Order Galon #{$order->order_code} berhasil! Petugas akan segera mengantar ke {$roomNumber}.");
    }
}
