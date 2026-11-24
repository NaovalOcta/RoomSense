<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingApprovedNotification;
use App\Notifications\BookingRejectedNotification;
use Illuminate\Support\Carbon;
use Tests\IsolatedMemoryTestCase;

class Module4AdminBookingStatusTest extends IsolatedMemoryTestCase
{
    private User $borrower;

    private User $admin;

    private Room $room;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $this->borrower = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->room = Room::create(['name' => 'Synthetic room', 'capacity' => 20, 'is_active' => true]);
        $this->booking = $this->bookingFor('pending');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function bookingFor(string $status): Booking
    {
        return Booking::create(['user_id' => $this->borrower->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => $status]);
    }

    public function test_b6_01_validation_and_authorization(): void
    {
        $this->actingAs($this->borrower)->patch(route('admin.bookings.status', $this->booking), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($this->admin)->patch(route('admin.bookings.status', $this->booking), ['status' => 'cancelled', 'notes' => str_repeat('x', 501)])->assertSessionHasErrors(['status', 'notes']);
        $this->assertSame('pending', $this->booking->fresh()->status);
    }

    public function test_b6_02_approved_conflict_blocks_approval(): void
    {
        $this->bookingFor('approved');
        $this->actingAs($this->admin)->patch(route('admin.bookings.status', $this->booking), ['status' => 'approved'])->assertSessionHas('error');
        $this->assertSame('pending', $this->booking->fresh()->status);
        $this->assertSame(0, $this->borrower->notifications()->count());
    }

    public function test_b6_03_approve_with_notes(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.bookings.status', $this->booking), ['status' => 'approved', 'notes' => 'Approved synthetic'])->assertSessionHas('success');
        $this->assertSame('approved', $this->booking->fresh()->status);
        $this->assertSame('Approved synthetic', $this->booking->fresh()->notes);
    }

    public function test_b6_04_reject_with_notes(): void
    {
        $this->actingAs($this->admin)->patch(route('admin.bookings.status', $this->booking), ['status' => 'rejected', 'notes' => 'Rejected synthetic'])->assertSessionHas('success');
        $this->assertSame('rejected', $this->booking->fresh()->status);
        $this->assertSame('Rejected synthetic', $this->booking->fresh()->notes);
    }

    public function test_b6_05_persists_both_status_notifications(): void
    {
        $other = $this->bookingFor('pending');
        $this->actingAs($this->admin)->patch(route('admin.bookings.status', $this->booking), ['status' => 'approved'])->assertSessionHas('success');
        $this->patch(route('admin.bookings.status', $other), ['status' => 'rejected', 'notes' => 'Synthetic reason'])->assertSessionHas('success');
        $notifications = $this->borrower->notifications()->get()->keyBy('type');
        $this->assertCount(2, $notifications);
        $this->assertSame($this->booking->id, $notifications[BookingApprovedNotification::class]->data['booking_id']);
        $this->assertSame($other->id, $notifications[BookingRejectedNotification::class]->data['booking_id']);
        $this->assertSame('Synthetic reason', $notifications[BookingRejectedNotification::class]->data['booking_snapshot']['rejection_reason']);
    }
}
