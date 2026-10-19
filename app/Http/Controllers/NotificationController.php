<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Tandai satu notifikasi sebagai sudah dibaca saat ditekan (FR-12).
     */
    public function markAsRead(Request $request, $id)
    {
        $notification = AppNotification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => Auth::user()->unreadNotificationsCount(),
                'action_url' => $notification->action_url,
            ]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return redirect()->back()->with('success', 'Notifikasi telah ditandai sebagai dibaca.');
    }

    /**
     * Buka notifikasi via link, tandai sebagai dibaca, lalu arahkan ke action_url (FR-12).
     */
    public function openAndRead(Request $request, $id)
    {
        $notification = AppNotification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return redirect()->back();
    }

    /**
     * Tandai semua notifikasi milik pengguna aktif sebagai sudah dibaca (FR-12).
     */
    public function readAll(Request $request)
    {
        AppNotification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sebagai sudah dibaca.');
    }
}
