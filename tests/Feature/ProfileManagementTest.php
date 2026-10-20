<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PendingEmailVerification;
use Illuminate\Database\QueryException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function account(array $attributes = []): User
    {
        return User::factory()->create(['email' => 'student@webmail.umm.ac.id', ...$attributes]);
    }

    private function emailInput(string $email = 'new@webmail.umm.ac.id'): array
    {
        return ['email' => $email, 'current_password' => 'password'];
    }

    private function verificationUrl(): string
    {
        $url = null;
        Notification::assertSentOnDemand(PendingEmailVerification::class, function ($notification, $channels, $notifiable) use (&$url) {
            $this->assertSame(['mail'], $channels);
            $this->assertSame('new@webmail.umm.ac.id', $notifiable->routes['mail']);
            $url = $notification->toMail($notifiable)->actionUrl;

            return true;
        });

        return $url;
    }

    public function test_own_profile_and_admin_profile_are_accessible_and_guest_is_redirected(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $user = $this->account();
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee($user->email)->assertSee('Profile Settings')->assertDontSee($user->password);
        $admin = User::factory()->unverified()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('profile.edit'))->assertOk()->assertSee('Administrator');
    }

    public function test_name_phone_normalization_and_blank_phone_persist_on_own_account(): void
    {
        $user = $this->account();
        $other = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), ['name' => '  New Name  ', 'phone' => '+62 (812) 1234-5678'])->assertSessionHasNoErrors();
        $this->assertSame('New Name', $user->fresh()->name);
        $this->assertSame('+6281212345678', $user->fresh()->phone);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->patch(route('profile.update'), ['name' => str_repeat('N', 255), 'phone' => ''])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->phone);
    }

    public function test_invalid_profile_and_privileged_fields_are_rejected(): void
    {
        $user = $this->account();
        $this->actingAs($user);
        foreach (['', '   ', str_repeat('x', 256), ['invalid']] as $name) {
            $this->patch(route('profile.update'), ['name' => $name])->assertSessionHasErrors('name');
        }
        foreach (['letters', '12345', str_repeat('1', 31)] as $phone) {
            $this->patch(route('profile.update'), ['name' => 'Valid', 'phone' => $phone])->assertSessionHasErrors('phone');
        }
        foreach (['id', 'user_id', 'is_admin', 'role', 'email'] as $field) {
            $this->patch(route('profile.update'), ['name' => 'Valid', $field => '1'])->assertSessionHasErrors($field);
        }
        $this->assertSame($user->name, $user->fresh()->name);
        $this->assertFalse($user->fresh()->is_admin);
        $this->patch('/profile/'.$user->id, ['name' => 'Wrong endpoint'])->assertNotFound();
    }

    public function test_upload_replace_remove_and_private_avatar_delivery(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('photo.png')])->assertSessionHasNoErrors();
        $old = $user->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($old);
        $this->get(route('profile.photo'))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('replacement.jpg')])->assertSessionHasNoErrors();
        $new = $user->fresh()->profile_photo_path;
        $this->assertNotSame($old, $new);
        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($new);
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('profile.photo'))->assertNotFound();
        $this->delete(route('profile.photo.destroy'))->assertSessionHasNoErrors();
        Storage::disk('local')->assertExists($new);
        $this->actingAs($user)->delete(route('profile.photo.destroy'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($new);
        $this->get(route('profile.photo'))->assertNotFound();
        $this->get(route('profile.edit'))->assertSee('Avatar default');
    }

    public function test_photo_size_mime_svg_and_missing_upload_are_rejected(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('large.png')->size(2049)])->assertSessionHasErrors('photo');
        foreach ([['photo.jpg', '<?php echo "executable";'], ['photo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>']] as [$name, $content]) {
            $fake = UploadedFile::fake()->createWithContent($name, $content);
            $file = new UploadedFile($fake->getPathname(), $name, null, null, true);
            $this->post(route('profile.photo.update'), ['photo' => $file])->assertSessionHasErrors('photo');
        }
        $this->post(route('profile.photo.update'))->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('boundary.png')->size(2048)])->assertSessionHasNoErrors();
    }

    public function test_photo_database_failure_preserves_old_file_and_cleans_new_upload(): void
    {
        $user = $this->account();
        $user->profile_photo_path = 'profile-photos/'.$user->id.'/old.png';
        $user->save();
        Storage::disk('local')->put($user->profile_photo_path, 'old');
        DB::connection()->beforeExecuting(function ($query) {
            if (str_starts_with($query, 'update `users`')) {
                throw new QueryException('mysql', 'Simulated photo failure', [], new \RuntimeException('Database unavailable'));
            }
        });
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('new.png')])->assertSessionHasErrors('photo');
        $this->assertSame($user->profile_photo_path, $user->fresh()->profile_photo_path);
        $this->assertSame([$user->profile_photo_path], Storage::disk('local')->allFiles());
    }

    public function test_storage_write_failure_is_reported_without_changing_active_photo(): void
    {
        $user = $this->account();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('photo.png')])->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_cleanup_failure_keeps_committed_photo_and_reports_warning(): void
    {
        $user = $this->account();
        $user->profile_photo_path = 'profile-photos/'.$user->id.'/old.png';
        $user->save();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn('profile-photos/'.$user->id.'/new.png');
        $disk->shouldReceive('delete')->with($user->profile_photo_path)->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('photo.png')])->assertSessionHas('warning')->assertSessionHasNoErrors();
        $this->assertSame('profile-photos/'.$user->id.'/new.png', $user->fresh()->profile_photo_path);
    }

    public function test_email_is_pending_until_signed_single_use_verification_and_login_contract_is_preserved(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.email.store'), $this->emailInput())->assertSessionHasNoErrors();
        $pending = $user->fresh();
        $this->assertSame($user->email, $pending->email);
        $this->assertSame('new@webmail.umm.ac.id', $pending->pending_email);
        $this->assertTrue($this->app['auth']->guard()->validate(['email' => $user->email, 'password' => 'password']));
        $this->assertFalse($this->app['auth']->guard()->validate(['email' => $pending->pending_email, 'password' => 'password']));
        $url = $this->verificationUrl();
        Password::broker('users')->createToken($user);
        $this->get($url)->assertRedirect(route('profile.edit'));
        $activated = $user->fresh();
        $this->assertSame('new@webmail.umm.ac.id', $activated->email);
        $this->assertNotNull($activated->email_verified_at);
        $this->assertNull($activated->pending_email_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertTrue($this->app['auth']->guard()->validate(['email' => $activated->email, 'password' => 'password']));
        $this->get($url)->assertForbidden();
    }

    public function test_invalid_duplicate_unchanged_and_non_campus_email_changes_are_handled(): void
    {
        $user = $this->account();
        User::factory()->create(['email' => 'owned@webmail.umm.ac.id']);
        $this->actingAs($user);
        foreach (['invalid', 'owned@webmail.umm.ac.id', 'outside@example.com', str_repeat('x', 256).'@webmail.umm.ac.id'] as $email) {
            $this->post(route('profile.email.store'), $this->emailInput($email))->assertSessionHasErrors('email');
        }
        $this->post(route('profile.email.store'), $this->emailInput($user->email))->assertSessionHasNoErrors();
        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_sensitive_operations_require_correct_password_and_never_flash_it(): void
    {
        $user = $this->account();
        $this->actingAs($user);
        foreach (['profile.email.store', 'profile.email.resend', 'profile.sessions.destroy'] as $route) {
            $this->post(route($route), ['email' => 'new@webmail.umm.ac.id', 'current_password' => 'wrong-secret'])->assertSessionHasErrors('current_password');
            $this->assertArrayNotHasKey('current_password', session()->getOldInput());
        }
        $this->patch(route('profile.password'), ['current_password' => 'wrong-secret', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertNull($user->fresh()->pending_email);
    }

    public function test_expired_tampered_wrong_account_and_wrong_token_links_do_not_activate_email(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.email.store'), $this->emailInput());
        $url = $this->verificationUrl();
        $this->get($url.'&changed=1')->assertForbidden();
        $wrongToken = URL::temporarySignedRoute('profile.email.verify', now()->addMinutes(60), ['id' => $user->id, 'token' => Str::random(64)]);
        $this->get($wrongToken)->assertForbidden();
        $other = User::factory()->create();
        $this->actingAs($other)->get($url)->assertForbidden();
        $this->actingAs($user);
        $this->travel(61)->minutes();
        $this->get($url)->assertForbidden();
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_resend_rotates_token_cancel_invalidates_link_and_delivery_failure_rolls_back(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.email.store'), $this->emailInput());
        $oldUrl = $this->verificationUrl();
        $oldHash = $user->fresh()->pending_email_token;
        $this->post(route('profile.email.resend'), ['current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertNotSame($oldHash, $user->fresh()->pending_email_token);
        $this->get($oldUrl)->assertForbidden();
        $this->delete(route('profile.email.cancel'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->pending_email);
        $this->post(route('profile.email.resend'), ['current_password' => 'password'])->assertSessionHasErrors('email');
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Simulated SMTP failure with sensitive text'));
        $response = $this->post(route('profile.email.store'), $this->emailInput());
        $response->assertSessionHasErrors('email');
        $this->assertNull($user->fresh()->pending_email);
        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_email_collision_at_activation_preserves_original_email(): void
    {
        $user = $this->account();
        $this->actingAs($user)->post(route('profile.email.store'), $this->emailInput());
        $url = $this->verificationUrl();
        User::factory()->create(['email' => 'new@webmail.umm.ac.id']);
        $this->get($url)->assertSessionHasErrors('email');
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertNotNull($user->fresh()->pending_email_token);
    }

    public function test_password_confirmation_policy_and_new_password_authentication(): void
    {
        $user = $this->account();
        $this->actingAs($user);
        foreach ([['short', 'short'], ['new-password', 'mismatch'], ['password', 'password']] as [$password, $confirmation]) {
            $this->patch(route('profile.password'), ['current_password' => 'password', 'password' => $password, 'password_confirmation' => $confirmation])->assertSessionHasErrors('password');
        }
        $this->patch(route('profile.password'), ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->post(route('login.post'), ['email' => $user->email, 'password' => 'new-password'])->assertRedirect(route('dashboard'));
    }

    public function test_revocation_database_failure_rolls_back_password_and_session_version(): void
    {
        $user = $this->account();
        DB::connection()->beforeExecuting(function ($query) {
            if (str_starts_with($query, 'delete from `sessions`')) {
                throw new QueryException('mysql', 'Simulated session failure', [], new \RuntimeException('Database unavailable'));
            }
        });
        $this->actingAs($user)->patch(route('profile.password'), ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $this->assertSame(0, $user->fresh()->auth_session_version);
        $this->assertAuthenticated();
    }

    public function test_unsupported_session_driver_does_not_claim_success(): void
    {
        $user = $this->account();
        config(['session.driver' => 'array']);
        $this->actingAs($user)->post(route('profile.sessions.destroy'), ['current_password' => 'password'])->assertSessionHasErrors('current_password');
        $this->assertSame(0, $user->fresh()->auth_session_version);
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

    public function test_logout_all_sessions_revokes_real_device_and_remember_cookies_only_for_target_user(): void
    {
        $user = $this->account();
        $other = User::factory()->create();
        $sessionName = config('session.cookie');
        $rememberName = $this->app['auth']->guard()->getRecallerName();
        $login = fn ($account) => $this->post(route('login.post'), ['email' => $account->email, 'password' => 'password', 'remember' => true]);
        $a = $login($user);
        $cookieA = [$sessionName => $a->getCookie($sessionName)->getValue()];
        $this->browser();
        $b = $login($user);
        $cookieB = [$sessionName => $b->getCookie($sessionName)->getValue(), $rememberName => $b->getCookie($rememberName)->getValue()];
        $staleRow = (array) DB::table('sessions')->where('id', $cookieB[$sessionName])->first();
        $this->browser();
        $d = $login($other);
        $cookieD = [$sessionName => $d->getCookie($sessionName)->getValue()];
        $this->browser($cookieA);
        $this->post(route('profile.sessions.destroy'), ['current_password' => 'password'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        foreach ([$cookieA, $cookieB, [$rememberName => $cookieB[$rememberName]]] as $cookies) {
            $this->browser($cookies);
            $this->get(route('dashboard'))->assertRedirect(route('login'));
            $this->assertGuest();
        }
        // Simulate a stale request persisting its pre-revocation session payload.
        DB::table('sessions')->updateOrInsert(['id' => $staleRow['id']], $staleRow);
        $this->browser([$sessionName => $staleRow['id']]);
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->browser($cookieD);
        $this->get(route('dashboard'))->assertOk();
        $this->assertAuthenticatedAs($other);
    }

    public function test_revocation_during_an_inflight_request_cannot_restore_authenticated_session(): void
    {
        $user = $this->account();
        Route::get('/session-race-probe', function () use ($user) {
            User::whereKey($user->id)->increment('auth_session_version');

            return response('protected response');
        })->middleware(['web', 'auth']);
        $this->actingAs($user)->get('/session-race-probe')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_email_requests_are_rate_limited_and_secret_fields_are_hidden(): void
    {
        $user = $this->account();
        $this->actingAs($user);
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('profile.email.store'), $this->emailInput($user->email))->assertRedirect();
        }
        $this->post(route('profile.email.store'), $this->emailInput())->assertStatus(429);
        $this->assertArrayNotHasKey('pending_email_token', $user->toArray());
        $this->assertArrayNotHasKey('auth_session_version', $user->toArray());
    }
}
