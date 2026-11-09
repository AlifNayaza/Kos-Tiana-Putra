<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotificationService
{
    /**
     * Kirim satu notifikasi ke satu pengguna.
     */
    public function notify(int $userId, string $title, string $message, string $type, ?string $actionUrl = null): AppNotification
    {
        return AppNotification::create([
            'user_id'    => $userId,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'action_url' => $actionUrl,
            'is_read'    => false,
        ]);
    }

    /**
     * Kirim notifikasi yang sama ke semua pengguna dengan peran tertentu.
     */
    public function notifyRole(string $role, string $title, string $message, string $type, ?string $actionUrl = null): void
    {
        $now = now();

        $rows = User::where('role', $role)->pluck('id')->map(fn ($id) => [
            'user_id'    => $id,
            'title'      => $title,
            'message'    => $message,
            'type'       => $type,
            'action_url' => $actionUrl,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($rows) {
            AppNotification::insert($rows);
        }
    }
}
