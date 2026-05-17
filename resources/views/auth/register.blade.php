<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Daftar ke RoomSense — Sistem Peminjaman Ruangan Kampus UMM.">
    <title>Daftar — RoomSense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Animated background grid */
        .bg-grid {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Floating orbs animation */
        @keyframes float-slow {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-20px) scale(1.02); }
        }
        @keyframes float-medium {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(15px) scale(0.98); }
        }
        .orb-1 { animation: float-slow 8s ease-in-out infinite; }
        .orb-2 { animation: float-medium 10s ease-in-out infinite; }
        .orb-3 { animation: float-slow 12s ease-in-out infinite 2s; }

        /* Card slide-up animation */
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-animate { animation: slide-up 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Input focus ring */
        .input-field {
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
        }
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        /* Tab underline animation */
        .auth-tab { position: relative; }
        .auth-tab::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 2px;
            background: #3b82f6;
            border-radius: 2px 2px 0 0;
            transform: scaleX(0);
            transition: transform 0.25s ease;
        }
        .auth-tab.active::after { transform: scaleX(1); }

        /* Button shimmer */
        .btn-primary {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 60%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.08), transparent);
            transition: left 0.4s;
        }
        .btn-primary:hover::after { left: 150%; }

        /* Password strength bar */
        .strength-bar {
            height: 3px;
            border-radius: 99px;
            transition: width 0.3s ease, background-color 0.3s ease;
        }

        /* UMM badge pulse */
        @keyframes badge-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.3); }
            50% { box-shadow: 0 0 0 4px rgba(59, 130, 246, 0); }
        }
        .badge-umm { animation: badge-pulse 3s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-full flex items-center justify-center bg-slate-950 bg-grid px-4 py-10 overflow-auto">

    <!-- ── Decorative background orbs ── -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="orb-1 absolute -top-48 -right-48 w-96 h-96 rounded-full bg-blue-600/12 blur-3xl"></div>
        <div class="orb-2 absolute top-1/2 -left-32 w-72 h-72 rounded-full bg-indigo-600/10 blur-3xl"></div>
        <div class="orb-3 absolute -bottom-48 right-1/4 w-80 h-80 rounded-full bg-violet-600/8 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative card-animate">

        <!-- ── Brand ── -->
        <div class="text-center mb-8">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-2xl shadow-blue-600/40 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2 3h20v4H2V3zm2 5v13h3V8H4zm6 0v13h4V8h-4zm7 0v13h3V8h-3z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">Room<span class="text-blue-400">Sense</span></h1>
            <p class="mt-1 text-sm text-slate-400">Sistem Peminjaman Ruangan Kampus UMM</p>
        </div>

        <!-- ── Auth Card ── -->
        <div class="bg-slate-800/60 backdrop-blur-xl border border-slate-700/60 rounded-2xl shadow-2xl overflow-hidden">

            <!-- Tab Navigation -->
            <div class="flex border-b border-slate-700/60">
                <a id="tab-login" href="{{ route('login') }}"
                   class="auth-tab flex-1 py-4 text-sm font-medium text-slate-400 text-center transition-colors hover:text-slate-200">
                    Masuk
                </a>
                <a id="tab-register" href="{{ route('register') }}"
                   class="auth-tab active flex-1 py-4 text-sm font-semibold text-blue-400 text-center transition-colors hover:text-blue-300">
                    Daftar
                </a>
            </div>

            <!-- Form -->
            <div class="p-8">

                <!-- UMM Email notice -->
                <div class="badge-umm mb-5 flex items-start gap-3 p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/25">
                    <svg class="h-4 w-4 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-xs text-blue-300 leading-relaxed">
                        Pendaftaran hanya diperbolehkan menggunakan email resmi UMM dengan domain
                        <span class="font-semibold text-blue-200">@webmail.umm.ac.id</span>
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 p-3.5 rounded-xl bg-red-500/10 border border-red-500/30 flex items-start gap-3">
                        <svg class="h-4 w-4 text-red-400 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p class="text-xs text-red-400 leading-relaxed">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-4" id="registerForm">
                    @csrf

                    <!-- Full Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Nama Lengkap
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                autofocus
                                autocomplete="name"
                                class="input-field w-full rounded-xl border bg-slate-900/60 pl-10 pr-4 py-3 text-sm text-white
                                       placeholder:text-slate-500 outline-none
                                       {{ $errors->has('name') ? 'border-red-500 focus:border-red-400' : 'border-slate-700 focus:border-blue-500' }}"
                                placeholder="Ahmad Fauzi"
                            >
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Email Kampus
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                                </svg>
                            </div>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                oninput="validateEmail(this)"
                                class="input-field w-full rounded-xl border bg-slate-900/60 pl-10 pr-10 py-3 text-sm text-white
                                       placeholder:text-slate-500 outline-none
                                       {{ $errors->has('email') ? 'border-red-500 focus:border-red-400' : 'border-slate-700 focus:border-blue-500' }}"
                                placeholder="nim@webmail.umm.ac.id"
                            >
                            <div id="email-status" class="absolute inset-y-0 right-0 pr-3.5 flex items-center hidden">
                                <!-- Injected by JS -->
                            </div>
                        </div>
                        <p id="email-hint" class="mt-1.5 text-xs text-slate-500">Harus menggunakan domain @webmail.umm.ac.id</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordStrength(this.value)"
                                class="input-field w-full rounded-xl border border-slate-700 focus:border-blue-500
                                       bg-slate-900/60 pl-10 pr-12 py-3 text-sm text-white
                                       placeholder:text-slate-500 outline-none"
                                placeholder="Min. 8 karakter"
                            >
                            <button type="button" onclick="togglePassword('password', this)"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300 transition-colors">
                                <svg class="h-4 w-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div class="mt-2 space-y-1.5">
                            <div class="flex gap-1">
                                <div class="strength-bar flex-1 bg-slate-700" id="str-1"></div>
                                <div class="strength-bar flex-1 bg-slate-700" id="str-2"></div>
                                <div class="strength-bar flex-1 bg-slate-700" id="str-3"></div>
                                <div class="strength-bar flex-1 bg-slate-700" id="str-4"></div>
                            </div>
                            <p id="strength-label" class="text-xs text-slate-600"></p>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-300 mb-1.5">
                            Konfirmasi Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <input
                                type="password"
                                id="password_confirmation"
                                name="password_confirmation"
                                required
                                autocomplete="new-password"
                                oninput="checkPasswordMatch()"
                                class="input-field w-full rounded-xl border border-slate-700 focus:border-blue-500
                                       bg-slate-900/60 pl-10 pr-12 py-3 text-sm text-white
                                       placeholder:text-slate-500 outline-none"
                                placeholder="Ulangi password"
                            >
                            <button type="button" onclick="togglePassword('password_confirmation', this)"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300 transition-colors">
                                <svg class="h-4 w-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <p id="match-hint" class="mt-1.5 text-xs hidden"></p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" id="submit-btn"
                            class="btn-primary w-full rounded-xl py-3 text-sm font-semibold text-white transition-all duration-200
                                   shadow-lg shadow-blue-600/25 hover:shadow-blue-500/35 hover:-translate-y-0.5 active:translate-y-0 mt-2">
                        Buat Akun
                    </button>
                </form>

                <!-- Link to Login -->
                <p class="mt-6 text-center text-sm text-slate-500">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" class="font-semibold text-blue-400 hover:text-blue-300 transition-colors ml-1">
                        Masuk sekarang →
                    </a>
                </p>

            </div>
        </div>

        <!-- Footer note -->
        <p class="text-center text-xs text-slate-600 mt-6">
            &copy; {{ date('Y') }} RoomSense &mdash; Universitas Muhammadiyah Malang
        </p>

    </div>

    <script>
        function togglePassword(fieldId, btn) {
            const input = document.getElementById(fieldId);
            const icon = btn.querySelector('.eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                `;
            } else {
                input.type = 'password';
                icon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                `;
            }
        }

        function validateEmail(input) {
            const statusEl = document.getElementById('email-status');
            const hintEl = document.getElementById('email-hint');
            const val = input.value;

            if (!val) {
                statusEl.classList.add('hidden');
                hintEl.textContent = 'Harus menggunakan domain @webmail.umm.ac.id';
                hintEl.className = 'mt-1.5 text-xs text-slate-500';
                return;
            }

            statusEl.classList.remove('hidden');

            if (val.endsWith('@webmail.umm.ac.id')) {
                statusEl.innerHTML = `<svg class="h-4 w-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>`;
                hintEl.textContent = 'Email kampus valid ✓';
                hintEl.className = 'mt-1.5 text-xs text-green-400';
                input.classList.remove('border-red-500');
                input.classList.add('border-green-500');
            } else if (val.includes('@')) {
                statusEl.innerHTML = `<svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>`;
                hintEl.textContent = 'Email harus berakhiran @webmail.umm.ac.id';
                hintEl.className = 'mt-1.5 text-xs text-red-400';
                input.classList.add('border-red-500');
                input.classList.remove('border-green-500');
            } else {
                statusEl.classList.add('hidden');
                hintEl.textContent = 'Harus menggunakan domain @webmail.umm.ac.id';
                hintEl.className = 'mt-1.5 text-xs text-slate-500';
                input.classList.remove('border-red-500', 'border-green-500');
            }
        }

        function checkPasswordStrength(value) {
            const bars = [document.getElementById('str-1'), document.getElementById('str-2'),
                          document.getElementById('str-3'), document.getElementById('str-4')];
            const label = document.getElementById('strength-label');

            bars.forEach(b => { b.style.backgroundColor = '#334155'; });
            label.textContent = '';

            if (!value) return;

            let score = 0;
            if (value.length >= 8) score++;
            if (/[A-Z]/.test(value)) score++;
            if (/[0-9]/.test(value)) score++;
            if (/[^A-Za-z0-9]/.test(value)) score++;

            const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
            const labels = ['Sangat lemah', 'Lemah', 'Sedang', 'Kuat'];

            for (let i = 0; i < score; i++) {
                bars[i].style.backgroundColor = colors[score - 1];
            }
            label.textContent = labels[score - 1] || '';
            label.style.color = colors[score - 1] || '';
        }

        function checkPasswordMatch() {
            const pw = document.getElementById('password').value;
            const conf = document.getElementById('password_confirmation').value;
            const hint = document.getElementById('match-hint');

            if (!conf) { hint.classList.add('hidden'); return; }

            hint.classList.remove('hidden');
            if (pw === conf) {
                hint.textContent = 'Password cocok ✓';
                hint.className = 'mt-1.5 text-xs text-green-400';
            } else {
                hint.textContent = 'Password tidak cocok';
                hint.className = 'mt-1.5 text-xs text-red-400';
            }
        }
    </script>
</body>
</html>
