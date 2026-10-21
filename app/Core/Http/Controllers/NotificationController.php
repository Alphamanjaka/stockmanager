<?php

namespace App\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Cloche du back office : chaque utilisateur ne voit et ne lit que ses propres notifications.
 */
class NotificationController extends Controller
{
    /** Ouvre une notification : elle est marquée comme lue, puis son lien est suivi. */
    public function read(Request $request, string $notification)
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        return redirect($notification->data['url'] ?? route('admin.dashboard'));
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }
}
