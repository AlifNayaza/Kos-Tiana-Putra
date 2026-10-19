<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_item_id',
        'predicted_demand_qty',
        'target_stock',
        'current_stock',
        'recommended_restock_qty',
        'prediction_period',
        'daily_avg_sales',
        'safety_stock',
        'sales_trend',
        'status',
        'recommendation_note',
        'confidence_score',
        'calculated_at',
    ];

    protected $casts = [
        'predicted_demand_qty' => 'integer',
        'target_stock' => 'integer',
        'current_stock' => 'integer',
        'recommended_restock_qty' => 'integer',
        'daily_avg_sales' => 'decimal:1',
        'safety_stock' => 'integer',
        'confidence_score' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    /**
     * Alias accessor agar kompatibel dengan pemanggilan $pred->daily_average_sales
     */
    public function getDailyAverageSalesAttribute(): float
    {
        return (float) ($this->daily_avg_sales ?? 0);
    }

    /**
     * Estimasi modal belanja restock (Saran Restock * Harga Satuan/Modal)
     */
    public function getEstimatedCostAttribute(): float
    {
        $unitPrice = (float) ($this->catalogItem?->price ?? 0);
        return (float) ($this->recommended_restock_qty * $unitPrice);
    }

    public function getFormattedEstimatedCostAttribute(): string
    {
        return 'Rp ' . number_format($this->estimated_cost, 0, ',', '.');
    }

    /**
     * Badge visual tren penjualan (Naik, Stabil, Turun)
     */
    public function getTrendBadgeAttribute(): array
    {
        return match ($this->sales_trend) {
            'naik' => [
                'icon'  => '↗',
                'label' => 'Tren Naik',
                'bg'    => 'bg-transparent text-done border-transparent',
            ],
            'turun' => [
                'icon'  => '↘',
                'label' => 'Tren Turun',
                'bg'    => 'bg-alert-wash text-alert border-transparent',
            ],
            default => [
                'icon'  => '→',
                'label' => 'Stabil',
                'bg'    => 'bg-transparent text-ink-soft border-transparent',
            ],
        };
    }

    /**
     * Badge visual status ketersediaan stok
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'kritis' => [
                'label' => 'Kritis',
                'sub'   => 'Stok Habis / Kritis',
                'bg'    => 'bg-alert-wash text-alert border-transparent',
                'bar'   => 'bg-alert',
            ],
            'waspada' => [
                'label' => 'Waspada',
                'sub'   => 'Mendekati Batas',
                'bg'    => 'bg-wait-wash text-wait border-transparent',
                'bar'   => 'bg-wait',
            ],
            default => [
                'label' => 'Aman',
                'sub'   => 'Stok Cukup',
                'bg'    => 'bg-transparent text-done border-transparent',
                'bar'   => 'bg-done',
            ],
        };
    }

    /**
     * Rincian rumus matematika transparan
     * Target = Proyeksi 7H + Safety Stock
     * Saran = max(0, Target - Stok Saat Ini)
     */
    public function getFormulaDetailAttribute(): array
    {
        $buffer = $this->safety_stock > 0 ? $this->safety_stock : ($this->catalogItem?->minimum_stock ?? 0);
        $target = $this->target_stock > 0 ? $this->target_stock : ($this->predicted_demand_qty + $buffer);
        $deficit = max(0, $this->predicted_demand_qty - $this->current_stock);

        return [
            'predicted'   => $this->predicted_demand_qty,
            'safety'      => $buffer,
            'target'      => $target,
            'current'     => $this->current_stock,
            'restock'     => $this->recommended_restock_qty,
            'deficit'     => $deficit,
            'explanation' => "Kurang {$deficit} + Buffer {$buffer}",
        ];
    }

    /**
     * Estimasi ketahanan stok (Days of Supply)
     */
    public function getDaysOfSupplyAttribute(): float
    {
        $daily = max((float) $this->daily_avg_sales, 0.5);
        return round($this->current_stock / $daily, 1);
    }

    /**
     * Badge visual ketahanan stok
     */
    public function getDaysOfSupplyBadgeAttribute(): array
    {
        $days = $this->days_of_supply;
        if ($days <= 2.5) {
            return [
                'text'  => "~{$days} hari",
                'class' => 'bg-alert-wash text-alert border-alert/20 font-bold',
                'label' => 'Habis < 2.5 Hari',
            ];
        }
        if ($days <= 5.0) {
            return [
                'text'  => "~{$days} hari",
                'class' => 'bg-wait-wash text-wait border-wait/20 font-semibold',
                'label' => 'Cukup 3-5 Hari',
            ];
        }
        return [
            'text'  => "~{$days} hari",
            'class' => 'bg-done-wash text-done border border-done/20 font-semibold',
            'label' => 'Cukup > 5 Hari',
        ];
    }
}
