<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'in:all,read,unread'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->user()->notifications()->latest();

        if (! empty($validated['type']) && $validated['type'] !== 'all') {
            $query->where('type', $validated['type']);
        }

        if (($validated['status'] ?? null) === 'unread') {
            $query->whereNull('read_at');
        } elseif (($validated['status'] ?? null) === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'status' => 'success',
            'data' => $notifications->getCollection()
                ->map(fn (Notification $notification) => $this->notificationResource($notification))
                ->values(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Notification $notification) => $this->notificationResource($notification))
            ->values();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = Cache::remember("user:{$userId}:unread_notification_count", 30, fn () => Notification::query()
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count());

        return response()->json([
            'status' => 'success',
            'unread_count' => $count,
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        $this->forgetUserCaches($request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllAsRead(Request $request, NotificationService $notificationService): JsonResponse
    {
        $count = $notificationService->markAllAsRead($request->user()->id);
        $this->forgetUserCaches($request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications marked as read.',
            'count' => $count,
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();
        $this->forgetUserCaches($request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted.',
        ]);
    }

    private function notificationResource(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'data' => $notification->data,
            'action_url' => $notification->action_url,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'created_at' => $notification->created_at?->toDateTimeString(),
            'updated_at' => $notification->updated_at?->toDateTimeString(),
        ];
    }

    private function forgetUserCaches(int $userId): void
    {
        Cache::forget("user:{$userId}:unread_notification_count");
        Cache::forget("user:{$userId}:recent_notifications");
    }
}
