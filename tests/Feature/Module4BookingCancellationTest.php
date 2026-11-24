<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\IsolatedMemoryTestCase;

class Module4BookingCancellationTest extends IsolatedMemoryTestCase
{
    private User $owner;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-02 01:00:00', 'UTC'));
        $this->owner = User::factory()->create();
        $room = Room::create(['name' => 'Synthetic room', 'capacity' => 20, 'is_active' => true]);
        $this->booking = Booking::create(['user_id' => $this->owner->id, 'room_id' => $room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => 'pending']);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function test_b7_01_foreign_booking_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->delete(route('bookings.destroy', $this->booking))->assertForbidden();
        $this->assertSame('pending', $this->booking->fresh()->status);
        $this->assertNull($this->booking->fresh()->cancelled_by);
        $this->assertSame(0, $this->owner->notifications()->count());
    }

    public function test_b7_02_non_pending_booking_unchanged(): void
    {
        $this->booking->update(['status' => 'approved']);
        $this->actingAs($this->owner)->delete(route('bookings.destroy', $this->booking))->assertSessionHas('error');
        $this->assertSame('approved', $this->booking->fresh()->status);
        $this->assertNull($this->booking->fresh()->cancelled_at);
        $this->assertSame(0, $this->owner->notifications()->count());
    }

    public function test_b7_03_owned_pending_booking_is_cancelled_and_retained(): void
    {
        $this->actingAs($this->owner)->delete(route('bookings.destroy', $this->booking))->assertSessionHas('success');
        $booking = $this->booking->fresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame($this->owner->id, $booking->cancelled_by);
        $this->assertSame('2026-11-02 01:00:00', $booking->getRawOriginal('cancelled_at'));
        $this->assertDatabaseHas('bookings', ['id' => $this->booking->id]);
    }

    public function test_b7_04_notification_failure_prevents_status_update(): void
    {
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('Synthetic mail failure'));
        Mail::mailer()->setSymfonyTransport($transport);

        $this->actingAs($this->owner)->delete(route('bookings.destroy', $this->booking))->assertStatus(500);

        $booking = $this->booking->fresh();
        $this->assertSame('pending', $booking->status);
        $this->assertNull($booking->cancelled_at);
        $this->assertNull($booking->cancelled_by);
        $this->assertSame(0, $this->owner->notifications()->count());
    }

    public function test_b7_05_database_notification_is_persisted(): void
    {
        $this->actingAs($this->owner)->delete(route('bookings.destroy', $this->booking))->assertSessionHas('success');
        $notification = $this->owner->notifications()->sole();
        $this->assertSame(BookingCancelledNotification::class, $notification->type);
        $this->assertSame($this->booking->id, $notification->data['booking_id']);
        $this->assertSame('cancelled', $notification->data['event_status']);
        $this->assertSame('cancelled', $this->booking->fresh()->status);
    }
}
