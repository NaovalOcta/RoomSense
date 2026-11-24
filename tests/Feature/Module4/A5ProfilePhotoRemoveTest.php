<?php

namespace Tests\Feature\Module4;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedMemoryTestCase;

class A5ProfilePhotoRemoveTest extends IsolatedMemoryTestCase
{
    /** A5-01 */
    public function test_a5_01_remove_and_repeat(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = 'profile-photos/'.$user->id.'/photo.png';
        Storage::disk('local')->put($path, 'synthetic');
        $user->forceFill(['profile_photo_path' => $path])->save();
        $this->actingAs($user)->delete(route('profile.photo.destroy'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('local')->assertMissing($path);
        $this->delete(route('profile.photo.destroy'))->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->profile_photo_path);
    }
}
