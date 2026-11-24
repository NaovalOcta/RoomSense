<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\IsolatedMemoryTestCase;

class Module4BookingHistoryTest extends IsolatedMemoryTestCase
{
    private User $user;

    private User $other;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $this->user = User::factory()->create();
        $this->other = User::factory()->create();
        $this->room = Room::create(['name' => 'Synthetic room', 'capacity' => 20, 'is_active' => true]);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function bookingFor(User $user, int $sequence): Booking
    {
        $booking = Booking::create(['user_id' => $user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => 'pending']);
        $booking->created_at = Carbon::parse('2026-11-01 00:00:00', 'UTC')->addMinutes($sequence);
        $booking->save();

        return $booking;
    }

    public function test_h1_01_empty_owned_history_order_and_pagination(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertViewHas('bookings', fn ($items) => $items->isEmpty());
        $ids = [];
        for ($i = 0; $i < 11; $i++) {
            $ids[] = $this->bookingFor($this->user, $i)->id;
        }
        $foreign = $this->bookingFor($this->other, 20);
        $this->get(route('dashboard'))->assertOk()->assertViewHas('bookings', function ($items) use ($ids, $foreign) {
            return $items->total() === 11 && $items->count() === 10 && $items->first()->id === $ids[10] && ! $items->contains('id', $foreign->id);
        });
        $this->get(route('dashboard', ['page' => 2]))->assertOk()->assertViewHas('bookings', fn ($items) => $items->count() === 1 && $items->first()->id === $ids[0]);
    }

    public function test_h1_02_owned_reference_filters_history(): void
    {
        $target = $this->bookingFor($this->user, 1);
        $this->bookingFor($this->user, 2);
        $this->get(route('dashboard', ['booking' => $target->id]))->assertOk()->assertViewHas('bookings', fn ($items) => $items->total() === 1 && $items->first()->id === $target->id);
    }

    public function test_h1_03_foreign_nonexistent_and_invalid_references(): void
    {
        $foreign = $this->bookingFor($this->other, 1);
        $this->get(route('dashboard', ['booking' => $foreign->id]))->assertNotFound();
        $this->get(route('dashboard', ['booking' => 999999]))->assertNotFound();
        $this->get(route('dashboard', ['booking' => 'invalid']))->assertSessionHasErrors('booking');
    }
}
