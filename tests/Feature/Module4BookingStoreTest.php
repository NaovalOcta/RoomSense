<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\AdminNewBookingNotification;
use App\Notifications\BookingSubmittedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Tests\IsolatedMemoryTestCase;

class Module4BookingStoreTest extends IsolatedMemoryTestCase
{
    private User $user;

    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-11-02 08:00:00', 'Asia/Jakarta'));
        $this->user = User::factory()->create();
        $this->room = Room::create(['name' => 'Synthetic room', 'capacity' => 20, 'is_active' => true]);
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['room_id' => $this->room->id, 'date' => '2026-11-03', 'start_time' => '08:00', 'end_time' => '09:00', 'purpose' => 'Synthetic'], $overrides);
    }

    public static function invalidCases(): array
    {
        return [
            'B2-01 invalid fields' => [['room_id' => 999999, 'date' => 'bad'], 'room_id'],
            'B2-01 invalid slot' => [['start_time' => '08:03'], 'start_time'],
            'B2-01 oversized purpose' => [['purpose' => str_repeat('x', 256)], 'purpose'],
            'B2-02 before window' => [['date' => '2026-11-01'], 'date'],
            'B2-02 after window' => [['date' => '2026-11-17'], 'date'],
            'B2-03 before opening' => [['start_time' => '06:55'], 'start_time'],
            'B2-03 after closing' => [['start_time' => '20:30', 'end_time' => '21:05'], 'start_time'],
            'B2-04 too short' => [['end_time' => '08:25'], 'end_time'],
            'B2-04 too long' => [['end_time' => '12:05'], 'end_time'],
            'B2-04 reversed interval' => [['start_time' => '09:00', 'end_time' => '08:00'], 'end_time'],
            'B2-05 start in past' => [['date' => '2026-11-02', 'start_time' => '07:00', 'end_time' => '08:00'], 'start_time'],
        ];
    }

    #[DataProvider('invalidCases')]
    public function test_invalid_booking_cases(array $overrides, string $field): void
    {
        $response = $this->post(route('bookings.store'), $this->payload($overrides));
        $response->assertSessionHasErrors($field);
        $this->assertSame(0, Booking::count());
    }

    private function existing(string $status): Booking
    {
        return Booking::create(['user_id' => $this->user->id, 'room_id' => $this->room->id, 'start_time' => '2026-11-03 01:00:00', 'end_time' => '2026-11-03 02:00:00', 'status' => $status]);
    }

    public function test_b2_06_approved_conflict_suggests_active_alternative(): void
    {
        $this->existing('approved');
        $alternative = Room::create(['name' => 'Alternative', 'capacity' => 20, 'is_active' => true]);
        $response = $this->post(route('bookings.store'), $this->payload());
        $response->assertSessionHasErrors('room_id')->assertSessionHas('suggestions', fn ($items) => collect($items)->contains('id', $alternative->id));
        $this->assertSame(1, Booking::count());
    }

    public function test_b2_07_pending_overlap_is_created_with_warning(): void
    {
        $this->existing('pending');
        $this->post(route('bookings.store'), $this->payload())->assertSessionHas('warning');
        $this->assertSame(2, Booking::count());
    }

    public function test_b2_08_success_persists_utc_and_pending_status(): void
    {
        $payload = $this->payload(['date' => '2026-11-16', 'start_time' => '17:00', 'end_time' => '21:00']);
        unset($payload['purpose']);

        $this->post(route('bookings.store'), $payload)
            ->assertRedirect(route('dashboard'))->assertSessionHasNoErrors()->assertSessionMissing('warning');

        $booking = Booking::sole();
        $this->assertSame('pending', $booking->status);
        $this->assertNull($booking->purpose);
        $this->assertSame('2026-11-16 10:00:00', $booking->getRawOriginal('start_time'));
        $this->assertSame('2026-11-16 14:00:00', $booking->getRawOriginal('end_time'));
    }

    public function test_b2_09_inactive_room_must_be_rejected(): void
    {
        $this->room->update(['is_active' => false]);
        $response = $this->post(route('bookings.store'), $this->payload());
        $this->assertSame(0, Booking::count(), 'Inactive rooms must not accept new bookings.');
        $response->assertSessionHasErrors('room_id');
    }

    public function test_b2_10_notification_failure_occurs_after_creation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $transport = Mockery::mock(TransportInterface::class);
        $transport->shouldReceive('send')->once()->andThrow(new TransportException('Synthetic mail failure'));
        Mail::mailer()->setSymfonyTransport($transport);

        $this->post(route('bookings.store'), $this->payload())->assertStatus(500);

        $this->assertSame('pending', Booking::sole()->status);
        $this->assertSame(0, $this->user->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_b2_11_database_notifications_are_persisted(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->post(route('bookings.store'), $this->payload())->assertSessionHasNoErrors();
        $booking = Booking::sole();
        $userNotification = $this->user->notifications()->sole();
        $adminNotification = $admin->notifications()->sole();
        $this->assertSame(BookingSubmittedNotification::class, $userNotification->type);
        $this->assertSame(AdminNewBookingNotification::class, $adminNotification->type);
        $this->assertSame($booking->id, $userNotification->data['booking_id']);
        $this->assertSame($booking->id, $adminNotification->data['booking_id']);
    }
}
