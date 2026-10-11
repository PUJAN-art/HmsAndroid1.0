<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_rating' => ['required', 'integer', 'between:1,5'],
            'mess_rating' => ['required', 'integer', 'between:1,5'],
            'security_rating' => ['required', 'integer', 'between:1,5'],
            'staff_rating' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ]);

        $isAnonymous = $request->boolean('is_anonymous');
        $feedback = Feedback::create([
            'user_id' => $isAnonymous ? null : $request->user()->id,
            'room_rating' => $validated['room_rating'] ?? null,
            'mess_rating' => $validated['mess_rating'] ?? null,
            'security_rating' => $validated['security_rating'] ?? null,
            'staff_rating' => $validated['staff_rating'] ?? null,
            'comments' => $validated['comments'] ?? $validated['comment'] ?? null,
            'is_anonymous' => $isAnonymous,
            'status' => 'pending',
        ]);

        if (! $isAnonymous) {
            AuditLogger::log(
                'create_feedback',
                'Feedback',
                $feedback,
                'Student submitted feedback via mobile API'
            );
        }

        $notifications = app(NotificationService::class);
        if ($isAnonymous) {
            $notifications->notifyRole(
                'admin',
                'info',
                __('New Feedback Received'),
                __('New feedback has been submitted anonymously.'),
                ['feedback_id' => $feedback->id],
                route('admin.feedback.index')
            );
        } else {
            $notifications->notifyNewFeedback($feedback->id, $request->user()->id);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback submitted successfully.',
            'feedback' => [
                'id' => $feedback->id,
                'room_rating' => $feedback->room_rating,
                'mess_rating' => $feedback->mess_rating,
                'security_rating' => $feedback->security_rating,
                'staff_rating' => $feedback->staff_rating,
                'comments' => $feedback->comments,
                'is_anonymous' => (bool) $feedback->is_anonymous,
                'status' => $feedback->status,
                'created_at' => $feedback->created_at,
            ],
        ], 201);
    }
}