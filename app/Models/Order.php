<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'user_id',
        'room_number',
        'order_type',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'notes',
        'processed_by',
        'completed_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'total_amount' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total_amount, 0, ',', '.');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'menunggu' => ['bg' => 'bg-transparent', 'text' => 'text-wait', 'border' => 'border-transparent', 'label' => 'Menunggu'],
            'diproses' => ['bg' => 'bg-transparent', 'text' => 'text-process', 'border' => 'border-transparent', 'label' => 'Diproses'],
            'diantar'  => ['bg' => 'bg-transparent', 'text' => 'text-brand', 'border' => 'border-transparent', 'label' => 'Diantar'],
            'selesai'  => ['bg' => 'bg-transparent', 'text' => 'text-done', 'border' => 'border-transparent', 'label' => 'Selesai'],
            'batal'    => ['bg' => 'bg-alert-wash', 'text' => 'text-alert', 'border' => 'border-transparent', 'label' => 'Dibatalkan'],
            default    => ['bg' => 'bg-transparent', 'text' => 'text-ink-soft', 'border' => 'border-transparent', 'label' => ucfirst($this->status)],
        };
    }

    public function isCancellable(): bool
    {
        return $this->status === 'menunggu';
    }

    public function getStepIndexAttribute(): int
    {
        return match ($this->status) {
            'menunggu' => 1,
            'diproses' => 2,
            'diantar'  => 3,
            'selesai'  => 4,
            default    => 0, // Batal
        };
    }

    // Ambang batas pesanan tertunda (dalam menit)
    public const DELAYED_THRESHOLD_WAITING_MINUTES = 15;
    public const DELAYED_THRESHOLD_PROCESSING_MINUTES = 15;
    public const DELAYED_THRESHOLD_DELIVERING_MINUTES = 30;

    /**
     * Tandai apakah pesanan tertunda melebihi ambang batas wajar.
     */
    public function getIsDelayedAttribute(): bool
    {
        if (in_array($this->status, ['selesai', 'batal'])) {
            return false;
        }

        if (!$this->created_at) {
            return false;
        }

        $minutes = (int) $this->created_at->diffInMinutes(now());

        return match ($this->status) {
            'menunggu' => $minutes > self::DELAYED_THRESHOLD_WAITING_MINUTES,
            'diproses' => $minutes > self::DELAYED_THRESHOLD_PROCESSING_MINUTES,
            'diantar'  => $minutes > self::DELAYED_THRESHOLD_DELIVERING_MINUTES,
            default    => false,
        };
    }

    /**
     * Durasi keterlambatan dalam format singkat (misal: 25m atau 1j 10m).
     */
    public function getDelayedDurationAttribute(): string
    {
        if (!$this->is_delayed || !$this->created_at) {
            return '';
        }

        $minutes = (int) $this->created_at->diffInMinutes(now());
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $rem = $minutes % 60;
            return $rem > 0 ? "{$hours}j {$rem}m" : "{$hours} jam";
        }

        return "{$minutes}m";
    }
}
