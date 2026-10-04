<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_guest_pages_and_csrf_forms_exist(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Lupa Password?');
        $this->get(route('password.request'))->assertOk()
            ->assertViewIs('auth.forgot-password')->assertSee('name="_token"', false)
            ->assertSee('Spam/Junk')->assertSee('60 detik');
    }

    public function test_registered_unknown_and_cooldown_requests_have_same_public_response(): void
    {
        $user = User::factory()->create();
        $sent = $this->post(route('password.email'), ['email' => $user->email]);
        $status = session('status');
        $sent->assertRedirect(route('password.request'))->assertSessionHas('status');
        $cooldown = $this->post(route('password.email'), ['email' => $user->email]);
        $cooldown->assertRedirect(route('password.request'))->assertSessionHas('status', $status);
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))->assertSessionHas('status', $status);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        Notification::assertCount(1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'unknown@example.com']);
        $this->assertGuest();
    }

    public function test_email_is_normalized_and_admin_non_campus_email_is_accepted(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true]);
        $this->post(route('password.email'), ['email' => '  ADMIN@EXAMPLE.COM  '])
            ->assertSessionHasNoErrors()->assertSessionHas('_old_input.email', $user->email);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_invalid_email_inputs_are_rejected_without_side_effects(): void
    {
        foreach (['invalid', ['email@example.com'], '', str_repeat('a', 250).'@example.com'] as $email) {
            $this->post(route('password.email'), ['email' => $email, 'password' => 'never-flash', 'token' => 'never-flash'])
                ->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.token');
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_token_is_hashed_and_default_notification_url_is_valid(): void
    {
        $user = User::factory()->create(['email' => 'recovery+tag@example.com']);
        $this->post(route('password.email'), ['email' => $user->email]);
        $notification = Notification::sent($user, ResetPassword::class)->first();
        $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertTrue($stored !== $notification->token);
        $this->assertTrue(Hash::check($notification->token, $stored));
        $url = $notification->toMail($user)->actionUrl;
        $parts = parse_url($url);
        parse_str($parts['query'], $query);
        $this->assertTrue($parts['path'] === '/reset-password/'.$notification->token);
        $this->assertSame($user->email, $query['email']);
        $this->assertStringContainsString('email=recovery%2Btag%40example.com', $url);
        $this->assertSame('http', $parts['scheme']);
        $this->assertSame('127.0.0.1', $parts['host']);
        $this->assertSame(8000, $parts['port']);
        $this->get($url)->assertOk()->assertViewIs('auth.reset-password');
    }

    public function test_broker_cooldown_and_resend_replace_old_token(): void
    {
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email]);
        $first = Notification::sent($user, ResetPassword::class)->first()->token;
        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->travel(61)->seconds();
        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertSentToTimes($user, ResetPassword::class, 2);
        $latest = Notification::sent($user, ResetPassword::class)->last()->token;
        $this->assertFalse(Password::broker('users')->tokenExists($user, $first));
        $this->assertTrue(Password::broker('users')->tokenExists($user, $latest));
        $data = ['email' => $user->email, 'password' => 'different-password', 'password_confirmation' => 'different-password'];
        $this->post(route('password.update'), $data + ['token' => $first])->assertSessionHasErrors('email');
        $this->post(route('password.update'), $data + ['token' => $latest])->assertRedirect(route('login'));
    }

    public function test_http_throttle_six_then_429_recovers_and_reset_bucket_is_separate(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertStatus(429);
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.update'), [])->assertRedirect()->assertSessionHasErrors('token');
        }
        $this->post(route('password.update'), [])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertRedirect();
        $this->post(route('password.update'), [])->assertRedirect();
    }

    public function test_smtp_failure_keeps_generic_response_and_only_logs_safe_context(): void
    {
        Notification::swap($this->app->make(ChannelManager::class));
        // The only mocked boundary is notification delivery, after the real broker creates a token.
        Notification::shouldReceive('send')->once()->andThrow(new TransportException('sensitive-transport-detail'));
        Log::shouldReceive('warning')->once()->with('Password reset email delivery failed.', ['category' => 'smtp_transport']);
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))->assertSessionHasNoErrors();
        $status = session('status');
        $this->assertStringNotContainsString('sensitive-transport-detail', $status);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHas('status', $status);
    }

    public function test_authenticated_user_is_redirected_from_all_four_guest_routes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get(route('password.request'))->assertRedirect(route('dashboard'));
        $this->get(route('password.reset', ['token' => 'invalid']))->assertRedirect(route('dashboard'));
        $this->post(route('password.email'), [])->assertRedirect(route('dashboard'));
        $this->post(route('password.update'), [])->assertRedirect(route('dashboard'));
        Notification::assertNothingSent();
    }
}
