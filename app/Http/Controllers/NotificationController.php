<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function send(Request $request, FirebaseService $firebase): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'title' => 'required|string',
            'body' => 'required|string',
        ]);

        $firebase->sendNotification(
            $request->token,
            $request->title,
            $request->body,
            [
                'title' => $request->title,
                'body' => $request->body,
                'type' => 'task',
                'task_id' => '123',
                'action_1' => 'ACEPTAR',
                'action_2' => 'RECHAZAR',
            ]
        );

        return response()->json(['status' => 'ok']);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->query('since');

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->take(50);

        if ($since) {
            $notifications->where('created_at', '>', $since);
        }

        return response()->json(['data' => $notifications->get()]);
    }

    public function sync(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notifications' => 'required|array',
            'notifications.*.id' => 'required|string',
            'notifications.*.read' => 'required|boolean',
        ]);

        $user = $request->user();
        $now = gmdate('Y-m-d H:i:s');

        foreach ($validated['notifications'] as $notification) {
            $user->notifications()
                ->where('uuid', $notification['id'])
                ->update([
                    'read' => $notification['read'],
                    'updated_at' => $now,
                    'sync_status' => 'synced',
                ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $user = request()->user();
        $now = gmdate('Y-m-d H:i:s');

        $user->notifications()
            ->where('uuid', $id)
            ->update([
                'read' => true,
                'updated_at' => $now,
                'sync_status' => 'synced',
            ]);

        return response()->json(null, 204);
    }
}
