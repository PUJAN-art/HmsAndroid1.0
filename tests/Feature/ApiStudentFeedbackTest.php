<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_submit_feedback_from_the_mobile_form(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/feedback', [
                'room_rating' => 4,
                'mess_rating' => 5,
                'security_rating' => 3,
                'comment' => 'Please improve the study area.',
                'is_anonymous' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('feedback.room_rating', 4)
            ->assertJsonPath('feedback.mess_rating', 5)
            ->assertJsonPath('feedback.security_rating', 3)
            ->assertJsonPath('feedback.comments', 'Please improve the study area.')
            ->assertJsonPath('feedback.is_anonymous', false)
            ->assertJsonPath('feedback.status', 'pending');

        $this->assertDatabaseHas('feedbacks', [
            'user_id' => $student->id,
            'room_rating' => 4,
            'mess_rating' => 5,
            'security_rating' => 3,
            'comments' => 'Please improve the study area.',
            'is_anonymous' => false,
            'status' => 'pending',
        ]);
    }

    public function test_anonymous_feedback_is_not_linked_to_the_student(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/feedback', [
                'room_rating' => 5,
                'mess_rating' => 4,
                'security_rating' => 4,
                'comments' => 'Anonymous feedback.',
                'is_anonymous' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('feedback.is_anonymous', true);

        $this->assertDatabaseHas('feedbacks', [
            'user_id' => null,
            'comments' => 'Anonymous feedback.',
            'is_anonymous' => true,
        ]);
        $this->assertSame(0, AuditLog::where('module', 'Feedback')->count());
    }

    public function test_feedback_ratings_must_be_between_one_and_five(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/feedback', [
                'room_rating' => 6,
                'mess_rating' => 4,
                'security_rating' => 3,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('room_rating');

        $this->assertSame(0, Feedback::count());
    }

    public function test_all_three_displayed_ratings_are_required(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/feedback', [
                'room_rating' => 4,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mess_rating', 'security_rating']);

        $this->assertSame(0, Feedback::count());
    }

    public function test_feedback_submission_requires_a_student_token(): void
    {
        $this->postJson('/api/student/feedback', [
            'room_rating' => 4,
        ])->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/student/feedback', ['room_rating' => 4])
            ->assertForbidden();
    }
}