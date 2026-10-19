<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Models\StockLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockManagementController extends Controller
{
    /**
     * Tampilkan halaman kelola stok galon & menu beserta audit log (FR-10).
     */
    public function index()
    {
        // Item Galon (Prioritas utama operasional kos)
        $galonItems = CatalogItem::where('category', 'galon')
            ->orderBy('name')
            ->get();

        // Item Makanan & Minuman
        $otherItems = CatalogItem::whereIn('category', ['makanan', 'minuman'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // 20 Riwayat Mutasi Stok Terkini (Audit Log)
        $recentLogs = StockLog::with(['catalogItem', 'user'])
            ->latest('created_at')
            ->take(20)
            ->get();

        // Hitung total item yang stoknya menipis/habis
        $lowStockCount = CatalogItem::whereColumn('stock', '<=', 'minimum_stock')->count();

        return view('petugas.stocks', compact('galonItems', 'otherItems', 'recentLogs', 'lowStockCount'));
    }

    /**
     * Catat mutasi stok masuk (restock) atau stok keluar manual (rusak/bocor) (FR-10).
     * Menerapkan NFR-05 Integritas Data via DB::transaction & lockForUpdate.
     */
    public function adjustStock(Request $request)
    {
        $request->validate([
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'type'            => ['required', 'in:in,out'],
            'quantity'        => ['required', 'integer', 'min:1', 'max:500'],
            'notes'           => ['required', 'string', 'max:255'],
        ]);

        $itemId = (int) $request->input('catalog_item_id');
        $type = $request->input('type');
        $qty = (int) $request->input('quantity');
        $notes = trim($request->input('notes'));
        $petugas = Auth::user();

        $result = DB::transaction(function () use ($itemId, $type, $qty, $notes, $petugas) {
            // Kunci baris dengan lockForUpdate untuk mencegah race condition
            $catalogItem = CatalogItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            $beforeStock = $catalogItem->stock;

            if ($type === 'out') {
                if ($catalogItem->stock < $qty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Pengurangan gagal: Stok {$catalogItem->name} saat ini hanya tersisa {$catalogItem->stock} {$catalogItem->unit} (tidak boleh negatif).",
                    ]);
                }
                $afterStock = $beforeStock - $qty;
                $catalogItem->decrement('stock', $qty);
            } else {
                $afterStock = $beforeStock + $qty;
                $catalogItem->increment('stock', $qty);
            }

            // Catat ke audit log mutasi stok
            $log = StockLog::create([
                'catalog_item_id' => $catalogItem->id,
                'user_id'         => $petugas->id,
                'type'            => $type,
                'quantity'        => $qty,
                'before_stock'    => $beforeStock,
                'after_stock'     => $afterStock,
                'reference_type'  => 'manual_adjustment',
                'notes'           => $notes,
            ]);

            return [
                'item'        => $catalogItem,
                'type'        => $type,
                'qty'         => $qty,
                'after_stock' => $afterStock,
            ];
        });

        $typeLabel = $result['type'] === 'in' ? 'penambahan (+)' : 'pengurangan (-)';
        return redirect()->back()
            ->with('success', "Mutasi {$typeLabel} {$result['qty']} {$result['item']->unit} untuk \"{$result['item']->name}\" berhasil dicatat. Sisa stok aktual: {$result['after_stock']} {$result['item']->unit}.");
    }
}
