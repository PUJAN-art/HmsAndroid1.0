<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentIdCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_id_card_returns_all_card_fields_and_room_assignment(): void
    {
        $room = Room::create([
            'room_number' => '601',
            'capacity' => 2,
            'type' => 'standard',
            'status' => 'active',
        ]);
        $bed = $room->beds()->create([
            'bed_number' => '601-2',
            'is_occupied' => true,
        ]);
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '9800000000',
            'student_id_number' => 'HMS-601',
            'department' => 'Computer',
            'year' => 2,
            'semester' => 4,
            'blood_group' => 'B+',
            'id_card_issued_date' => '2026-01-01',
            'id_card_expiry_date' => '2027-01-01',
            'bed_id' => $bed->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/id-card')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.card_title', 'Student ID Card')
            ->assertJsonPath('data.student.student_id_number', 'HMS-601')
            ->assertJsonPath('data.student.department', 'Computer')
            ->assertJsonPath('data.student.year', 2)
            ->assertJsonPath('data.student.semester', 4)
            ->assertJsonPath('data.student.blood_group', 'B+')
            ->assertJsonPath('data.student.expiry_label', 'Jan 2027')
            ->assertJsonPath('data.room.room_number', '601')
            ->assertJsonPath('data.room.bed_number', '601-2')
            ->assertJsonPath('data.qr_code_payload', route('admin.students.profile', $student->id));
    }

    public function test_id_card_returns_null_room_when_student_has_no_assignment(): void
    {
        $student = User::factory()->create(['role' => 'student', 'bed_id' => null]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/id-card')
            ->assertOk()
            ->assertJsonPath('data.room', null)
            ->assertJsonPath('data.student.expiry_label', 'Valid till Graduation');
    }

    public function test_id_card_requires_an_authenticated_student(): void
    {
        $this->getJson('/api/student/id-card')->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/student/id-card')
            ->assertForbidden();
    }
}