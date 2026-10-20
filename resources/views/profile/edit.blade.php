@extends('layouts.app')
@section('title', 'Profile Settings')
@section('content')
@php
    $inputClass = 'mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-blue-500';
    $buttonClass = 'rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400';
    $panelClass = 'rounded-2xl border border-slate-800 bg-slate-900/50 p-5 sm:p-6 space-y-4';
@endphp
<div class="mx-auto max-w-3xl space-y-6">
    <div><h1 class="text-2xl font-bold sm:text-3xl">Profile Settings</h1><p class="mt-2 text-sm text-slate-400">Kelola profil dan keamanan akun Anda.</p></div>

    <section class="{{ $panelClass }}" aria-labelledby="profile-info-title">
        <h2 id="profile-info-title" class="text-lg font-semibold">Profile Information</h2>
        <p class="text-sm text-slate-400">Role: <strong class="text-slate-200">{{ $user->isAdmin() ? 'Administrator' : 'Mahasiswa' }}</strong> (read-only)</p>
        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf @method('PATCH')
            <div><label for="name" class="text-sm font-medium">Full name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name" class="{{ $inputClass }}"></div>
            <div><label for="phone" class="text-sm font-medium">Phone number (optional)</label><input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel" class="{{ $inputClass }}"><p class="mt-1 text-xs text-slate-500">6–30 karakter; angka, tanda +, spasi, tanda kurung, atau tanda hubung.</p></div>
            <button class="{{ $buttonClass }}">Save profile</button>
        </form>
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="profile-photo-title">
        <h2 id="profile-photo-title" class="text-lg font-semibold">Profile Picture</h2>
        <x-profile-avatar :user="$user" size="h-20 w-20" />
        <form action="{{ route('profile.photo.update') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div><label for="photo" class="text-sm font-medium">Upload or replace photo</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required class="{{ $inputClass }}"><p class="mt-1 text-xs text-slate-500">JPG, PNG, WEBP; maksimal 2 MB.</p></div>
            <button class="{{ $buttonClass }}">Save photo</button>
        </form>
        @if($user->profile_photo_path)
            <form action="{{ route('profile.photo.destroy') }}" method="POST">@csrf @method('DELETE')<button class="text-sm font-semibold text-rose-400 hover:text-rose-300">Remove photo</button></form>
        @endif
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="profile-email-title">
        <h2 id="profile-email-title" class="text-lg font-semibold">Email Settings</h2>
        <p class="break-words text-sm">Active email: <strong>{{ $user->email }}</strong></p>
        <p class="text-sm text-slate-400">{{ $user->email_verified_at ? 'Email verified' : 'Email not verified' }}{{ $user->isAdmin() && ! $user->email_verified_at ? ' · Administrator access is permitted by the existing policy.' : '' }}</p>
        <p class="text-sm text-slate-400">Email lama tetap aktif sampai Anda memverifikasi email baru. Buka tautan menggunakan akun ini.</p>
        @if($user->password)
            <form action="{{ route('profile.email.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label for="email" class="text-sm font-medium">New email</label><input id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required autocomplete="email" class="{{ $inputClass }}">@unless($user->isAdmin())<p class="mt-1 text-xs text-slate-500">Gunakan email @webmail.umm.ac.id.</p>@endunless</div>
                <div><label for="email-current-password" class="text-sm font-medium">Current password</label><input id="email-current-password" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClass }}"></div>
                <button class="{{ $buttonClass }}">Send verification</button>
            </form>
            @if($user->pending_email)
                <div class="space-y-3 rounded-xl border border-amber-500/30 p-4">
                    <p class="break-words text-sm text-amber-300">Pending: {{ $user->pending_email }} · {{ $user->pending_email_expires_at?->isFuture() ? 'Menunggu verifikasi' : 'Tautan kedaluwarsa' }}</p>
                    <form action="{{ route('profile.email.resend') }}" method="POST" class="space-y-3">@csrf<label for="resend-current-password" class="text-sm">Current password</label><input id="resend-current-password" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClass }}"><button class="{{ $buttonClass }}">Resend verification</button></form>
                    <form action="{{ route('profile.email.cancel') }}" method="POST">@csrf @method('DELETE')<button class="text-sm text-slate-300 hover:text-white">Cancel email change</button></form>
                </div>
            @endif
        @endif
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="profile-security-title">
        <h2 id="profile-security-title" class="text-lg font-semibold">Password &amp; Security</h2>
        @if($user->password)
            <p class="text-sm text-slate-400">Minimal 8 karakter. Perubahan password mencabut semua sesi, termasuk sesi ini.</p>
            <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">@csrf @method('PATCH')
                <div><label for="password-current" class="text-sm font-medium">Current password</label><input id="password-current" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClass }}"></div>
                <div><label for="password" class="text-sm font-medium">New password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="{{ $inputClass }}"></div>
                <div><label for="password-confirmation" class="text-sm font-medium">Confirm new password</label><input id="password-confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="{{ $inputClass }}"></div>
                <button class="{{ $buttonClass }}">Change password</button>
            </form>
        @else
            <p class="text-sm text-slate-400">Akun ini belum memiliki password lokal. Hubungi pengelola untuk alur pengamanan akun yang sesuai.</p>
        @endif
    </section>

    <section class="{{ $panelClass }}" aria-labelledby="profile-sessions-title">
        <h2 id="profile-sessions-title" class="text-lg font-semibold">Session Management</h2>
        <p class="text-sm text-slate-400">Keluar dari semua perangkat, termasuk perangkat ini. Anda perlu login kembali.</p>
        @if($user->password)
            <form action="{{ route('profile.sessions.destroy') }}" method="POST" class="space-y-4">@csrf
                <div><label for="sessions-current-password" class="text-sm font-medium">Current password</label><input id="sessions-current-password" name="current_password" type="password" required autocomplete="current-password" class="{{ $inputClass }}"></div>
                <button data-confirm="Keluar dari semua perangkat, termasuk perangkat ini?" class="rounded-xl border border-rose-500/40 px-4 py-2.5 text-sm font-semibold text-rose-400 hover:bg-rose-500/10">Logout All Sessions</button>
            </form>
        @endif
    </section>
</div>
@endsection
