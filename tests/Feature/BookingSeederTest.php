<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\BookingSeeder;
use Tests\IsolatedMemoryTestCase;

class BookingSeederTest extends IsolatedMemoryTestCase
{
    public function test_historical_start_is_not_mutated_by_audit_dates(): void
    {
        User::factory()->create(['is_admin' => true]);
        User::factory()->create(['is_admin' => false]);
        User::factory()->create(['is_admin' => false]);
        Room::create(['name' => 'A', 'capacity' => 10, 'is_active' => true]);
        Room::create(['name' => 'B', 'capacity' => 20, 'is_active' => true]);
        $this->seed(BookingSeeder::class);
        $history = Booking::where('purpose', 'like', '%(Historical)')->get();
        $this->assertCount(20, $history);
        foreach ($history as $booking) {
            $seconds = $booking->end_time->getTimestamp() - $booking->start_time->getTimestamp();
            $this->assertGreaterThanOrEqual(3600, $seconds);
            $this->assertLessThanOrEqual(10800, $seconds);
        }
    }
}
