<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountSessions;
use App\Services\ProfilePhotos;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        if (is_string($request->input('name'))) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        if (is_string($request->input('phone'))) {
            $request->merge(['phone' => trim($request->input('phone')) ?: null]);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?=.*[0-9])\+?[0-9 ()-]{6,30}$/'],
            'id' => ['prohibited'], 'user_id' => ['prohibited'], 'is_admin' => ['prohibited'],
            'role' => ['prohibited'], 'email' => ['prohibited'],
        ]);
        $user = $request->user();
        $user->name = $validated['name'];
        $user->phone = isset($validated['phone']) ? preg_replace('/[ ()-]/', '', $validated['phone']) : null;
        $user->save();

        return back()->with('success', 'Informasi profil berhasil diperbarui.');
    }

    public function photo(Request $request)
    {
        $path = $request->user()->profile_photo_path;
        abort_unless($path && str_starts_with($path, 'profile-photos/'.$request->user()->id.'/') && ! str_contains($path, '..'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function updatePhoto(Request $request, ProfilePhotos $photos)
    {
        $request->validate(['photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        return $this->savePhoto($request, $photos, $request->file('photo'));
    }

    public function removePhoto(Request $request, ProfilePhotos $photos)
    {
        return $this->savePhoto($request, $photos, null);
    }

    private function savePhoto(Request $request, ProfilePhotos $photos, $photo)
    {
        try {
            $cleaned = $photos->replace($request->user(), $photo);
        } catch (Throwable) {
            Log::warning('Profile photo update failed.', ['category' => 'storage_or_database']);

            return back()->withErrors(['photo' => 'Foto belum dapat diperbarui. Silakan coba lagi.']);
        }

        $response = back()->with('success', 'Foto profil berhasil diperbarui.');
        if (! $cleaned) {
            $response->with('warning', 'Foto aktif sudah diperbarui, tetapi pembersihan file lama perlu dicoba kembali oleh pengelola.');
        }

        return $response;
    }

    public function password(Request $request, AccountSessions $sessions)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        return $this->revokeSessions($request, $sessions, $validated['password']);
    }

    public function logoutSessions(Request $request, AccountSessions $sessions)
    {
        $request->validate(['current_password' => ['required', 'string', 'current_password:web']]);

        return $this->revokeSessions($request, $sessions);
    }

    private function revokeSessions(Request $request, AccountSessions $sessions, ?string $password = null)
    {
        try {
            DB::transaction(function () use ($request, $sessions, $password) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                // Recheck after acquiring the lock, in case credentials changed concurrently.
                if (! Hash::check($request->input('current_password'), $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak sesuai.']);
                }
                if ($password !== null) {
                    $user->password = Hash::make($password);
                }
                $sessions->revoke($user);
            });
        } catch (QueryException) {
            Log::warning('Account session revocation failed.', ['category' => 'database']);

            return back()->withErrors(['current_password' => 'Sesi belum dapat dicabut. Silakan coba lagi.']);
        }

        Auth::guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', $password !== null
            ? 'Password berhasil diperbarui dan semua sesi dicabut. Silakan login kembali.'
            : 'Semua sesi berhasil dicabut. Silakan login kembali.');
    }
}
