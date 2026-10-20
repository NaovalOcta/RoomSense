<?php

namespace Tests\Feature;

use App\Events\RealtimeNotificationEvent;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\AdminNewBookingNotification;
use App\Notifications\BookingApprovedNotification;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingCompletedNotification;
use App\Notifications\BookingRejectedNotification;
use App\Notifications\BookingReminderNotification;
use App\Notifications\BookingSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDetailTest extends TestCase
{
    use RefreshDatabase;

    private function booking(User $user, array $attributes = []): Booking
    {
        $room = Room::create(['name' => 'Notification Room', 'capacity' => 25, 'building' => 'GKB', 'is_active' => true]);

        return Booking::create(['user_id' => $user->id, 'room_id' => $room->id, 'start_time' => now()->addDay()->setTime(9, 0), 'end_time' => now()->addDay()->setTime(10, 0), 'purpose' => 'Detail regression', 'status' => 'pending', ...$attributes]);
    }

    private function notice(User $user, array $data = [], string $type = BookingSubmittedNotification::class)
    {
        return $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => $type, 'data' => ['title' => 'Booking event', 'message' => 'Full event message', ...$data]]);
    }

    public function test_owned_detail_has_full_message_correct_booking_and_safe_destination(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $notice = $this->notice($user, ['booking_id' => $booking->id, 'event_status' => 'pending', 'action_url' => 'javascript:alert(1)']);
        $this->actingAs($user)->get(route('notifications.show', $notice->id))->assertOk()->assertSee('Full event message')->assertSee('Notification Room')->assertSee('Current booking #'.$booking->id)->assertSee(route('dashboard', ['booking' => $booking->id]))->assertDontSee('javascript:alert');
        $this->assertNull($notice->fresh()->read_at);
        $response = $this->postJson(route('notifications.open', $notice->id))->assertOk()->assertJsonPath('count', 0);
        $this->assertStringContainsString('View Booking', $response->json('html'));
        $this->assertStringContainsString($booking->start_time->format('d M Y H:i'), $response->json('html'));
        $this->assertStringContainsString($booking->end_time->format('d M Y H:i'), $response->json('html'));
        $this->assertNotNull($notice->fresh()->read_at);
        $this->get(route('dashboard', ['booking' => $booking->id]))->assertOk()->assertSee('Detail regression');
    }

    public function test_read_is_idempotent_and_other_users_unread_count_is_unchanged(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notice = $this->notice($user);
        $this->notice($user);
        $otherNotice = $this->notice($other);
        $this->actingAs($user)->postJson(route('notifications.open', $notice->id))->assertJsonPath('count', 1);
        $readAt = $notice->fresh()->read_at;
        $this->travel(2)->minutes();
        $this->postJson(route('notifications.open', $notice->id))->assertJsonPath('count', 1);
        $this->assertEquals($readAt, $notice->fresh()->read_at);
        $this->getJson(route('notifications.unread-count'))->assertJsonPath('count', 1);
        $this->assertNull($otherNotice->fresh()->read_at);
    }

    public function test_unauthorized_notification_requests_never_mark_read_and_guest_is_rejected(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $notice = $this->notice($other);
        $this->postJson(route('notifications.open', $notice->id))->assertUnauthorized();
        $this->actingAs($user)->get(route('notifications.show', $notice->id))->assertNotFound();
        $this->postJson(route('notifications.open', $notice->id))->assertNotFound();
        $this->patchJson(route('notifications.read', $notice->id))->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);
        // Same numeric id with a different polymorphic notifiable type must also be rejected.
        $notice->notifiable_id = $user->id;
        $notice->notifiable_type = Room::class;
        $notice->save();
        $this->postJson(route('notifications.open', $notice->id))->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);
    }

    public function test_unverified_user_cannot_open_or_consume_notification(): void
    {
        $user = User::factory()->unverified()->create();
        $notice = $this->notice($user);
        $this->actingAs($user)->postJson(route('notifications.open', $notice->id))->assertForbidden();
        $this->assertNull($notice->fresh()->read_at);
    }

    public function test_missing_unrelated_legacy_and_unauthorized_booking_payloads_have_graceful_fallbacks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otherBooking = $this->booking($other, ['notes' => 'Private rejection note', 'status' => 'rejected']);
        $this->actingAs($user);
        foreach ([999999, $otherBooking->id, 'not-an-id'] as $bookingId) {
            $notice = $this->notice($user, ['booking_id' => $bookingId]);
            $response = $this->postJson(route('notifications.open', $notice->id))->assertOk();
            $this->assertStringContainsString('Booking tidak tersedia', $response->json('html'));
            $this->assertStringNotContainsString('View Booking', $response->json('html'));
            $this->assertStringNotContainsString('Private rejection note', $response->json('html'));
        }
        foreach (['SystemNotice', BookingSubmittedNotification::class] as $type) {
            $notice = $this->notice($user, ['message' => 'Legacy or system detail'], $type);
            $this->get(route('notifications.show', $notice->id))->assertOk()->assertSee('Legacy or system detail')->assertDontSee('View Booking');
        }
        $this->get(route('dashboard', ['booking' => $otherBooking->id]))->assertNotFound();
        $this->get(route('dashboard', ['booking' => 999999]))->assertNotFound();
    }

    public function test_historical_event_and_current_status_are_distinct_and_html_is_escaped(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user, ['status' => 'rejected', 'notes' => '<script>currentReason()</script>']);
        $notice = $this->notice($user, ['title' => '<script>titleAttack()</script>', 'message' => '<img src=x onerror=alert(1)>', 'booking_id' => $booking->id, 'event_status' => 'pending']);
        $this->actingAs($user);
        $response = $this->postJson(route('notifications.open', $notice->id))->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('Original notification event:', $html);
        $this->assertStringContainsString('pending', $html);
        $this->assertStringContainsString('Current status', $html);
        $this->assertStringContainsString('rejected', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->get(route('notifications.index'))->assertOk()->assertDontSee('<script>titleAttack()', false);
    }

    public function test_all_existing_booking_notifications_save_reference_and_original_event_snapshot(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user, ['notes' => 'Original reason']);
        foreach ([new BookingSubmittedNotification($booking), new BookingApprovedNotification($booking), new BookingRejectedNotification($booking), new BookingCancelledNotification($booking), new BookingCompletedNotification($booking), new BookingReminderNotification($booking, 'H-24'), new AdminNewBookingNotification($booking)] as $notification) {
            $payload = $notification->toDatabase($user);
            $this->assertSame($booking->id, $payload['booking_id']);
            $this->assertSame($booking->room->name, $payload['booking_snapshot']['room_name']);
            $this->assertArrayHasKey('event_status', $payload);
            $this->assertNotEmpty($payload['message']);
        }
        $data = (new BookingRejectedNotification($booking))->toDatabase($user);
        $notice = $this->notice($user, $data);
        $booking->update(['status' => 'approved', 'notes' => null]);
        $this->actingAs($user)->get(route('notifications.show', $notice->id))->assertSee('Original reason')->assertSee('approved')->assertSee('rejected');
    }

    public function test_admin_owned_notification_links_to_existing_approval_page_and_regular_user_is_denied(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $admin = User::factory()->unverified()->create(['is_admin' => true]);
        $notice = $this->notice($admin, (new AdminNewBookingNotification($booking))->toDatabase($admin));
        $this->actingAs($admin)->get(route('notifications.show', $notice->id))->assertOk()->assertSee(route('admin.bookings.index', ['booking' => $booking->id]));
        $this->get(route('admin.bookings.index', ['booking' => $booking->id]))->assertOk()->assertSee($user->name);
        $this->actingAs($user)->get(route('admin.bookings.index', ['booking' => $booking->id]))->assertForbidden();
        $this->postJson(route('notifications.open', $notice->id))->assertNotFound();
    }

    public function test_recent_list_and_mark_all_return_persisted_ids_and_owned_counts(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $one = $this->notice($user);
        $two = $this->notice($user);
        $otherNotice = $this->notice($other);
        $this->actingAs($user)->getJson(route('notifications.recent'))->assertOk()->assertJsonPath('count', 2)->assertJsonCount(2, 'notifications');
        $this->patchJson(route('notifications.read', $one->id))->assertOk()->assertJsonPath('count', 1);
        $this->patchJson(route('notifications.read-all'))->assertOk()->assertJsonPath('count', 0);
        $this->assertNotNull($two->fresh()->read_at);
        $this->assertNull($otherNotice->fresh()->read_at);
        $this->get(route('notifications.index', ['filter' => 'unread']))->assertOk()->assertSee('No notifications');
        $this->get(route('notifications.index', ['filter' => 'read']))->assertOk()->assertSee('Full event message');
    }

    public function test_failed_detail_render_does_not_mark_as_read(): void
    {
        $user = User::factory()->create();
        $notice = $this->notice($user);
        View::composer('notifications._detail', function () {
            throw new \RuntimeException('Simulated render failure');
        });
        $this->actingAs($user)->postJson(route('notifications.open', $notice->id))->assertStatus(500);
        $this->assertNull($notice->fresh()->read_at);
    }

    public function test_admin_approval_rejection_booking_history_search_and_notification_delivery_regressions(): void
    {
        Notification::fake();
        Event::fake([RealtimeNotificationEvent::class]);
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $approved = $this->booking($user);
        $rejected = $this->booking($user, ['start_time' => now()->addDays(2)->setTime(9, 0), 'end_time' => now()->addDays(2)->setTime(10, 0)]);
        $this->actingAs($admin)->patch(route('admin.bookings.status', $approved), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->patch(route('admin.bookings.status', $rejected), ['status' => 'rejected', 'notes' => 'Maintenance'])->assertSessionHasNoErrors();
        Notification::assertSentTo($user, BookingApprovedNotification::class);
        Notification::assertSentTo($user, BookingRejectedNotification::class);
        Event::assertDispatchedTimes(RealtimeNotificationEvent::class, 2);
        $this->get(route('admin.bookings.index', ['status' => 'approved']))->assertOk();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Maintenance');
        $this->get(route('rooms.index', ['search' => 'Notification', 'capacity' => 20]))->assertOk()->assertSee('Notification Room');
        $this->delete(route('bookings.destroy', $approved))->assertSessionHas('error');
        $this->assertSame('approved', $approved->fresh()->status);
    }
}
