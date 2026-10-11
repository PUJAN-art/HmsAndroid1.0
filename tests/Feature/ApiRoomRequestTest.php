<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRoomRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_request_a_room_and_retrieve_request_history(): void
    {
        $student = User::factory()->create(['role' => 'student', 'bed_id' => null]);
        $room = Room::create([
            'room_number' => '501',
            'capacity' => 1,
            'type' => 'standard',
            'status' => 'active',
        ]);
        $bed = $room->beds()->create([
            'bed_number' => '501-1',
            'is_occupied' => false,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/student/rooms/{$room->id}/request", [
                'bed_id' => $bed->id,
                'student_message' => 'Please assign this room.',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.room_id', $room->id)
            ->assertJsonPath('data.bed_id', $bed->id)
            ->assertJsonPath('data.status', 'pending');

        $this->getJson('/api/student/room-requests')
            ->assertOk()
            ->assertJsonPath('data.0.room_id', $room->id);
    }

    public function test_room_request_requires_student_authentication(): void
    {
        $this->postJson('/api/room-requests', ['room_id' => 1])->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/room-requests', ['room_id' => 1])
            ->assertForbidden();
    }

    public function test_student_cannot_request_a_full_room_or_repeat_a_pending_request(): void
    {
        $student = User::factory()->create(['role' => 'student', 'bed_id' => null]);
        $room = Room::create([
            'room_number' => '502',
            'capacity' => 1,
            'type' => 'standard',
            'status' => 'active',
        ]);
        $bed = $room->beds()->create([
            'bed_number' => '502-1',
            'is_occupied' => true,
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/student/rooms/{$room->id}/request")
            ->assertUnprocessable();

        $bed->update(['is_occupied' => false]);
        $this->postJson("/api/student/rooms/{$room->id}/request")
            ->assertCreated();

        $this->postJson("/api/student/rooms/{$room->id}/request")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'You already have a pending room request.');
    }
}