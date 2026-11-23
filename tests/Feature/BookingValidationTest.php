<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IsolatedMemoryTestCase;

class BookingValidationTest extends IsolatedMemoryTestCase
{
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        Notification::fake();
        $this->actingAs(User::factory()->create());
        $this->room = Room::create(['name' => 'Boundary', 'building' => 'A', 'capacity' => 50, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function cases(): array
    {
        return [
            'BVA-BKG-01' => ['2026-11-02', '08:00', '10:00', true],
            'BVA-BKG-02' => ['2026-11-03', '08:00', '10:00', true],
            'BVA-BKG-03 / EP-BKG-01' => ['2026-11-09', '08:00', '10:00', true],
            'BVA-BKG-04' => ['2026-11-15', '08:00', '10:00', true],
            'BVA-BKG-05' => ['2026-11-16', '08:00', '10:00', true],
            'BVA-BKG-06' => ['2026-11-09', '08:00', '08:30', true],
            'BVA-BKG-07' => ['2026-11-09', '08:00', '08:35', true],
            'BVA-BKG-08' => ['2026-11-09', '08:00', '11:55', true],
            'BVA-BKG-09' => ['2026-11-09', '08:00', '12:00', true],
            'EP-BKG-02' => ['2026-11-01', '08:00', '10:00', false],
            'EP-BKG-03' => ['2026-11-17', '08:00', '10:00', false],
            'EP-BKG-04' => ['', '08:00', '10:00', false],
            'EP-BKG-05' => ['2026-11-09X', '08:00', '10:00', false],
            'EP-BKG-06' => [['2026-11-09'], '08:00', '10:00', false],
            'EP-BKG-07' => ['2026-11-09', '08:00', '09:00', true],
            'EP-BKG-08' => ['2026-11-09', '08:00', '08:25', false],
            'EP-BKG-09' => ['2026-11-09', '08:00', '12:05', false],
            'unreal date' => ['2026-11-31', '08:00', '10:00', false],
            'before open' => ['2026-11-09', '06:55', '08:00', false],
            'after close' => ['2026-11-09', '20:30', '21:05', false],
            'off grid' => ['2026-11-09', '08:01', '09:00', false],
            'seconds rejected' => ['2026-11-09', '08:00:01', '09:00', false],
            'same time' => ['2026-11-09', '08:00', '08:00', false],
            'cross midnight' => ['2026-11-09', '20:00', '07:00', false],
        ];
    }

    #[DataProvider('cases')]
    public function test_booking_boundaries($date, $start, $end, bool $accepted): void
    {
        $response = $this->post(route('bookings.store'), ['room_id' => $this->room->id, 'date' => $date, 'start_time' => $start, 'end_time' => $end]);
        $accepted ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors();
        $this->assertSame($accepted ? 1 : 0, Booking::count());
        if ($accepted) {
            $this->assertSame(Carbon::parse("$date $start", 'Asia/Jakarta')->utc()->format('Y-m-d H:i:s'), Booking::first()->getRawOriginal('start_time'));
        }
    }

    public function test_same_instant_and_subsecond_snapshot(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $data = ['room_id' => $this->room->id, 'date' => '2026-11-02', 'start_time' => '08:00', 'end_time' => '08:30'];
        $this->post(route('bookings.store'), $data)->assertSessionHasNoErrors();
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00.001', 'Asia/Jakarta'));
        $this->post(route('bookings.store'), $data)->assertSessionHasErrors('start_time');
        $this->assertSame(1, Booking::count());
    }
}
