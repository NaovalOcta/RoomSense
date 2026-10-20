<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PendingEmailVerification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AccountEmailController extends Controller
{
    public function store(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'current_password' => ['required', 'string', 'current_password:web'],
        ]);

        return $this->send($request, $validated['email']);
    }

    public function resend(Request $request)
    {
        $request->validate(['current_password' => ['required', 'string', 'current_password:web']]);

        return $this->send($request, null);
    }

    private function send(Request $request, ?string $email)
    {
        try {
            $sent = DB::transaction(function () use ($request, $email) {
                $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                if (! Hash::check($request->input('current_password'), $user->password)) {
                    throw ValidationException::withMessages(['current_password' => 'Password saat ini tidak sesuai.']);
                }
                $destination = $email ?? $user->pending_email;
                if (! $destination) {
                    throw ValidationException::withMessages(['email' => 'Tidak ada perubahan email yang menunggu verifikasi.']);
                }
                if ($destination === strtolower($user->email)) {
                    return false;
                }
                if (! $user->isAdmin() && ! str_ends_with($destination, '@webmail.umm.ac.id')) {
                    throw ValidationException::withMessages(['email' => 'Email wajib menggunakan domain @webmail.umm.ac.id.']);
                }
                if (User::where('email', $destination)->whereKeyNot($user->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'Alamat email sudah digunakan.']);
                }

                $token = Str::random(64);
                $user->pending_email = $destination;
                $user->pending_email_token = hash('sha256', $token);
                $user->pending_email_expires_at = now()->addMinutes(60);
                $user->save();
                $url = URL::temporarySignedRoute('profile.email.verify', $user->pending_email_expires_at, ['id' => $user->id, 'token' => $token]);

                // Synchronous delivery: a transport failure rolls back the pending request.
                Notification::route('mail', $destination)->notify(new PendingEmailVerification($url));

                return true;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            Log::warning('Pending email verification request failed.', ['category' => 'mail_or_database']);

            return back()->withErrors(['email' => 'Permintaan belum berhasil dikirim. Email aktif tetap sama; silakan coba lagi.']);
        }

        return back()->with('success', $sent
            ? 'Tautan verifikasi dikirim ke email baru. Email lama tetap aktif sampai verifikasi selesai.'
            : 'Alamat tersebut sudah menjadi email aktif Anda.');
    }

    public function verify(Request $request, string $id, string $token)
    {
        abort_unless((string) $request->user()->id === $id, 403, 'Tautan ini bukan milik akun Anda.');
        try {
            $user = DB::transaction(function () use ($id, $token) {
                $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
                abort_unless($user->pending_email && $user->pending_email_token && $user->pending_email_expires_at?->isFuture()
                    && hash_equals($user->pending_email_token, hash('sha256', $token)), 403, 'Tautan tidak valid, kedaluwarsa, atau sudah digunakan.');

                if (User::where('email', $user->pending_email)->whereKeyNot($user->id)->exists()) {
                    throw ValidationException::withMessages(['email' => 'Email pengganti sudah digunakan. Silakan ajukan alamat lain.']);
                }
                // An outstanding reset for the previous address must not outlive its ownership.
                Password::broker('users')->deleteToken($user);
                $user->email = $user->pending_email;
                $user->email_verified_at = now();
                $this->clearPending($user);
                $user->save(); // The existing UNIQUE(email) constraint resolves competing activations.

                return $user;
            });
        } catch (QueryException) {
            return redirect()->route('profile.edit')->withErrors(['email' => 'Email belum dapat diaktifkan. Silakan ajukan ulang perubahan email.']);
        }
        event(new Verified($user));

        return redirect()->route('profile.edit')->with('success', 'Email baru berhasil diverifikasi dan diaktifkan.');
    }

    public function cancel(Request $request)
    {
        DB::transaction(function () use ($request) {
            $user = User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $this->clearPending($user);
            $user->save();
        });

        return back()->with('success', 'Permintaan perubahan email dibatalkan.');
    }

    private function clearPending(User $user): void
    {
        $user->pending_email = null;
        $user->pending_email_token = null;
        $user->pending_email_expires_at = null;
    }
}
