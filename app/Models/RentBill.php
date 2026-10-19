<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_code',
        'tenant_profile_id',
        'room_id',
        'month',
        'year',
        'amount',
        'due_date',
        'status',
        'payment_date',
        'payment_method',
        'payment_proof',
        'confirmed_by',
        'notes',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'integer',
        'due_date' => 'date',
        'payment_date' => 'datetime',
    ];

    public function tenantProfile(): BelongsTo
    {
        return $this->belongsTo(TenantProfile::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        return $months[$this->month] ?? (string)$this->month;
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->month_name . ' ' . $this->year;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Status efektif tagihan dengan satu sumber kebenaran (Single Source of Truth).
     * Jika belum lunas dan jatuh tempo < hari ini -> 'menunggak'.
     * Jika ada bukti transfer terunggah dan belum lunas -> 'menunggu_verifikasi'.
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'lunas') {
            return 'lunas';
        }

        if (!empty($this->payment_proof)) {
            return 'menunggu_verifikasi';
        }

        if ($this->status === 'menunggak') {
            return 'menunggak';
        }

        if ($this->due_date) {
            $dueDate = \Carbon\Carbon::parse($this->due_date)->startOfDay();
            $today = \Carbon\Carbon::now()->startOfDay();
            if ($dueDate->lt($today)) {
                return 'menunggak';
            }
        }

        return 'menunggu';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->effective_status === 'menunggak';
    }

    public function getIsWaitingVerificationAttribute(): bool
    {
        return $this->effective_status === 'menunggu_verifikasi';
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->effective_status === 'lunas';
    }

    public function getDaysOverdueAttribute(): int
    {
        if (!$this->due_date) {
            return 0;
        }

        $dueDate = \Carbon\Carbon::parse($this->due_date)->startOfDay();
        $today = \Carbon\Carbon::now()->startOfDay();

        if ($today->gt($dueDate)) {
            return (int) $today->diffInDays($dueDate);
        }

        return 0;
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->effective_status) {
            'lunas' => [
                'status' => 'lunas',
                'bg'     => 'bg-transparent',
                'text'   => 'text-done',
                'border' => 'border-transparent',
                'label'  => 'Lunas',
            ],
            'menunggu_verifikasi' => [
                'status' => 'menunggu_verifikasi',
                'bg'     => 'bg-transparent',
                'text'   => 'text-wait',
                'border' => 'border-transparent',
                'label'  => 'Menunggu Verifikasi',
            ],
            'menunggak' => [
                'status' => 'menunggak',
                'bg'     => 'bg-alert-wash',
                'text'   => 'text-alert',
                'border' => 'border-transparent',
                'label'  => 'Menunggak',
            ],
            default => [
                'status' => 'menunggu',
                'bg'     => 'bg-transparent',
                'text'   => 'text-wait',
                'border' => 'border-transparent',
                'label'  => 'Menunggu Pembayaran',
            ],
        };
    }
}
