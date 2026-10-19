<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CatalogManagementController extends Controller
{
    /**
     * Tampilkan katalog menu warkop & galon untuk pengelolaan (FR-06).
     */
    public function index(Request $request)
    {
        $category = $request->query('category', 'semua');
        $search = $request->query('q');

        $query = CatalogItem::query();

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

        $stats = [
            'total'   => CatalogItem::count(),
            'galon'   => CatalogItem::where('category', 'galon')->count(),
            'makanan' => CatalogItem::where('category', 'makanan')->count(),
            'minuman' => CatalogItem::where('category', 'minuman')->count(),
            'menipis' => CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->count(),
        ];

        return view('pengelola.katalog.index', compact('items', 'stats', 'category', 'search'));
    }

    /**
     * Tambah item menu baru ke katalog (FR-06).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['required', 'in:makanan,minuman,galon'],
            'price'         => ['required', 'integer', 'min:500'],
            'stock'         => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:1'],
            'unit'          => ['required', 'string', 'max:50'],
            'description'   => ['nullable', 'string'],
            'is_available'  => ['nullable', 'boolean'],
        ]);

        $validated['is_available'] = $request->boolean('is_available', true);

        DB::transaction(function () use ($validated) {
            $item = CatalogItem::create($validated);

            // Catat stok awal jika stok > 0
            if ($item->stock > 0) {
                StockLog::create([
                    'catalog_item_id' => $item->id,
                    'user_id'         => Auth::id(),
                    'type'            => 'in',
                    'quantity'        => $item->stock,
                    'before_stock'    => 0,
                    'after_stock'     => $item->stock,
                    'reference_type'  => 'initial_stock',
                    'notes'           => 'Stok awal penambahan menu baru',
                ]);
            }
        });

        return redirect()->route('pengelola.katalog.index')
            ->with('success', "Item menu \"{$validated['name']}\" berhasil ditambahkan.");
    }

    /**
     * Perbarui data menu, harga, atau stok minimum (FR-06).
     */
    public function update(Request $request, CatalogItem $katalog)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category'      => ['required', 'in:makanan,minuman,galon'],
            'price'         => ['required', 'integer', 'min:500'],
            'minimum_stock' => ['required', 'integer', 'min:1'],
            'unit'          => ['required', 'string', 'max:50'],
            'description'   => ['nullable', 'string'],
            'is_available'  => ['nullable', 'boolean'],
        ]);

        $validated['is_available'] = $request->boolean('is_available', true);

        $katalog->update($validated);

        return redirect()->route('pengelola.katalog.index')
            ->with('success', "Menu \"{$katalog->name}\" berhasil diperbarui.");
    }

    /**
     * Hapus item dari katalog.
     */
    public function destroy(CatalogItem $katalog)
    {
        $name = $katalog->name;
        $katalog->delete();

        return redirect()->route('pengelola.katalog.index')
            ->with('success', "Item \"{$name}\" telah dihapus dari katalog.");
    }

    /**
     * Tampilkan halaman Audit Log Riwayat Mutasi Stok lengkap (FR-10).
     */
    public function stockLogsIndex(Request $request)
    {
        $typeFilter = $request->query('type');
        $itemFilter = $request->query('item_id');
        $search = $request->query('q');

        $query = StockLog::with(['catalogItem', 'user']);

        if ($typeFilter && $typeFilter !== 'semua') {
            $query->where('type', $typeFilter);
        }

        if ($itemFilter && $itemFilter !== 'semua') {
            $query->where('catalog_item_id', $itemFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhereHas('catalogItem', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $logs = $query->latest('created_at')->paginate(20);
        $catalogItems = CatalogItem::orderBy('name')->get();

        return view('pengelola.stok.index', compact('logs', 'catalogItems', 'typeFilter', 'itemFilter', 'search'));
    }
}
