<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomRequest;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoomRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = RoomRequest::where('user_id', $request->user()->id)
            ->with(['room', 'bed'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'bed_id' => ['nullable', 'integer', 'exists:beds,id'],
            'student_message' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->createRequest(
            $request,
            (int) $validated['room_id'],
            isset($validated['bed_id']) ? (int) $validated['bed_id'] : null,
            $validated['student_message'] ?? null,
        );
    }

    public function storeForRoom(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'bed_id' => ['nullable', 'integer', 'exists:beds,id'],
            'student_message' => ['nullable', 'string', 'max:500'],
        ]);

        return $this->createRequest(
            $request,
            $id,
            isset($validated['bed_id']) ? (int) $validated['bed_id'] : null,
            $validated['student_message'] ?? null,
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $roomRequest = RoomRequest::where('user_id', $request->user()->id)
            ->with(['room', 'bed'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $roomRequest,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $roomRequest = RoomRequest::where('user_id', $request->user()->id)->findOrFail($id);

        if ($roomRequest->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending room requests can be cancelled.',
            ], 422);
        }

        $roomRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Room request cancelled successfully.',
        ]);
    }

    private function createRequest(
        Request $request,
        int $roomId,
        ?int $bedId,
        ?string $studentMessage,
    ): JsonResponse {
        $user = $request->user();

        $roomRequest = DB::transaction(function () use ($user, $roomId, $bedId, $studentMessage) {
            $room = Room::with('beds')->lockForUpdate()->findOrFail($roomId);

            if ($room->status !== 'active' || $room->availableBedsCount() < 1) {
                return null;
            }

            if ($bedId !== null) {
                $bed = $room->beds->firstWhere('id', $bedId);
                if (! $bed || $bed->is_occupied) {
                    return false;
                }
            }

            if (RoomRequest::where('user_id', $user->id)->where('status', 'pending')->exists()) {
                return 'pending';
            }

            if ($user->bed_id) {
                return 'assigned';
            }

            return RoomRequest::create([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'bed_id' => $bedId,
                'student_message' => $studentMessage,
                'status' => 'pending',
            ]);
        });

        if ($roomRequest === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'This room has no available beds.',
            ], 422);
        }

        if ($roomRequest === false) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected bed is not available in this room.',
            ], 422);
        }

        if ($roomRequest === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have a pending room request.',
            ], 422);
        }

        if ($roomRequest === 'assigned') {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have a room assigned.',
            ], 422);
        }

        app(NotificationService::class)->notifyNewRoomRequest($roomRequest->id, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Room request submitted successfully.',
            'data' => $roomRequest->load(['room', 'bed']),
        ], 201);
    }
}