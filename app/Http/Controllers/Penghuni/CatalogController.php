<?php

namespace App\Http\Controllers\Penghuni;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function __construct(private OrderService $orders)
    {
    }

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

        // Hitung total item per kategori untuk badge tab (satu query)
        $perCategory = CatalogItem::where('is_available', true)
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $counts = [
            'semua'   => (int) $perCategory->sum(),
            'makanan' => (int) ($perCategory['makanan'] ?? 0),
            'minuman' => (int) ($perCategory['minuman'] ?? 0),
            'galon'   => (int) ($perCategory['galon'] ?? 0),
        ];

        // Seluruh menu yang tersedia (tanpa filter) agar keranjang tersimpan dapat dipulihkan dengan harga & stok terbaru.
        $catalogMap = CatalogItem::where('is_available', true)->get()->mapWithKeys(fn ($i) => [
            $i->id => ['name' => $i->name, 'price' => $i->price, 'stock' => $i->stock, 'category' => $i->category],
        ]);

        return view('penghuni.catalog', compact('items', 'category', 'search', 'counts', 'roomNumber', 'catalogMap'));
    }

    /**
     * Simpan pesanan makanan/minuman/galon dari keranjang belanja Alpine.js (FR-07).
     * Logika stok dan transaksi ada di OrderService (NFR-05 Integritas Data).
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $roomNumber = $user->tenantProfile?->room?->room_number ?? 'Kamar Penghuni';

        $request->validate([
            'items_json'     => ['required', 'string', 'max:20000'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(config('kos.payment_methods')))],
            'order_notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $cart = json_decode($request->input('items_json'), true);

        if (!is_array($cart)) {
            throw ValidationException::withMessages(['items_json' => 'Keranjang pesanan tidak valid.']);
        }

        $order = $this->orders->place(
            $user,
            $roomNumber,
            $cart,
            $request->input('payment_method'),
            $request->input('order_notes'),
        );

        return redirect()->route('penghuni.pesanan')
            ->with('cart_clear', true)
            ->with('success', "Pesanan #{$order->order_code} berhasil dibuat! Petugas akan mengantarkannya ke {$roomNumber}.");
    }

    /**
     * Fitur Pesan Galon Cepat 1-Click Order (FR-09).
     */
    public function quickGalon(Request $request)
    {
        $user = Auth::user();
        $roomNumber = $user->tenantProfile?->room?->room_number ?? 'Kamar Penghuni';

        $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'quantity'        => ['nullable', 'integer', 'min:1', 'max:5'],
            'quick_note'      => ['nullable', 'string', 'max:255'],
        ]);

        $qty = (int) ($request->input('quantity', 1) ?: 1);
        $note = $request->input('quick_note') ?: 'Tolong pasang di dispenser kamar.';

        $order = $this->orders->place(
            $user,
            $roomNumber,
            [['id' => $request->input('catalog_item_id'), 'quantity' => $qty, 'notes' => $note]],
            null,
            $note,
            'galon',
            'catalog_item_id',
        );

        return redirect()->route('penghuni.pesanan')
            ->with('success', "Quick Order Galon #{$order->order_code} berhasil! Petugas akan segera mengantar ke {$roomNumber}.");
    }
}
