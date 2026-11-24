<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class Module4PasswordStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_p1_01_invalid_email_has_no_token(): void
    {
        Notification::fake();
        $this->post(route('password.email'), ['email' => ['bad']])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_p1_02_normalization_generic_response_token_and_cooldown(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'student@example.com']);
        $this->post(route('password.email'), ['email' => '  STUDENT@EXAMPLE.COM  '])->assertSessionHas('_old_input.email', $user->email)->assertSessionHas('status');
        $status = session('status');
        $token = Notification::sent($user, ResetPassword::class)->first()->token;
        $this->assertTrue(Hash::check($token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token')));
        $this->post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHas('status', $status);
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', $status);
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->assertDatabaseCount('password_reset_tokens', 1);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'unknown@example.com']);
    }

    public function test_p1_03_transport_failure_has_generic_response_and_safe_log(): void
    {
        $user = User::factory()->create();
        Notification::shouldReceive('send')->andThrow(new TransportException('synthetic transport failure'));
        Log::shouldReceive('warning')->once()->with('Password reset email delivery failed.', ['category' => 'smtp_transport']);
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect(route('password.request'))->assertSessionHas('status', 'Jika email tersebut terdaftar, tautan reset password akan dikirim. Periksa folder Spam/Junk dan tunggu setidaknya 60 detik sebelum mencoba lagi.');
    }
}
