<?php

namespace Tests\Feature\Module4;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\IsolatedMemoryTestCase;

class A4ProfilePhotoUpdateTest extends IsolatedMemoryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('avatar.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/aZkAAAAASUVORK5CYII='));
    }

    /** A4-01 */
    public function test_a4_01_first_upload(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => $this->image()])->assertSessionHasNoErrors();
        $path = $user->fresh()->profile_photo_path;
        $this->assertStringStartsWith('profile-photos/'.$user->id.'/', $path);
        Storage::disk('local')->assertExists($path);
    }

    /** A4-02 */
    public function test_a4_02_replacement_deletes_old_file(): void
    {
        $user = User::factory()->create();
        $old = 'profile-photos/'.$user->id.'/old.png';
        Storage::disk('local')->put($old, 'synthetic');
        $user->forceFill(['profile_photo_path' => $old])->save();
        $this->actingAs($user)->post(route('profile.photo.update'), ['photo' => $this->image()])->assertSessionHasNoErrors();
        $new = $user->fresh()->profile_photo_path;
        $this->assertNotEquals($old, $new);
        Storage::disk('local')->assertExists($new);
        Storage::disk('local')->assertMissing($old);
    }

    /** A4-03 */
    public function test_a4_03_invalid_upload_preserves_existing_photo(): void
    {
        $user = User::factory()->create();
        $old = 'profile-photos/'.$user->id.'/old.png';
        Storage::disk('local')->put($old, 'synthetic');
        $user->forceFill(['profile_photo_path' => $old])->save();
        $this->actingAs($user);
        foreach ([[], ['photo' => UploadedFile::fake()->createWithContent('bad.svg', '<svg></svg>')], ['photo' => UploadedFile::fake()->create('large.png', 2049, 'image/png')]] as $input) {
            $this->post(route('profile.photo.update'), $input)->assertSessionHasErrors('photo');
            $this->assertSame($old, $user->fresh()->profile_photo_path);
            Storage::disk('local')->assertExists($old);
        }
    }
}
