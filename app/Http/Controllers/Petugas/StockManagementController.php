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
     * Catat mutasi stok masuk (restock), keluar manual (rusak/bocor), atau stock opname (FR-10).
     *
     * Untuk opname, klien mengirim `physical_count` (hasil hitung fisik). Selisih dihitung di server
     * terhadap stok terkini yang sudah dikunci, bukan angka lama yang dilihat petugas di layar,
     * sehingga stok akhir selalu sama dengan hasil hitung fisik walau ada pesanan masuk bersamaan.
     * Menerapkan NFR-05 Integritas Data via DB::transaction & lockForUpdate.
     */
    public function adjustStock(Request $request)
    {
        $isOpname = $request->filled('physical_count');

        $request->validate([
            'catalog_item_id' => ['required', 'integer', 'exists:catalog_items,id'],
            'physical_count'  => ['nullable', 'integer', 'min:0', 'max:100000'],
            'type'            => [$isOpname ? 'nullable' : 'required', 'in:in,out'],
            'quantity'        => [$isOpname ? 'nullable' : 'required', 'integer', 'min:1', 'max:500'],
            'notes'           => ['required', 'string', 'max:200'],
        ]);

        $itemId = (int) $request->input('catalog_item_id');
        $notes = trim($request->input('notes'));
        $petugas = Auth::user();

        $result = DB::transaction(function () use ($request, $isOpname, $itemId, $notes, $petugas) {
            // Kunci baris dengan lockForUpdate untuk mencegah race condition
            $catalogItem = CatalogItem::where('id', $itemId)->lockForUpdate()->firstOrFail();

            $beforeStock = $catalogItem->stock;

            if ($isOpname) {
                $physical = (int) $request->input('physical_count');
                $delta = $physical - $beforeStock;

                if ($delta === 0) {
                    return ['item' => $catalogItem, 'type' => 'opname', 'qty' => 0, 'delta' => 0, 'after_stock' => $beforeStock];
                }

                $type = $delta > 0 ? 'in' : 'out';
                $qty = abs($delta);
                $notes .= ($notes !== '' ? ': ' : '') . "Hitung fisik {$beforeStock} -> {$physical}";
                $referenceType = 'stock_opname';
            } else {
                $type = $request->input('type');
                $qty = (int) $request->input('quantity');
                $referenceType = 'manual_adjustment';
            }

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
            StockLog::create([
                'catalog_item_id' => $catalogItem->id,
                'user_id'         => $petugas->id,
                'type'            => $type,
                'quantity'        => $qty,
                'before_stock'    => $beforeStock,
                'after_stock'     => $afterStock,
                'reference_type'  => $referenceType,
                'notes'           => $notes,
            ]);

            return ['item' => $catalogItem, 'type' => $type, 'qty' => $qty, 'delta' => $type === 'in' ? $qty : -$qty, 'after_stock' => $afterStock, 'opname' => $isOpname];
        });

        if ($result['type'] === 'opname') {
            return redirect()->back()
                ->with('info', "Stok \"{$result['item']->name}\" sudah sesuai hitung fisik ({$result['after_stock']} {$result['item']->unit}); tidak ada perubahan.");
        }

        if (!empty($result['opname'])) {
            $sign = $result['delta'] > 0 ? '+' : '';
            return redirect()->back()
                ->with('success', "Stock opname \"{$result['item']->name}\" tercatat: selisih {$sign}{$result['delta']} {$result['item']->unit}. Stok aktual sekarang {$result['after_stock']} {$result['item']->unit}.");
        }

        $typeLabel = $result['type'] === 'in' ? 'penambahan (+)' : 'pengurangan (-)';
        return redirect()->back()
            ->with('success', "Mutasi {$typeLabel} {$result['qty']} {$result['item']->unit} untuk \"{$result['item']->name}\" berhasil dicatat. Sisa stok aktual: {$result['after_stock']} {$result['item']->unit}.");
    }
}
