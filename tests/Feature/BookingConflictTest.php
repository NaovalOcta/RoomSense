<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\IsolatedMemoryTestCase;

class BookingConflictTest extends IsolatedMemoryTestCase
{
    private Room $room;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 06:00:00', 'Asia/Jakarta'));
        Notification::fake();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->room = Room::create(['name' => 'Conflict', 'capacity' => 50, 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public static function statuses(): array
    {
        return ['approved' => ['approved'], 'pending' => ['pending'], 'rejected' => ['rejected'], 'cancelled' => ['cancelled']];
    }

    #[DataProvider('statuses')]
    public function test_overlapping_booking_statuses(string $status): void
    {
        Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => $status]);
        $response = $this->post(route('bookings.store'), ['room_id' => $this->room->id, 'date' => '2026-11-03', 'start_time' => '08:30', 'end_time' => '09:30']);
        if ($status === 'approved') {
            $response->assertSessionHasErrors('room_id');
            $this->assertSame(1, Booking::count());
        } else {
            $response->assertSessionHasNoErrors();
            $this->assertSame(2, Booking::count());
            if ($status === 'pending') {
                $response->assertSessionHas('warning');
            }
        }
    }

    public function test_touching_is_not_overlap_and_approval_rechecks(): void
    {
        $approved = Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => 'approved']);
        $this->assertFalse($this->room->hasConflict('2026-11-03 02:00:00', '2026-11-03 03:00:00', null, ['approved']));
        $pending = Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:30:00', 'end_time' => '2026-11-03 02:30:00', 'status' => 'pending']);
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->patch(route('admin.bookings.status', $pending), ['status' => 'approved'])->assertSessionHas('error');
        $this->assertSame('pending', $pending->fresh()->status);
        $this->assertSame('approved', $approved->fresh()->status);
    }
}

