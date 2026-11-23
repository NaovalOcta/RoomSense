<?php

namespace Tests\Integration;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingSubmittedNotification;
use Database\Seeders\BookingSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Support\DisposableMysql;

class MysqlBookingIntegrationTest extends TestCase
{
    private User $user;
    private Room $room;

    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';
        $this->traitsUsedByTest = class_uses_recursive(static::class);
        DisposableMysql::configure($app);
        $app->make(Kernel::class)->bootstrap();
        DisposableMysql::verify($app);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        // Test fixture independence, including after browser verification.
        // Only the verified owned disposable target is reachable; rollback restores its rows.
        foreach (['notifications', 'sessions', 'bookings', 'rooms', 'users'] as $table) {
            DB::table($table)->delete();
        }
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        Notification::fake();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->room = Room::create(['name' => 'Ruang 😀 é', 'building' => 'Timur', 'capacity' => 50, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        DB::statement("SET time_zone = '+00:00'");
        DB::rollBack();
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function input(array $override = []): array
    {
        return ['room_id' => $this->room->id, 'date' => '2026-11-03', 'start_time' => '08:00', 'end_time' => '10:00', ...$override];
    }

    public function test_raw_utc_history_schedule_notification_and_admin_round_trip(): void
    {
        $this->post(route('bookings.store'), $this->input())->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertSame('2026-11-03 01:00:00', DB::table('bookings')->value('start_time'));
        $this->assertSame('2026-11-03 03:00:00', DB::table('bookings')->value('end_time'));
        $this->assertSame('2026-11-03 08:00', $booking->start_wib->format('Y-m-d H:i'));
        $this->get(route('dashboard'))->assertOk()->assertSee('8:00 AM')->assertSee('10:00 AM');
        $this->getJson(route('api.rooms.schedule', ['room' => $this->room, 'date' => '2026-11-03']))->assertOk()->assertJsonPath('bookings.0.start', '08:00')->assertJsonPath('bookings.0.end', '10:00');
        $notice = new BookingSubmittedNotification($booking);
        $payload = $notice->toDatabase($this->user);
        $this->assertSame('2026-11-03T01:00:00+00:00', $payload['booking_snapshot']['start_time']);
        $stored = $this->user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => $notice::class, 'data' => $payload]);
        $this->get(route('notifications.show', $stored->id))->assertOk()->assertSee('03 Nov 2026 08:00');
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->patch(route('admin.bookings.status', $booking), ['status' => 'approved'])->assertSessionHasNoErrors();
        $this->get(route('admin.bookings.index'))->assertOk()->assertSee('8:00 AM');
    }

    public function test_timestamp_session_behavior_is_distinct_from_datetime(): void
    {
        $this->post(route('bookings.store'), $this->input())->assertSessionHasNoErrors();
        $this->assertSame('+00:00', DB::selectOne('SELECT @@session.time_zone AS tz')->tz);
        $before = DB::selectOne('SELECT start_time, created_at, UNIX_TIMESTAMP(created_at) AS epoch FROM bookings LIMIT 1');
        DB::statement("SET time_zone = '+07:00'");
        $after = DB::selectOne('SELECT start_time, created_at, UNIX_TIMESTAMP(created_at) AS epoch FROM bookings LIMIT 1');
        $this->assertSame($before->start_time, $after->start_time);
        $this->assertSame((int) $before->epoch, (int) $after->epoch);
        $this->assertSame(Carbon::parse($before->created_at, 'UTC')->addHours(7)->format('Y-m-d H:i:s'), $after->created_at);
        $type = DB::selectOne("SELECT DATA_TYPE AS type FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'created_at'")->type;
        $this->assertSame('timestamp', $type);
    }

    public function test_strict_validation_date_duration_grid_and_microseconds(): void
    {
        foreach ([['date' => '2026-11-17'], ['date' => '2026-11-31'], ['date' => ['2026-11-03']], ['start_time' => '08:00:01'], ['end_time' => '08:25'], ['end_time' => '12:05'], ['start_time' => '06:55'], ['start_time' => '08:01']] as $invalid) {
            $this->postJson(route('bookings.store'), $this->input($invalid))->assertUnprocessable();
        }
        Carbon::setTestNow(Carbon::parse('2026-11-03 08:00:00.000001', 'Asia/Jakarta'));
        $this->postJson(route('bookings.store'), $this->input())->assertUnprocessable()->assertJsonValidationErrors('start_time');
        $this->assertSame(0, Booking::count());
    }

    public function test_conflicts_statuses_touching_and_admin_recheck(): void
    {
        $this->post(route('bookings.store'), $this->input())->assertSessionHasNoErrors();
        $first = Booking::firstOrFail();
        $this->post(route('bookings.store'), $this->input(['start_time' => '09:00', 'end_time' => '11:00']))->assertSessionHas('warning');
        $second = Booking::orderByDesc('id')->firstOrFail();
        $first->update(['status' => 'approved']);
        $this->post(route('bookings.store'), $this->input())->assertSessionHasErrors('room_id');
        $this->post(route('bookings.store'), $this->input(['start_time' => '10:00', 'end_time' => '11:00']))->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->patch(route('admin.bookings.status', $second), ['status' => 'approved'])->assertSessionHas('error');
        $this->assertSame('pending', $second->fresh()->status);
        $first->update(['status' => 'rejected']); $second->update(['status' => 'cancelled']);
        $this->actingAs($this->user)->post(route('bookings.store'), $this->input())->assertSessionHasNoErrors();
    }

    public function test_unicode_search_capacity_and_invalid_input(): void
    {
        Room::create(['name' => 'Inactive 😀', 'capacity' => 100, 'building' => 'Timur', 'is_active' => false]);
        $this->get(route('rooms.index', ['search' => "\u{00a0}😀\u{2003}", 'capacity' => 50]))->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->count() === 1 && $rooms->first()->id === $this->room->id);
        $this->get(route('rooms.index', ['search' => 'Timur', 'capacity' => 100]))->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->isEmpty());
        $this->getJson(route('rooms.index', ['search' => str_repeat('😀', 101)]))->assertUnprocessable();
        $this->getJson(route('rooms.index', ['search' => ['bad']]))->assertUnprocessable();
    }

    public function test_local_day_overlap_and_current_status(): void
    {
        $booking = Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-01 16:30:00', 'end_time' => '2026-11-01 17:30:00', 'status' => 'approved']);
        $this->getJson(route('api.rooms.schedule', ['room' => $this->room, 'date' => '2026-11-02']))->assertOk()->assertJsonCount(1, 'bookings')->assertJsonPath('bookings.0.end', '00:30');
        Carbon::setTestNow(Carbon::parse('2026-11-02 00:00:00', 'Asia/Jakarta'));
        $this->assertSame('booked', $this->room->fresh()->current_status);
        $this->assertSame('booked', $this->room->fresh()->load('bookings')->current_status);
        $this->get(route('rooms.show', $this->room))->assertOk()->assertViewHas('todayBookings', fn ($bookings) => $bookings->count() === 1);
    }

    public function test_seeder_utc_and_valid_intervals(): void
    {
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);
        Room::create(['name' => 'Seed B', 'capacity' => 20, 'is_active' => true]);
        $this->seed(BookingSeeder::class);
        $this->assertSame(34, Booking::count());
        $this->assertSame('2026-11-03 02:00:00', Booking::orderBy('id')->first()->getRawOriginal('start_time'));
        foreach (Booking::all() as $booking) {
            $this->assertGreaterThanOrEqual(30, $booking->start_utc->diffInMinutes($booking->end_utc));
            $this->assertLessThanOrEqual(240, $booking->start_utc->diffInMinutes($booking->end_utc));
            $this->assertSame($booking->start_wib->format('Y-m-d'), $booking->end_wib->format('Y-m-d'));
        }
    }
}
