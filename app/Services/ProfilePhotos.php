<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProfilePhotos
{
    public function replace(User $user, ?UploadedFile $photo): bool
    {
        $newPath = null;
        try {
            if ($photo) {
                $newPath = $photo->store('profile-photos/'.$user->id, 'local');
                if (! $newPath) {
                    throw new RuntimeException('Photo storage failed.');
                }
            }

            $oldPath = DB::transaction(function () use ($user, $newPath) {
                $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                $oldPath = $account->profile_photo_path;
                $account->profile_photo_path = $newPath;
                $account->save();

                return $oldPath;
            });
        } catch (Throwable $exception) {
            $this->delete($user, $newPath);
            throw $exception;
        }

        $user->refresh();

        return $this->delete($user, $oldPath);
    }

    private function delete(User $user, ?string $path): bool
    {
        if (! $path) {
            return true;
        }
        // Only generated files in this account's photo directory may be removed.
        if (! str_starts_with($path, 'profile-photos/'.$user->id.'/') || str_contains($path, '..')) {
            Log::warning('Profile photo cleanup refused.', ['category' => 'invalid_path']);

            return false;
        }
        try {
            if (Storage::disk('local')->delete($path)) {
                return true;
            }
        } catch (Throwable) {
            // Retain the safely committed replacement and report cleanup separately.
        }
        Log::warning('Profile photo cleanup failed.', ['category' => 'filesystem']);

        return false;
    }
}
