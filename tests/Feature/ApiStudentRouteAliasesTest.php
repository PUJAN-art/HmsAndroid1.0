<?php

namespace Tests\Feature;

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