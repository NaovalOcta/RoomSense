<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingSubmittedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\IsolatedMemoryTestCase;

class Module3RegressionTest extends IsolatedMemoryTestCase
{
    private User $user;
    private Room $room;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->room = Room::create(['name' => 'Regression Room', 'capacity' => 30, 'is_active' => true]);
        $this->booking = Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => 'approved', 'purpose' => 'Regression booking']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_history_shows_the_users_booking(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertSee('Regression Room')->assertSee('Regression booking');
    }

    public function test_schedule_exposes_the_existing_contract(): void
    {
        $this->getJson(route('api.rooms.schedule', ['room' => $this->room, 'date' => '2026-11-03']))
            ->assertOk()->assertJsonPath('bookings.0.start', '08:00')->assertJsonPath('bookings.0.end', '09:00')->assertJsonPath('bookings.0.status', 'approved');
    }

    public function test_notification_snapshot_and_read_management(): void
    {
        $payload = (new BookingSubmittedNotification($this->booking))->toDatabase($this->user);
        $this->assertSame($this->booking->id, $payload['booking_id']);
        $this->assertNotEmpty($payload['booking_snapshot']['start_time']);
        $notice = $this->user->notifications()->create(['id' => (string) Str::uuid(), 'type' => BookingSubmittedNotification::class, 'data' => $payload]);
        $this->postJson(route('notifications.open', $notice->id))->assertOk()->assertJsonPath('count', 0);
        $this->assertNotNull($notice->fresh()->read_at);
    }
}
