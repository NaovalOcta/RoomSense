<?php

namespace Tests\Feature\Auth;

use App\Events\RealtimeNotificationEvent;
use App\Models\Room;
use App\Models\User;
use App\Notifications\AdminNewBookingNotification;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingSubmittedNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class AuthenticationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_testing_configuration_and_mysql_account_are_isolated(): void
    {
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', config('database.default'));
        $this->assertSame('room_sense_testing', DB::selectOne('SELECT DATABASE() AS db')->db);
        $this->assertSame('database', config('session.driver'));
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertNull(config('broadcasting.default'));
        try {
            DB::select('SELECT COUNT(*) FROM `room-sense`.`users`');
            $this->fail('Testing account must not read the development database.');
        } catch (QueryException $exception) {
            $this->assertSame(1142, $exception->errorInfo[1]);
        }
    }

    public function test_bootstrap_guard_rejects_all_three_unsafe_conditions_before_refresh_database(): void
    {
        // These subprocesses call createApplication only; they never set up RefreshDatabase.
        $code = <<<'PHP'
        require 'vendor/autoload.php';
        try {
            (new Tests\Feature\Auth\AuthenticationRegressionTest('probe'))->createApplication();
            echo 'Guard unexpectedly accepted unsafe configuration';
            exit(1);
        } catch (RuntimeException $exception) {
            echo $exception->getMessage();
        }
        PHP;
        foreach ([
            [['APP_ENV' => 'local'], 'require the testing environment'],
            [['DB_CONNECTION' => 'sqlite'], 'require the mysql connection'],
            [['DB_DATABASE' => 'information_schema'], 'require room_sense_testing'],
        ] as [$environment, $message]) {
            $process = new Process([PHP_BINARY, '-r', $code], base_path(), $environment);
            $process->setTimeout(30);
            $process->run();
            $this->assertTrue($process->isSuccessful(), 'Guard probe must exit cleanly after rejection.');
            $this->assertStringContainsString($message, $process->getOutput());
        }
    }

    public function test_login_bad_credentials_and_successful_user_login_regenerate_session(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'))->assertOk();
        $oldSession = session()->getId();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($oldSession === session()->getId());
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_admin_login_and_access_remain_available(): void
    {
        $admin = User::factory()->unverified()->create(['is_admin' => true]);
        $this->post(route('login.post'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.bookings.index'));
        $this->get(route('admin.bookings.index'))->assertOk();
        $this->get(route('rooms.index'))->assertOk();
    }

    public function test_logout_and_remember_me_wiring_remain_available(): void
    {
        $user = User::factory()->create();
        $login = $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password', 'remember' => true]);
        $rememberName = $this->app['auth']->guard()->getRecallerName();
        $this->assertTrue($login->getCookie($rememberName) !== null);
        $oldSession = session()->getId();
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertFalse($oldSession === session()->getId());
    }

    public function test_auth_verified_and_admin_middleware_remain_enforced(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk();
        $this->get(route('admin.bookings.index'))->assertForbidden();
    }

    public function test_register_domain_and_password_validation_are_preserved(): void
    {
        $this->get(route('register'))->assertOk();
        $this->post(route('register.store'), ['name' => 'Student', 'email' => 'student@example.com', 'password' => '1234567', 'password_confirmation' => 'mismatch'])
            ->assertSessionHasErrors(['email', 'password']);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_campus_registration_emits_registered_and_sends_verification(): void
    {
        $records = @dns_get_record('webmail.umm.ac.id', DNS_MX | DNS_A | DNS_AAAA);
        if (! $records) {
            $this->markTestSkipped('Campus email DNS unavailable; production email:rfc,dns is unchanged.');
        }
        $registered = false;
        Event::listen(Registered::class, function () use (&$registered) {
            $registered = true;
        });
        $email = 'recovery-regression@webmail.umm.ac.id';
        $this->post(route('register.store'), ['name' => 'Student', 'email' => $email, 'password' => 'password', 'password_confirmation' => 'password', 'is_admin' => true])
            ->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));
        $user = User::where('email', $email)->firstOrFail();
        $this->assertFalse($user->is_admin);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($registered);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_signed_email_verification_and_resend_work(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
        Event::fake([Verified::class]);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_unsigned_and_wrong_user_verification_are_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->get($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertNull($other->fresh()->email_verified_at);
    }

    public function test_room_booking_cancellation_and_notification_wiring_remain_available(): void
    {
        Event::fake([RealtimeNotificationEvent::class]);
        $user = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $room = Room::create(['name' => 'Recovery Regression Room', 'capacity' => 20, 'building' => 'GKB', 'is_active' => true]);
        $this->actingAs($user)->get(route('rooms.index', ['search' => 'Recovery']))->assertOk()->assertSee($room->name);
        $this->get(route('rooms.show', $room))->assertOk();
        $this->post(route('bookings.store'), ['room_id' => $room->id, 'date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00', 'purpose' => 'Regression'])
            ->assertRedirect(route('dashboard'));
        $booking = $user->bookings()->firstOrFail();
        $this->assertSame('pending', $booking->status);
        Notification::assertSentTo($user, BookingSubmittedNotification::class);
        Notification::assertSentTo($admin, AdminNewBookingNotification::class);
        Event::assertDispatched(RealtimeNotificationEvent::class);
        $this->get(route('notifications.index'))->assertOk();
        $this->delete(route('bookings.destroy', $booking))->assertRedirect();
        $this->assertSame('cancelled', $booking->fresh()->status);
        Notification::assertSentTo($user, BookingCancelledNotification::class);
    }
}
