<?php

namespace Tests\Feature\Module4;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedMemoryTestCase;

class A3ProfilePhotoReadTest extends IsolatedMemoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** A3-01 */
    public function test_a3_01_private_photo_response(): void
    {
        $user = User::factory()->create();
        $path = 'profile-photos/'.$user->id.'/photo.png';
        Storage::disk('local')->put($path, 'synthetic');
        $user->forceFill(['profile_photo_path' => $path])->save();
        $this->actingAs($user)->get(route('profile.photo'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** A3-02 */
    public function test_a3_02_missing_foreign_and_unsafe_path(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        foreach ([null, 'profile-photos/999999/foreign.png', 'profile-photos/'.$user->id.'/../foreign.png'] as $path) {
            $user->forceFill(['profile_photo_path' => $path])->save();
            $this->get(route('profile.photo'))->assertNotFound();
        }
    }

    /** A3-03 */
    public function test_a3_03_valid_path_missing_file(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['profile_photo_path' => 'profile-photos/'.$user->id.'/missing.png'])->save();
        $this->actingAs($user)->get(route('profile.photo'))->assertNotFound();
    }
}
