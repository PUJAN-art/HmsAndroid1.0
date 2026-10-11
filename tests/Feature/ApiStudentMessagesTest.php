<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiStudentMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_list_their_inbox_and_unread_count(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStudent = User::factory()->create(['role' => 'student']);

        $message = $this->createMessage($admin, $student, ['subject' => 'Important notice']);
        $this->createMessage($otherStudent, User::factory()->create(['role' => 'student']));

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/student/messages/inbox')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id', $message->id)
            ->assertJsonPath('data.0.subject', 'Important notice')
            ->assertJsonPath('data.0.sender.id', $admin->id)
            ->assertJsonPath('pagination.total', 1);

        $this->getJson('/api/student/messages/unread-count')
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_opening_a_received_message_marks_it_read_and_returns_replies(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $message = $this->createMessage($admin, $student);
        $reply = $this->createMessage($student, $admin, [
            'subject' => 'Re: Message subject',
            'reply_to_id' => $message->id,
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson("/api/student/messages/{$message->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $message->id)
            ->assertJsonPath('data.is_read', true)
            ->assertJsonPath('data.replies.0.id', $reply->id)
            ->assertJsonPath('data.replies.0.sender.id', $student->id);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'is_read' => true,
        ]);
    }

    public function test_student_can_reply_to_a_received_message_with_web_side_effects(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $message = $this->createMessage($admin, $student);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/student/messages/{$message->id}/reply", [
                'message' => 'Thank you for the update.',
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.from_user_id', $student->id)
            ->assertJsonPath('data.to_user_id', $admin->id)
            ->assertJsonPath('data.subject', 'Re: Message subject')
            ->assertJsonPath('data.reply_to_id', $message->id);

        $this->assertDatabaseHas('messages', [
            'from_user_id' => $student->id,
            'to_user_id' => $admin->id,
            'reply_to_id' => $message->id,
            'message' => 'Thank you for the update.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'type' => 'message_reply_received',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $student->id,
            'action' => 'send_reply',
            'module' => 'Message',
        ]);
    }

    public function test_student_cannot_read_or_reply_to_messages_they_do_not_receive_or_send(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'admin']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $message = $this->createMessage($admin, $otherStudent);

        $this->actingAs($student, 'sanctum')
            ->getJson("/api/student/messages/{$message->id}")
            ->assertForbidden();

        $this->postJson("/api/student/messages/{$message->id}/reply", [
            'message' => 'Unauthorized reply.',
        ])->assertForbidden();

        $this->assertSame(0, Message::where('from_user_id', $student->id)->count());
    }

    public function test_message_api_requires_student_authentication_and_valid_reply_body(): void
    {
        $this->getJson('/api/student/messages/inbox')->assertUnauthorized();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/student/messages/inbox')
            ->assertForbidden();

        $student = User::factory()->create(['role' => 'student']);
        $message = $this->createMessage($admin, $student);

        $this->actingAs($student, 'sanctum')
            ->postJson("/api/student/messages/{$message->id}/reply", ['message' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('message');

        $this->assertSame(0, Notification::count());
    }

    private function createMessage(User $sender, User $recipient, array $attributes = []): Message
    {
        return Message::create(array_merge([
            'from_user_id' => $sender->id,
            'to_user_id' => $recipient->id,
            'subject' => 'Message subject',
            'message' => 'Message body.',
        ], $attributes));
    }
}
