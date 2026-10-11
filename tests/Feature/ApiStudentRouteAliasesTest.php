<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentRouteAliasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_rooms_alias_returns_room_list(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Room::create([
            'room_number' => '401',
            'capacity' => 1,
            'type' => 'standard',
            'status' => 'active',
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/rooms')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rooms.0.room_number', '401');
    }

    public function test_student_route_aliases_require_a_student_token(): void
    {
        $this->getJson('/api/student/rooms')->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/student/rooms')
            ->assertForbidden();
    }

    public function test_student_data_aliases_return_successful_responses(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum');

        foreach ([
            '/api/student/fees',
            '/api/student/attendance',
            '/api/student/complaints',
        ] as $uri) {
            $this->getJson($uri)
                ->assertOk()
                ->assertJsonPath('status', 'success');
        }
    }

    public function test_student_can_list_only_their_own_activity_logs_with_pagination(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $oldLog = AuditLog::create([
            'user_id' => $student->id,
            'action' => 'checkin',
            'module' => 'Attendance',
            'details' => 'Checked in.',
            'created_at' => now()->subMinute(),
        ]);
        AuditLog::create([
            'user_id' => $student->id,
            'action' => 'create_complaint',
            'module' => 'Complaint',
            'details' => 'Submitted a complaint.',
            'created_at' => now(),
        ]);
        AuditLog::create([
            'user_id' => $otherStudent->id,
            'action' => 'private_action',
            'module' => 'Private',
            'details' => 'Must not be visible.',
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/activity-logs?per_page=1')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('pagination.current_page', 1)
            ->assertJsonPath('pagination.last_page', 2)
            ->assertJsonPath('pagination.per_page', 1)
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonPath('data.0.action', 'create_complaint')
            ->assertJsonMissing(['details' => 'Must not be visible.']);

        $this->getJson('/api/student/activity-logs?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldLog->id)
            ->assertJsonPath('data.0.action', 'checkin');
    }

    public function test_activity_logs_api_requires_student_authentication(): void
    {
        $this->getJson('/api/student/activity-logs')->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/student/activity-logs')
            ->assertForbidden();

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/activity-logs?per_page=101')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');
    }

    public function test_admin_can_list_students_and_pending_registrations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'student', 'is_approved' => true]);
        User::factory()->create(['role' => 'student', 'is_approved' => false]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/students')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('pagination.total', 2);

        $this->getJson('/api/admin/students/pending')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.is_approved', false);
    }

    public function test_admin_student_directory_rejects_non_admins(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/admin/students')
            ->assertForbidden();
    }
}
