<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_item_id',
        'user_id',
        'type',
        'quantity',
        'before_stock',
        'after_stock',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'before_stock' => 'integer',
        'after_stock' => 'integer',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Sumber mutasi dalam bahasa yang mudah dipahami pengelola. */
    public function getReferenceLabelAttribute(): string
    {
        return match ($this->reference_type) {
            'order'              => 'Pesanan',
            'order_cancellation' => 'Pesanan dibatalkan',
            'initial_stock'      => 'Stok awal',
            'manual_restock'     => 'Barang datang',
            'restock_ai'         => 'Belanja rekomendasi',
            'stock_damage'       => 'Rusak / hilang',
            'stock_opname'       => 'Hitung ulang',
            'manual_adjustment'  => 'Penyesuaian manual',
            default              => $this->type === 'in' ? 'Stok masuk' : 'Stok keluar',
        };
    }

    /** Catatan siap tampil; merapikan data lama yang menulis "Kamar Kamar A-04". */
    public function getDisplayNotesAttribute(): ?string
    {
        return $this->notes ? str_replace('Kamar Kamar ', 'Kamar ', $this->notes) : null;
    }
}
