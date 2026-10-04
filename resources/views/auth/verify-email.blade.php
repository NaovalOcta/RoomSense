@extends('layouts.app')
@section('title', 'Verifikasi Email')

@section('content')
<section aria-labelledby="verification-heading" class="flex min-h-[calc(100svh-15rem)] items-center justify-center py-4 sm:py-8">
    <div class="w-full max-w-lg">
        <div class="overflow-hidden rounded-2xl border border-slate-700/60 bg-slate-800/60 shadow-2xl shadow-slate-950/40 backdrop-blur-xl">
            <div class="h-1 bg-gradient-to-r from-blue-600 to-blue-400" aria-hidden="true"></div>

            <div class="p-6 sm:p-8">
                <div class="text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-500/20 bg-blue-500/10 text-blue-400" aria-hidden="true">
                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0a2.25 2.25 0 00-2.25-2.25h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-300">
                        Satu langkah lagi
                    </span>
                    <h1 id="verification-heading" class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">Verifikasi email Anda</h1>
                    <p class="mt-3 text-sm leading-relaxed text-slate-400">
                        Terima kasih telah mendaftar! Verifikasi email kampus Anda untuk mulai meminjam ruangan di RoomSense.
                    </p>
                </div>

                <div class="mt-6 rounded-xl border border-slate-700/60 bg-slate-900/60 p-4 text-center">
                    <p class="text-xs text-slate-400">Alamat email verifikasi</p>
                    <p class="mt-1.5 break-all text-sm font-semibold text-slate-200">{{ auth()->user()->email }}</p>
                </div>

                <ol class="mt-6 space-y-3 text-sm text-slate-300">
                    <li class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-xs font-semibold text-blue-300" aria-hidden="true">1</span>
                        <span class="pt-0.5 leading-relaxed">Buka kotak masuk email kampus Anda.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/10 text-xs font-semibold text-blue-300" aria-hidden="true">2</span>
                        <span class="pt-0.5 leading-relaxed">Klik tautan verifikasi pada email dari RoomSense.</span>
                    </li>
                </ol>

                @if (session('message'))
                    <div class="mt-6 flex items-start gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4" role="status">
                        <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75l2.25 2.25L15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-sm leading-relaxed text-emerald-300">{{ session('message') }}</p>
                    </div>
                @endif

                <div class="mt-6 border-t border-slate-700/60 pt-6">
                    <p class="mb-4 text-center text-xs leading-relaxed text-slate-400">
                        Belum menerima email? Periksa folder spam atau kirim ulang tautan verifikasi.
                    </p>
                    <form id="resend-verification-form" method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button id="resend-verification-button" type="submit" class="inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:from-blue-700 hover:to-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 disabled:cursor-wait disabled:opacity-60">
                            <svg id="resend-verification-spinner" class="hidden h-4 w-4 motion-safe:animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" />
                                <path class="opacity-75" fill="currentColor" d="M21 12h-3a6 6 0 00-6-6V3a9 9 0 019 9z" />
                            </svg>
                            <span id="resend-verification-label" aria-live="polite">Kirim ulang tautan verifikasi</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="border-t border-slate-700/60 bg-slate-900/30 px-6 py-4 text-center sm:px-8">
                <p class="text-xs text-slate-400">Ingin menggunakan akun lain?</p>
                <form action="{{ route('logout') }}" method="POST" class="mt-1">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 text-sm font-medium text-slate-300 transition-colors hover:bg-slate-800 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Keluar dari akun
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const resendForm = document.getElementById('resend-verification-form');
    const resendButton = document.getElementById('resend-verification-button');
    const resendLabel = document.getElementById('resend-verification-label');
    const resendSpinner = document.getElementById('resend-verification-spinner');

    resendForm.addEventListener('submit', () => {
        resendButton.disabled = true;
        resendForm.setAttribute('aria-busy', 'true');
        resendLabel.textContent = 'Mengirim tautan verifikasi...';
        resendSpinner.classList.remove('hidden');
    });

    window.addEventListener('pageshow', () => {
        resendButton.disabled = false;
        resendForm.removeAttribute('aria-busy');
        resendLabel.textContent = 'Kirim ulang tautan verifikasi';
        resendSpinner.classList.add('hidden');
    });
</script>
@endpush
