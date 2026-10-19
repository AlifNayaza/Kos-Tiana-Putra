<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'floor',
        'type',
        'monthly_price',
        'facilities',
        'status',
        'size',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'floor' => 'integer',
            'monthly_price' => 'integer',
        ];
    }

    public function activeTenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class)->where('is_active', true);
    }

    public function tenantProfiles(): HasMany
    {
        return $this->hasMany(TenantProfile::class);
    }

    public function rentBills(): HasMany
    {
        return $this->hasMany(RentBill::class);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'tersedia';
    }

    public function isOccupied(): bool
    {
        return $this->status === 'terisi';
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->monthly_price, 0, ',', '.');
    }
}
