<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'avatar',
        'password',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Penghuni hanya boleh memakai aplikasi selama profil huniannya aktif
     * (setelah checkout akun otomatis tidak bisa dipakai lagi).
     */
    public function canAccessApp(): bool
    {
        if ($this->role !== 'penghuni') {
            return true;
        }

        return (bool) $this->tenantProfile?->is_active;
    }

    public function isPengelola(): bool
    {
        return $this->role === 'pengelola';
    }

    public function isPetugas(): bool
    {
        return $this->role === 'petugas';
    }

    public function isPenghuni(): bool
    {
        return $this->role === 'penghuni';
    }

    public function tenantProfile(): HasOne
    {
        return $this->hasOne(TenantProfile::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class)->latest();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->appNotifications()->where('is_read', false)->count();
    }
}
