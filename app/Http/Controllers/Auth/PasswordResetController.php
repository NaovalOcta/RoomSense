<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $validator = Validator::make($request->only('email'), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        if ($validator->fails()) {
            return redirect()->route('password.request')->withErrors($validator)->withInput($request->only('email'));
        }

        try {
            Password::broker('users')->sendResetLink($validator->validated());
        } catch (TransportExceptionInterface) {
            // Preserve the same public response, including when transport fails.
            Log::warning('Password reset email delivery failed.', ['category' => 'smtp_transport']);
        }

        return redirect()->route('password.request')
            ->with('status', 'Jika email tersebut terdaftar, tautan reset password akan dikirim. Periksa folder Spam/Junk dan tunggu setidaknya 60 detik sebelum mencoba lagi.')
            ->withInput($request->only('email'));
    }

    public function edit(Request $request, string $token): View
    {
        $email = $request->query('email');

        return view('auth.reset-password', [
            'token' => $token,
            'email' => is_string($email) ? $email : '',
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => strtolower(trim($request->input('email')))]);
        }

        $token = $request->input('token');
        $resetUrl = route('password.reset', ['token' => is_string($token) && $token !== '' ? $token : 'invalid']);
        $validator = Validator::make($request->only('token', 'email', 'password', 'password_confirmation'), [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        if ($validator->fails()) {
            return redirect($resetUrl)->withErrors($validator)->withInput($request->only('email'));
        }

        $resetUser = null;
        try {
            // Include broker token consumption in the same transaction as all mutations.
            $status = DB::transaction(function () use ($validator, &$resetUser) {
                return Password::broker('users')->reset($validator->validated(), function (User $user, string $password) use (&$resetUser) {
                    if (Hash::check($password, $user->password)) {
                        throw ValidationException::withMessages([
                            'password' => 'Password baru harus berbeda dari password saat ini.',
                        ]);
                    }

                    $user->password = Hash::make($password);
                    $user->setRememberToken(Str::random(60));
                    $user->save();

                    DB::connection(config('session.connection'))
                        ->table(config('session.table'))
                        ->where('user_id', $user->getKey())->delete();
                    $resetUser = $user;
                });
            });
        } catch (ValidationException $exception) {
            return redirect($resetUrl)->withErrors($exception->errors())->withInput($request->only('email'));
        } catch (QueryException) {
            Log::warning('Password reset transaction failed.', ['category' => 'database']);

            return redirect($resetUrl)->withErrors([
                'email' => 'Password belum dapat direset. Silakan coba lagi nanti.',
            ])->withInput($request->only('email'));
        }

        if ($status === Password::PASSWORD_RESET) {
            event(new PasswordReset($resetUser));

            return redirect()->route('login')->with('status', 'Password berhasil direset. Silakan login dengan password baru.');
        }

        return redirect($resetUrl)->withErrors([
            'email' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
        ])->withInput($request->only('email'));
    }
}
