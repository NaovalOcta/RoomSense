<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Event::fake([PasswordReset::class]);
    }

    private function token(User $user): string
    {
        $user->refresh();
        Password::broker('users')->sendResetLink(['email' => $user->email]);

        return Notification::sent($user, ResetPassword::class)->last()->token;
    }

    private function resetInput(User $user, string $token, string $password = 'replacement-password'): array
    {
        return ['email' => $user->email, 'token' => $token, 'password' => $password, 'password_confirmation' => $password];
    }

    private function sessions(User $user, ?User $other = null): void
    {
        foreach (['target-a' => $user->id, 'target-b' => $user->id, 'unrelated-guest' => null, 'other' => $other?->id] as $id => $userId) {
            DB::table('sessions')->insert(['id' => $id, 'user_id' => $userId, 'payload' => base64_encode('{}'), 'last_activity' => time()]);
        }
    }

    public function test_get_reset_form_is_read_only_escapes_input_and_handles_email_array(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $before = DB::table('password_reset_tokens')->first();
        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()->assertViewIs('auth.reset-password')->assertViewHas('email', $user->email)
            ->assertSee('name="_token"', false)->assertSee('autocomplete="new-password"', false);
        $this->assertTrue(DB::table('password_reset_tokens')->first() == $before);
        $this->get(route('password.reset', ['token' => 'invalid', 'email' => ['array']]))
            ->assertOk()->assertViewHas('email', '');
        $this->get(route('password.reset', ['token' => 'invalid', 'email' => '"><script>alert(1)</script>']))
            ->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_success_hashes_password_rotates_remember_revokes_only_target_sessions_consumes_token_and_emits_event(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $remember = $user->remember_token;
        $verified = $user->fresh()->email_verified_at;
        $this->sessions($user, $other);
        $token = $this->token($user);
        $eventObserved = false;
        Event::getFacadeRoot()->except([PasswordReset::class]);
        Event::listen(PasswordReset::class, function ($event) use ($user, &$eventObserved) {
            $this->assertSame($user->id, $event->user->id);
            // RefreshDatabase holds an outer test transaction; controller's inner transaction has ended.
            $this->assertSame(1, DB::transactionLevel());
            $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
            $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
            $eventObserved = true;
        });
        $input = $this->resetInput($user, $token) + ['user_id' => $other->id, 'is_admin' => true, 'email_verified_at' => now()];
        $this->post(route('password.update'), $input)->assertRedirect(route('login'))->assertSessionHas('status')
            ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        $user->refresh();
        $this->assertTrue(Hash::check($input['password'], $user->password));
        $this->assertFalse($user->password === $input['password']);
        $this->assertFalse($remember === $user->remember_token);
        $this->assertSame(60, strlen($user->remember_token));
        $this->assertTrue($verified->equalTo($user->email_verified_at));
        $this->assertFalse($user->is_admin);
        $this->assertTrue($eventObserved);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'other', 'user_id' => $other->id]);
        $this->assertDatabaseHas('sessions', ['id' => 'unrelated-guest', 'user_id' => null]);
        $this->assertGuest();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => $input['password']])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_same_password_rejection_preserves_everything_and_same_link_can_succeed(): void
    {
        $user = User::factory()->create();
        $this->sessions($user);
        $token = $this->token($user);
        $original = $user->getRawOriginal();
        $storedToken = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->post(route('password.update'), $this->resetInput($user, $token, 'password'))
            ->assertSessionHasErrors(['password' => 'Password baru harus berbeda dari password saat ini.']);
        $user->refresh();
        $this->assertTrue($original === $user->getRawOriginal());
        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertTrue($storedToken == DB::table('password_reset_tokens')->where('email', $user->email)->first());
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
        Event::assertNotDispatched(PasswordReset::class);
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertRedirect(route('login'));
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_invalid_expired_and_mismatched_tokens_do_not_change_account_or_sessions(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->sessions($user, $other);
        $token = $this->token($user);
        $original = $user->getRawOriginal();
        $otherOriginal = $other->refresh()->getRawOriginal();
        $this->post(route('password.update'), $this->resetInput($user, 'invalid'))->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $this->post(route('password.update'), $this->resetInput($other, $token))->assertSessionHasErrors(['email' => $error]);
        $this->travel(61)->minutes();
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertSessionHasErrors(['email' => $error]);
        $this->assertTrue($original === $user->fresh()->getRawOriginal());
        $this->assertTrue($otherOriginal === $other->fresh()->getRawOriginal());
        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_input_rules_preserve_token_and_do_not_flash_passwords(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $original = $user->getRawOriginal();
        foreach ([['password' => '1234567', 'password_confirmation' => '1234567'], ['password_confirmation' => 'mismatch'], ['password' => ['array']], ['email' => ['array']], ['token' => ['array']], ['token' => '']] as $changes) {
            $this->post(route('password.update'), array_replace($this->resetInput($user, $token), $changes))
                ->assertSessionHasErrors()->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        }
        $this->assertTrue($original === $user->fresh()->getRawOriginal());
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_consumed_token_cannot_be_used_again(): void
    {
        $user = User::factory()->create();
        $token = $this->token($user);
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertRedirect(route('login'));
        $original = $user->fresh()->getRawOriginal();
        $this->post(route('password.update'), $this->resetInput($user, $token, 'another-password'))->assertSessionHasErrors('email');
        $this->assertTrue($original === $user->fresh()->getRawOriginal());
        Event::assertDispatchedTimes(PasswordReset::class, 1);
    }

    public function test_unverified_user_remains_unverified_and_password_is_not_trimmed(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'student@example.com']);
        $data = $this->resetInput($user, $this->token($user), '  replacement-password  ');
        $data['email'] = '  STUDENT@EXAMPLE.COM  ';
        $this->post(route('password.update'), $data)->assertRedirect(route('login'));
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertTrue(Hash::check($data['password'], $user->fresh()->password));
        $this->post(route('login.post'), ['email' => $user->email, 'password' => $data['password']])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    }

    public function test_admin_resets_through_same_flow_and_role_is_preserved(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'admin@example.com', 'is_admin' => true]);
        $this->post(route('password.update'), $this->resetInput($user, $this->token($user)) + ['is_admin' => false])->assertRedirect(route('login'));
        $this->assertTrue($user->fresh()->is_admin);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertGuest();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'replacement-password'])->assertRedirect(route('admin.bookings.index'));
    }

    public function test_session_delete_failure_rolls_back_all_mutations_and_does_not_emit_event(): void
    {
        $user = User::factory()->create();
        $this->sessions($user);
        $token = $this->token($user);
        $original = $user->getRawOriginal();
        // StartSession already resolves the original handler before entering the controller.
        $this->app['session']->driver();
        $this->app['events']->listen('eloquent.saved: '.User::class, function () {
            config(['session.table' => 'missing_sessions_table']);
        });
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue($original === $user->fresh()->getRawOriginal());
        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
        Event::assertNotDispatched(PasswordReset::class);
    }

    private function browser(array $cookies = []): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->forgetInstance('auth.driver');
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        $this->app['redirect']->setSession($this->app['session']->driver());
        $this->app['cookie']->flushQueuedCookies();
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
        $this->withCookies($cookies);
    }

    public function test_broker_token_deletion_failure_also_rolls_back_password_remember_and_deleted_sessions(): void
    {
        $user = User::factory()->create();
        $this->sessions($user);
        $token = $this->token($user);
        $original = $user->getRawOriginal();
        DB::connection()->beforeExecuting(function ($query) {
            if (str_starts_with($query, 'delete from `password_reset_tokens`')) {
                throw new QueryException('mysql', 'Simulated token deletion failure', [], new \RuntimeException('Simulated database failure'));
            }
        });
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertSessionHasErrors('email');
        $this->assertTrue($original === $user->fresh()->getRawOriginal());
        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertTrue(Password::broker('users')->tokenExists($user, $token));
        Event::assertNotDispatched(PasswordReset::class);
    }

    public function test_real_session_and_remember_cookies_fail_after_reset_while_other_browser_survives(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $sessionName = config('session.cookie');
        $rememberName = $this->app['auth']->guard()->getRecallerName();
        $a = $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password']);
        $cookieA = [$sessionName => $a->getCookie($sessionName)->getValue()];
        $this->browser();
        $b = $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password', 'remember' => true]);
        $cookieB = [$sessionName => $b->getCookie($sessionName)->getValue(), $rememberName => $b->getCookie($rememberName)->getValue()];
        $this->browser();
        $d = $this->post(route('login.post'), ['email' => $other->email, 'password' => 'password']);
        $cookieD = [$sessionName => $d->getCookie($sessionName)->getValue()];
        $this->assertDatabaseHas('sessions', ['id' => $cookieD[$sessionName], 'user_id' => $other->id]);
        $this->browser();
        $token = $this->token($user);
        $this->post(route('password.update'), $this->resetInput($user, $token, 'password'))->assertSessionHasErrors('password');
        foreach ([$cookieA, $cookieB] as $cookies) {
            $this->browser($cookies);
            $this->get(route('dashboard'))->assertOk();
            $this->assertAuthenticatedAs($user);
        }
        $this->browser();
        $this->post(route('password.update'), $this->resetInput($user, $token))->assertRedirect(route('login'));
        $this->assertGuest();
        foreach ([$cookieA, $cookieB, [$rememberName => $cookieB[$rememberName]]] as $cookies) {
            $this->browser($cookies);
            $this->get(route('dashboard'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
        $this->browser($cookieD);
        $this->get(route('dashboard'))->assertOk();
        $this->assertAuthenticatedAs($other);
    }
}
