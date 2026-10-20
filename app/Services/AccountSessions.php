<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountSessions
{
    public function revoke(User $user): void
    {
        if (config('session.driver') !== 'database') {
            throw ValidationException::withMessages(['current_password' => 'Pencabutan semua sesi memerlukan penyimpanan sesi database yang dikonfigurasi.']);
        }

        $user->auth_session_version++;
        $user->setRememberToken(Str::random(60));
        $user->save();

        DB::connection(config('session.connection'))
            ->table(config('session.table'))
            ->where('user_id', $user->getKey())->delete();
    }
}
