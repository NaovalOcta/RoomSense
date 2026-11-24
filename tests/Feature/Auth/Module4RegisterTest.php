<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class Module4RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function input(array $changes = []): array
    {
        return array_replace(['name' => 'Student', 'email' => 'student@webmail.umm.ac.id', 'password' => 'password', 'password_confirmation' => 'password'], $changes);
    }

    private function requireDns(string $domain): void
    {
        if (! @dns_get_record($domain, DNS_MX | DNS_A | DNS_AAAA)) {
            $this->markTestSkipped("DNS unavailable for $domain; production validation is unchanged.");
        }
    }

    public function test_r1_01_invalid_fields_do_not_create_user(): void
    {
        $this->post(route('register.store'), $this->input(['name' => '', 'email' => 'bad', 'password' => '1234567', 'password_confirmation' => 'wrong']))->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_r1_02_non_campus_domain_is_rejected(): void
    {
        $this->requireDns('example.com');
        $this->post(route('register.store'), $this->input(['email' => 'lab@example.com']))->assertSessionHasErrors(['email' => 'Email wajib menggunakan domain @webmail.umm.ac.id']);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_r1_03_registration_normalizes_and_sends_verification(): void
    {
        $this->requireDns('webmail.umm.ac.id');
        Notification::fake();
        $this->post(route('register.store'), $this->input(['email' => 'STUDENT@webmail.umm.ac.id', 'is_admin' => true]))->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));
        $user = User::where('email', 'student@webmail.umm.ac.id')->firstOrFail();
        $this->assertDatabaseCount('users', 1);
        $this->assertFalse($user->is_admin);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_r1_04_duplicate_email_does_not_create_user(): void
    {
        $this->requireDns('webmail.umm.ac.id');
        User::factory()->create(['email' => 'student@webmail.umm.ac.id']);
        $this->post(route('register.store'), $this->input())->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }
}
