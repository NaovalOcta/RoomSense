<?php

namespace Tests\Feature\Module4;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\IsolatedMemoryTestCase;

class N8NotificationDetailsTest extends IsolatedMemoryTestCase
{
    private function booking(User $owner): Booking
    {
        $room = Room::create(['name' => 'Private Room', 'capacity' => 20, 'building' => 'GKB', 'is_active' => true]);

        return Booking::create(['user_id' => $owner->id, 'room_id' => $room->id, 'start_time' => now()->addDay(), 'end_time' => now()->addDay()->addHour(), 'purpose' => 'Private purpose', 'status' => 'pending']);
    }

    private function notice(User $owner, array $data = [])
    {
        return $owner->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'TestNotice', 'data' => ['title' => 'Notice', 'message' => 'Synthetic message', ...$data]]);
    }

    /** N8-01 */
    public function test_n8_01_owned_reference_uses_safe_dashboard_link(): void
    {
        $user = User::factory()->create();
        $booking = $this->booking($user);
        $notice = $this->notice($user, ['booking_id' => (string) $booking->id, 'action_url' => 'javascript:alert(1)']);
        $this->actingAs($user)->get(route('notifications.show', $notice->id))->assertOk()->assertSee(route('dashboard', ['booking' => $booking->id]))->assertDontSee('javascript:alert');
        $this->assertNull($notice->fresh()->read_at);
    }

    /** N8-02 */
    public function test_n8_02_admin_reference_uses_admin_link(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $booking = $this->booking($user);
        $notice = $this->notice($admin, ['booking_id' => $booking->id]);
        $this->actingAs($admin)->get(route('notifications.show', $notice->id))->assertOk()->assertSee(route('admin.bookings.index', ['booking' => $booking->id]));
    }

    /** N8-03 */
    public function test_n8_03_missing_invalid_foreign_and_legacy_references(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $booking = $this->booking($other);
        $this->actingAs($user);
        foreach ([['booking_id' => $booking->id], ['booking_id' => 999999], ['booking_id' => 'invalid'], []] as $data) {
            $notice = $this->notice($user, $data);
            $this->get(route('notifications.show', $notice->id))->assertOk()->assertSee('Synthetic message')->assertDontSee('Private purpose')->assertDontSee('View Booking');
        }
    }

    /** N8-04 */
    public function test_n8_04_foreign_notice_is_not_accessible(): void
    {
        $user = User::factory()->create();
        $notice = $this->notice(User::factory()->create());
        $this->actingAs($user)->get(route('notifications.show', $notice->id))->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);
    }
}
