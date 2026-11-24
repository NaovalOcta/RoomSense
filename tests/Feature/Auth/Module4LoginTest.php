<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Module4LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_l1_01_invalid_input(): void
    {
        $this->post(route('login.post'), ['email' => 'abc', 'password' => ''])->assertSessionHasErrors(['email', 'password'])->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_l1_02_wrong_credentials(): void
    {
        $user = User::factory()->create();
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email')->assertSessionHas('_old_input.email', $user->email)->assertSessionMissing('_old_input.password');
        $this->assertGuest();
    }

    public function test_l1_03_borrower_remember_and_session_regeneration(): void
    {
        $user = User::factory()->create();
        $this->get(route('login'));
        $old = session()->getId();
        $response = $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password', 'remember' => 1]);
        $response->assertRedirect(route('dashboard'))->assertSessionHas('auth_session_version', $user->auth_session_version);
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($old, session()->getId());
        $this->assertNotNull($response->getCookie(auth()->guard()->getRecallerName()));
    }

    public function test_l1_04_admin_demo_and_intended_redirect(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->post(route('login.post'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('admin.bookings.index'));
        auth()->logout();
        $demo = User::factory()->create(['is_demo' => true]);
        $this->post(route('login.post'), ['email' => $demo->email, 'password' => 'password'])->assertRedirect(route('dashboard'))->assertSessionHas('warning');
        auth()->logout();
        session()->forget('warning');
        $user = User::factory()->create();
        $this->withSession(['url.intended' => url('/rooms')])->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(url('/rooms'));
        $this->assertAuthenticatedAs($user);
    }
}
