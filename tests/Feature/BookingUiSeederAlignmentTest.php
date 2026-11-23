<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\BookingSeeder;
use Illuminate\Support\Carbon;
use Tests\IsolatedMemoryTestCase;

class BookingUiSeederAlignmentTest extends IsolatedMemoryTestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_form_uses_jakarta_dates_grid_and_duration_guidance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-01 17:30:00', 'UTC'));
        $this->actingAs(User::factory()->create())->get(route('bookings.create'))->assertOk()
            ->assertSee('min="2026-11-02"', false)->assertSee('max="2026-11-16"', false)
            ->assertSee('step="300"', false)->assertSee('min="07:00"', false)->assertSee('max="21:00"', false)
            ->assertSee('30–240')->assertSee('WIB');
    }

    public function test_seed_intervals_are_utc_valid_and_reproducible(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);
        User::factory()->create(['is_admin' => false]);
        Room::create(['name' => 'A', 'capacity' => 10, 'is_active' => true]);
        Room::create(['name' => 'B', 'capacity' => 20, 'is_active' => true]);
        $this->seed(BookingSeeder::class);
        $first = Booking::orderBy('id')->get();
        $this->assertCount(34, $first);
        $this->assertSame('2026-11-03 02:00:00', $first->first()->getRawOriginal('start_time'));
        foreach ($first as $booking) {
            $start = Carbon::parse($booking->getRawOriginal('start_time'), 'UTC')->setTimezone('Asia/Jakarta');
            $end = Carbon::parse($booking->getRawOriginal('end_time'), 'UTC')->setTimezone('Asia/Jakarta');
            $this->assertSame($start->format('Y-m-d'), $end->format('Y-m-d'));
            $this->assertGreaterThanOrEqual('07:00', $start->format('H:i'));
            $this->assertLessThanOrEqual('21:00', $end->format('H:i'));
            $this->assertGreaterThanOrEqual(30, $start->diffInMinutes($end));
            $this->assertLessThanOrEqual(240, $start->diffInMinutes($end));
        }
        $fields = ['user_id', 'room_id', 'start_time', 'end_time', 'purpose', 'status', 'created_at', 'cancelled_at'];
        $expected = $first->map(fn ($booking) => array_intersect_key($booking->getRawOriginal(), array_flip($fields)))->toArray();
        $this->seed(BookingSeeder::class);
        $actual = Booking::orderBy('id')->skip(34)->take(34)->get()->map(fn ($booking) => array_intersect_key($booking->getRawOriginal(), array_flip($fields)))->toArray();
        $this->assertSame($expected, $actual);
    }
}
