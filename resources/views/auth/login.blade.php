<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Login to RoomSense — Campus Room Booking System.">
    <title>Login — RoomSense</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full flex items-center justify-center bg-slate-950 px-4">

    <!-- Background decoration -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none" aria-hidden="true">
        <div class="absolute -top-40 -right-40 w-80 h-80 rounded-full bg-blue-600/10 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 rounded-full bg-indigo-600/10 blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative">

        <!-- Logo / Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-2xl shadow-blue-600/40 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M2 3h20v4H2V3zm2 5v13h3V8H4zm6 0v13h4V8h-4zm7 0v13h3V8h-3z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white tracking-tight">RoomSense</h1>
            <p class="mt-1 text-sm text-slate-400">Campus Room Booking System</p>
        </div>

        <!-- Login Card -->
        <div class="bg-slate-800/50 backdrop-blur border border-slate-700/50 rounded-2xl p-8 shadow-2xl">
            <h2 class="text-lg font-semibold text-white mb-6">Sign in to your account</h2>

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Email address
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                               placeholder:text-slate-500 transition-colors outline-none
                               {{ $errors->has('email') ? 'border-red-500 focus:border-red-400' : 'border-slate-700 focus:border-blue-500' }}"
                        placeholder="you@example.com"
                    >
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Password
                    </label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-slate-700 focus:border-blue-500
                               bg-slate-900/50 px-4 py-3 text-sm text-white
                               placeholder:text-slate-500 transition-colors outline-none"
                        placeholder="••••••••"
                    >
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember"
                           class="h-4 w-4 rounded border-slate-600 bg-slate-700 text-blue-600 cursor-pointer">
                    <label for="remember" class="text-sm text-slate-400 cursor-pointer">Remember me</label>
                </div>

                <!-- Submit -->
                <button type="submit"
                        class="w-full rounded-xl bg-blue-600 hover:bg-blue-500 py-3 text-sm font-semibold
                               text-white transition-all duration-200 shadow-lg shadow-blue-600/20
                               hover:shadow-blue-500/30 hover:-translate-y-0.5 active:translate-y-0">
                    Sign in
                </button>
            </form>

            <!-- Demo credentials hint -->
            <div class="mt-6 pt-5 border-t border-slate-700/50">
                <p class="text-xs text-center text-slate-500 mb-3">Demo credentials</p>
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-400">
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="font-semibold text-slate-300 mb-1">Admin</p>
                        <p>admin@roomsense.com</p>
                        <p class="text-slate-500">password</p>
                    </div>
                    <div class="bg-slate-900/50 rounded-lg p-3">
                        <p class="font-semibold text-slate-300 mb-1">User</p>
                        <p>alice@roomsense.com</p>
                        <p class="text-slate-500">password</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
