<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Module4PasswordEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_p2_01_string_email_and_token_are_preserved(): void
    {
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => 'lab@example.com']))
            ->assertOk()->assertViewIs('auth.reset-password')->assertViewHas('token', 'sample-token')->assertViewHas('email', 'lab@example.com');
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }

    public function test_p2_02_non_string_email_is_safely_cleared(): void
    {
        $this->get(route('password.reset', ['token' => 'sample-token', 'email' => ['malformed']]))
            ->assertOk()->assertViewHas('token', 'sample-token')->assertViewHas('email', '');
        $this->assertSame(0, DB::table('password_reset_tokens')->count());
    }
}
