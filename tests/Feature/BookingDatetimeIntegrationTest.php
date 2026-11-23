<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingSubmittedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\IsolatedMemoryTestCase;

class BookingDatetimeIntegrationTest extends IsolatedMemoryTestCase
{
    private User $user;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 00:30:00', 'Asia/Jakarta'));
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->room = Room::create(['name' => 'WIB Room', 'capacity' => 30, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function booking(string $start = '2026-11-02 01:00:00', string $end = '2026-11-02 03:00:00'): Booking
    {
        return Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => $start, 'end_time' => $end, 'status' => 'approved']);
    }

    public function test_history_admin_and_notification_details_render_wib(): void
    {
        $booking = $this->booking();
        $this->get(route('dashboard'))->assertOk()->assertSee('8:00 AM')->assertSee('10:00 AM');
        $notice = $this->user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => BookingSubmittedNotification::class, 'data' => (new BookingSubmittedNotification($booking))->toDatabase($this->user)]);
        $this->get(route('notifications.show', $notice->id))->assertOk()->assertSee('02 Nov 2026 08:00')->assertSee('02 Nov 2026 10:00');
        $this->actingAs(User::factory()->create(['is_admin' => true]))->get(route('admin.bookings.index'))->assertOk()->assertSee('8:00 AM')->assertSee('10:00 AM');
    }

    public function test_local_day_schedule_uses_utc_overlap_boundaries(): void
    {
        $this->booking('2026-11-01 16:30:00', '2026-11-01 17:30:00'); // crosses local midnight
        $this->booking('2026-11-02 16:30:00', '2026-11-02 17:00:00');
        $this->booking('2026-11-01 16:00:00', '2026-11-01 17:00:00'); // ends exactly at day start
        $this->booking('2026-11-02 17:00:00', '2026-11-02 18:00:00'); // starts exactly at next day
        $this->getJson(route('api.rooms.schedule', ['room' => $this->room, 'date' => '2026-11-02']))->assertOk()->assertJsonCount(2, 'bookings')->assertJsonPath('bookings.0.start', '23:30')->assertJsonPath('bookings.0.end', '00:30');
        $this->get(route('rooms.show', $this->room))->assertOk()->assertSee('Nov 2, 2026')->assertViewHas('todayBookings', fn ($bookings) => $bookings->count() === 2);
    }

    public function test_new_snapshot_is_explicit_utc_but_email_is_wib(): void
    {
        $booking = $this->booking();
        $notice = new BookingSubmittedNotification($booking);
        $snapshot = $notice->toDatabase($this->user)['booking_snapshot'];
        $this->assertSame('2026-11-02T01:00:00+00:00', $snapshot['start_time']);
        $this->assertSame('2026-11-02T03:00:00+00:00', $snapshot['end_time']);
        $this->assertStringContainsString('08:00 - 10:00 (WIB)', implode(' ', $notice->toMail($this->user)->introLines));
        $this->assertSame('2026-11-02 01:00:00', $booking->getRawOriginal('start_time'));
    }

    public function test_room_status_in_loaded_and_query_paths_uses_utc_instants(): void
    {
        $this->booking();
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $this->assertSame('booked', $this->room->fresh()->current_status);
        $this->assertSame('booked', $this->room->fresh()->load('bookings')->current_status);
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00:00', 'Asia/Jakarta'));
        $this->assertSame('available', $this->room->fresh()->current_status);
    }

    public function test_jakarta_date_boundary_and_one_microsecond_past_start(): void
    {
        Notification::fake();
        $data = ['room_id' => $this->room->id, 'date' => '2026-11-16', 'start_time' => '07:00', 'end_time' => '07:30'];
        $this->post(route('bookings.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['start_time' => '2026-11-16 00:00:00', 'end_time' => '2026-11-16 00:30:00']);
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00.000001', 'Asia/Jakarta'));
        $data['date'] = '2026-11-02';
        $data['start_time'] = '08:00';
        $data['end_time'] = '08:30';
        $this->post(route('bookings.store'), $data)->assertSessionHasErrors('start_time');
        $this->assertSame(1, Booking::count());
    }
}
