<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiStudentProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_update_web_editable_profile_fields_and_photo(): void
    {
        Storage::fake('public');
        $student = User::factory()->create([
            'role' => 'student',
            'phone' => '9800000000',
            'address' => 'Old address',
        ]);

        $this->actingAs($student, 'sanctum')
            ->post('/api/student/profile', [
                'phone' => '9812345678',
                'address' => 'New address, Kathmandu',
                'profile_photo' => UploadedFile::fake()->create('profile.jpg', 100, 'image/jpeg'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('student.phone', '9812345678')
            ->assertJsonPath('student.contact', '9812345678')
            ->assertJsonPath('student.address', 'New address, Kathmandu')
            ->assertJsonPath('student.profile_photo_url', asset('storage/'.$student->fresh()->profile_photo_path));

        $student->refresh();
        $this->assertSame('9812345678', $student->phone);
        $this->assertSame('New address, Kathmandu', $student->address);
        Storage::disk('public')->assertExists($student->profile_photo_path);
    }

    public function test_student_profile_update_requires_student_auth_and_valid_fields(): void
    {
        $this->postJson('/api/student/profile', ['phone' => '9812345678'])
            ->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/student/profile', ['phone' => '9812345678'])
            ->assertForbidden();

        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/profile', ['phone' => str_repeat('1', 21)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('phone');

        $this->assertNull($student->fresh()->phone);
    }

    public function test_post_password_change_works_and_preserves_the_existing_put_alias(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'password' => 'OldPass@123',
        ]);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/auth/password', [
                'current_password' => 'OldPass@123',
                'password' => 'NewPass@456',
                'password_confirmation' => 'NewPass@456',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $this->assertTrue(Hash::check('NewPass@456', $student->fresh()->password));

        $this->putJson('/api/auth/password', [
            'current_password' => 'NewPass@456',
            'password' => 'NextPass@789',
            'password_confirmation' => 'NextPass@789',
        ])->assertOk();

        $this->assertTrue(Hash::check('NextPass@789', $student->fresh()->password));
    }
}
