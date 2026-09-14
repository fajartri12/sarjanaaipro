<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Daftar notifikasi user — endpoint JSON untuk dropdown di topbar. */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread' => $user->unreadNotificationsCount(),
            'notifications' => $user->appNotifications()
                ->limit(15)
                ->get()
                ->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'body' => $n->body,
                    'read' => $n->read_at !== null,
                    'at' => $n->created_at->diffForHumans(),
                    'url' => ! empty($n->data['project_id']) ? route('projects.show', $n->data['project_id']) : null,
                ]),
        ]);
    }

    public function markAllRead(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->user()->appNotifications()->unread()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markRead(Request $request, AppNotification $notification): \Illuminate\Http\JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}