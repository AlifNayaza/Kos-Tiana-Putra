<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'room_id',
        'id_card_number',
        'emergency_contact_name',
        'emergency_contact_phone',
        'occupation',
        'institution',
        'start_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function rentBills(): HasMany
    {
        return $this->hasMany(RentBill::class);
    }

    public function latestRentBill()
    {
        return $this->hasOne(RentBill::class)->latestOfMany();
    }
}
