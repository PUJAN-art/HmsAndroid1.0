<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ApiNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_filtered_notifications_and_get_recent_items(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $otherUser = User::factory()->create(['role' => 'student']);

        $unread = $this->createNotification($user, [
            'type' => 'message',
            'title' => 'New message',
            'data' => ['message_id' => 10],
        ]);
        $this->createNotification($user, [
            'type' => 'notice',
            'title' => 'Old notice',
            'read_at' => now()->subMinute(),
            'created_at' => now()->subMinute(),
        ]);
        $this->createNotification($otherUser, ['title' => 'Private notification']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?status=unread&type=message')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.id', $unread->id)
            ->assertJsonPath('data.0.data.message_id', 10)
            ->assertJsonPath('data.0.read_at', null)
            ->assertJsonPath('pagination.total', 1)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonMissing(['title' => 'Private notification']);

        $this->getJson('/api/notifications/recent')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('notifications.0.id', $unread->id)
            ->assertJsonMissing(['title' => 'Private notification']);
    }

    public function test_user_can_mark_one_or_all_notifications_read_and_unread_count_updates(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $notification = $this->createNotification($user);
        $this->createNotification($user, ['type' => 'notice']);
        $otherNotification = $this->createNotification(
            User::factory()->create(['role' => 'student'])
        );

        Cache::put("user:{$user->id}:unread_notification_count", 99, 30);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertSame(1, $this->getJson('/api/notifications/unread-count')->assertOk()->json('unread_count'));

        $this->postJson('/api/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->assertSame(0, $this->getJson('/api/notifications/unread-count')->json('unread_count'));
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_user_can_delete_only_their_own_notification(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $otherNotification = $this->createNotification(
            User::factory()->create(['role' => 'student'])
        );
        $ownNotification = $this->createNotification($user);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/notifications/{$ownNotification->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('notifications', ['id' => $ownNotification->id]);

        $this->deleteJson("/api/notifications/{$otherNotification->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $otherNotification->id]);
    }

    public function test_notification_api_requires_authentication_and_valid_filters(): void
    {
        $this->getJson('/api/notifications')->assertUnauthorized();

        $user = User::factory()->create(['role' => 'student']);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/notifications?status=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    private function createNotification(User $user, array $attributes = []): Notification
    {
        return Notification::create(array_merge([
            'user_id' => $user->id,
            'type' => 'info',
            'title' => 'Notification title',
            'message' => 'Notification body.',
        ], $attributes));
    }
}
