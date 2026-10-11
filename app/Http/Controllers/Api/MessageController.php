<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MessageController extends Controller
{
    public function inbox(Request $request): JsonResponse
    {
        $messages = Message::query()
            ->where('to_user_id', $request->user()->id)
            ->with('sender:id,name,email')
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $messages->getCollection()
                ->map(fn (Message $message) => $this->messageResource($message))
                ->values(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $message = Message::with(['sender:id,name,email', 'replies.sender:id,name,email'])
            ->findOrFail($id);

        if ($message->to_user_id !== $request->user()->id
            && $message->from_user_id !== $request->user()->id) {
            abort(403);
        }

        if ($message->to_user_id === $request->user()->id) {
            $message->markAsRead();
            Cache::forget("user:{$request->user()->id}:unread_message_count");
        }

        return response()->json([
            'status' => 'success',
            'data' => array_merge($this->messageResource($message), [
                'replies' => $message->replies
                    ->sortBy('created_at')
                    ->values()
                    ->map(fn (Message $reply) => $this->messageResource($reply)),
            ]),
        ]);
    }

    public function reply(Request $request, int $id, NotificationService $notificationService): JsonResponse
    {
        $message = Message::findOrFail($id);

        if ($message->to_user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string'],
        ]);

        $reply = Message::create([
            'from_user_id' => $request->user()->id,
            'to_user_id' => $message->from_user_id,
            'subject' => 'Re: '.$message->subject,
            'message' => $validated['message'],
            'reply_to_id' => $message->id,
        ]);

        AuditLogger::log(
            'send_reply',
            'Message',
            null,
            "Student replied to message: {$message->subject}"
        );

        $notificationService->notifyUser(
            $message->from_user_id,
            'message_reply_received',
            __('New Reply'),
            __('You have received a reply from ').$request->user()->name,
            ['message_id' => $reply->id, 'subject' => $reply->subject, 'student_id' => $request->user()->id],
            route('admin.messages.show', $message->id)
        );

        Cache::forget("user:{$message->from_user_id}:unread_message_count");

        return response()->json([
            'status' => 'success',
            'message' => 'Reply sent successfully.',
            'data' => $this->messageResource($reply->load('sender:id,name,email')),
        ], 201);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $count = Cache::remember("user:{$userId}:unread_message_count", 30, fn () => Message::query()
            ->where('to_user_id', $userId)
            ->where('is_read', false)
            ->count());

        return response()->json([
            'status' => 'success',
            'unread_count' => $count,
        ]);
    }

    private function messageResource(Message $message): array
    {
        return [
            'id' => $message->id,
            'from_user_id' => $message->from_user_id,
            'to_user_id' => $message->to_user_id,
            'subject' => $message->subject,
            'message' => $message->message,
            'is_read' => $message->is_read,
            'read_at' => $message->read_at?->toDateTimeString(),
            'reply_to_id' => $message->reply_to_id,
            'created_at' => $message->created_at?->toDateTimeString(),
            'sender' => $message->relationLoaded('sender') ? [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'email' => $message->sender?->email,
            ] : null,
        ];
    }
}
