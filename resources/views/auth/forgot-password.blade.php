<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lupa Password akun RoomSense.">
    <title>Lupa Password - RoomSense</title>
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

        /* Show/hide password toggle */
        .toggle-password { cursor: pointer; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-950 bg-grid px-4 py-10 text-white">
    <main class="w-full max-w-md card-animate">
        <a href="{{ route('login') }}" class="block text-center text-2xl font-bold text-blue-400 mb-8">RoomSense</a>
        <section class="rounded-2xl border border-slate-700/50 bg-slate-800/60 p-8 shadow-2xl">
            <h1 class="text-2xl font-bold mb-2">Lupa Password?</h1>
            <p class="text-sm text-slate-400 mb-6">Masukkan email akun untuk meminta tautan reset password.</p>
            @if (session('status'))
                <div role="status" class="mb-5 rounded-xl border border-emerald-700 bg-emerald-900/30 p-4 text-sm text-emerald-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-5 rounded-xl border border-red-700 bg-red-900/30 p-4 text-sm text-red-200">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                    <input type="email" id="email" name="email" value="{{ is_string(old('email')) ? old('email') : '' }}" required autofocus maxlength="255" autocomplete="email" class="input-field w-full rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm text-white outline-none focus:border-blue-500">
                </div>
                <button type="submit" class="btn-primary w-full rounded-xl py-3 text-sm font-semibold">{{ session('status') ? 'Kirim Ulang Tautan Reset Password' : 'Kirim Tautan Reset Password' }}</button>
                <p class="text-xs text-slate-400">Periksa folder Spam/Junk. Untuk mengirim ulang, gunakan form yang sama dan tunggu setidaknya 60 detik. Gunakan tautan dari email terbaru.</p>
            </form>
            <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-blue-400 hover:text-blue-300">Kembali ke Login</a></p>
        </section>
    </main>
</body>
</html>
