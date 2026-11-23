<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IsolatedMemoryTestCase;

class BookingStrictInputTest extends IsolatedMemoryTestCase
{
    public static function malformed(): array
    {
        return [
            'date whitespace' => ['date', ' 2026-11-03 '],
            'time whitespace' => ['start_time', ' 08:00 '],
            'zero seconds still not HH:mm' => ['start_time', '08:00:00'],
            'fractional seconds' => ['end_time', '09:00:00.001'],
            'time array' => ['start_time', ['08:00']],
            'time numeric' => ['end_time', 900],
            'time boolean' => ['start_time', true],
            'date number' => ['date', 20261103],
            'invalid hour' => ['start_time', '24:00'],
            'invalid minute' => ['end_time', '09:60'],
        ];
    }

    #[DataProvider('malformed')]
    public function test_malformed_json_is_validation_error_without_insert(string $field, mixed $value): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        try {
            Notification::fake();
            $this->actingAs(User::factory()->create());
            $room = Room::create(['name' => 'Strict', 'capacity' => 20, 'is_active' => true]);
            $data = ['room_id' => $room->id, 'date' => '2026-11-03', 'start_time' => '08:00', 'end_time' => '09:00'];
            $data[$field] = $value;
            $this->postJson(route('bookings.store'), $data)->assertUnprocessable()->assertJsonValidationErrors($field);
            $this->assertSame(0, Booking::count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
