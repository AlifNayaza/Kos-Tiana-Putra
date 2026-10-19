<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'price',
        'stock',
        'minimum_stock',
        'unit',
        'description',
        'image',
        'is_available',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'minimum_stock' => 'integer',
        'is_available' => 'boolean',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockLogs(): HasMany
    {
        return $this->hasMany(StockLog::class)->latest();
    }

    public function stockPredictions(): HasMany
    {
        return $this->hasMany(StockPrediction::class)->latest();
    }

    public function latestPrediction()
    {
        return $this->hasOne(StockPrediction::class)->latestOfMany();
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock;
    }

    /**
     * Satu accessor di model untuk status stok: habis jika 0, menipis jika <= minimum_stock, selain itu aman.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) {
            return 'habis';
        }
        if ($this->stock <= $this->minimum_stock) {
            return 'menipis';
        }
        return 'aman';
    }

    public function getStockStatusBadgeAttribute(): array
    {
        return match ($this->stock_status) {
            'habis' => [
                'status' => 'habis',
                'label'  => 'Habis',
                'bg'     => 'bg-transparent',
                'text'   => 'text-alert',
                'border' => 'border-transparent',
            ],
            'menipis' => [
                'status' => 'menipis',
                'label'  => 'Menipis',
                'bg'     => 'bg-transparent',
                'text'   => 'text-wait',
                'border' => 'border-transparent',
            ],
            default => [
                'status' => 'aman',
                'label'  => 'Aman',
                'bg'     => 'bg-transparent',
                'text'   => 'text-done',
                'border' => 'border-transparent',
            ],
        };
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
