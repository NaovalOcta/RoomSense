<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Database\Seeders\BookingSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoomSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\IsolatedMemoryTestCase;

class DatabaseSeederTest extends IsolatedMemoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-28 09:00:00', 'Asia/Jakarta'));
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_default_accounts_are_only_verified_admin_and_configured_demo(): void
    {
        config(['app.demo_user_email' => 'lecturer@example.test']);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $admin = User::where('email', 'admin@roomsense.com')->sole();
        $demo = User::where('email', 'lecturer@example.test')->sole();
        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
        $this->assertFalse($demo->isAdmin());
        $this->assertTrue((bool) $demo->is_demo);
        $this->assertNotNull($demo->email_verified_at);
        $this->assertDatabaseMissing('users', ['email' => 'bob@roomsense.com']);
        foreach ([$admin, $demo] as $user) {
            $this->assertFalse(Hash::check('password', $user->getRawOriginal('password')));
        }
        $this->assertDatabaseCount('notifications', 0);
        Notification::assertNothingSent();
    }

    public function test_demo_lifecycle_fixtures_have_valid_ownership_intervals_and_relationships(): void
    {
        $this->seed(DatabaseSeeder::class);
        $demo = User::where('email', config('app.demo_user_email'))->sole();
        $bookings = Booking::with(['user', 'room', 'cancelledBy'])->get();

        $this->assertCount(34, $bookings);
        $this->assertSame(20, $bookings->filter(fn ($b) => str_contains($b->purpose, '(Historical)'))->count());
        $this->assertEqualsCanonicalizing(['approved', 'pending', 'rejected', 'cancelled'], $bookings->pluck('status')->unique()->all());
        foreach ($bookings as $booking) {
            $this->assertSame($demo->id, $booking->user_id);
            $this->assertNotNull($booking->room);
            $this->assertFalse($booking->user->isAdmin());
            $this->assertNotNull($booking->user->email_verified_at);
            $start = $booking->start_wib;
            $end = $booking->end_wib;
            $this->assertSame($start->format('Y-m-d'), $end->format('Y-m-d'));
            $this->assertGreaterThanOrEqual('07:00', $start->format('H:i'));
            $this->assertLessThanOrEqual('21:00', $end->format('H:i'));
            $this->assertSame(0, (int) $start->format('i') % 5);
            $this->assertSame(0, (int) $end->format('i') % 5);
            $this->assertGreaterThanOrEqual(30, $start->diffInMinutes($end));
            $this->assertLessThanOrEqual(240, $start->diffInMinutes($end));
            $this->assertSame($start->utc()->format('Y-m-d H:i:s'), $booking->getRawOriginal('start_time'));
            if ($booking->status === Booking::STATUS_CANCELLED) {
                $this->assertSame($demo->id, $booking->cancelled_by);
                $this->assertNotNull($booking->cancelledBy);
                $this->assertNotNull($booking->cancelled_at);
            }
        }
        $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
    }

    public function test_booking_fixtures_are_reproducible_without_creating_additional_accounts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $fields = ['user_id', 'room_id', 'start_time', 'end_time', 'purpose', 'status', 'cancelled_by', 'cancelled_at', 'created_at'];
        $capture = fn ($query) => $query->get()->map(fn ($b) => array_intersect_key($b->getRawOriginal(), array_flip($fields)))->all();
        $first = $capture(Booking::orderBy('id'));
        $this->seed(BookingSeeder::class);
        $this->assertSame($first, $capture(Booking::orderBy('id')->skip(count($first))->take(count($first))));
        $this->assertDatabaseCount('users', 2);
    }

    public function test_booking_seeder_does_not_use_admin_or_unverified_borrower_as_demo(): void
    {
        User::factory()->create(['is_admin' => true]);
        User::factory()->unverified()->create(['email' => config('app.demo_user_email'), 'is_admin' => false]);
        $this->seed(RoomSeeder::class);
        $this->seed(BookingSeeder::class);
        $this->assertDatabaseCount('bookings', 0);
    }
}
