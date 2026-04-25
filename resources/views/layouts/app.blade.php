<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="RoomSense — Campus Room Booking System. Book labs, seminar rooms, and lecture halls with ease.">

    <title>@yield('title', 'RoomSense') — Campus Room Booking</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col min-h-screen">

    <!-- ── Navigation Bar ── -->
    <nav class="sticky top-0 z-50 border-b border-slate-700/50 bg-slate-900/80 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">

                <!-- Brand -->
                <a href="{{ auth()->user()->is_admin ? route('admin.bookings.index') : route('dashboard') }}"
                    class="flex items-center gap-2.5 font-bold text-white text-lg tracking-tight">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-white shadow-lg shadow-blue-600/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 3h20v4H2V3zm2 5v13h3V8H4zm6 0v13h4V8h-4zm7 0v13h3V8h-3z" />
                        </svg>
                    </div>
                    <span>Room<span class="text-blue-400">Sense</span></span>
                </a>

                <!-- Nav Links -->
                <div class="hidden md:flex items-center gap-1">
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.bookings.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs('admin.bookings.*') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }}">
                            Booking Approvals
                        </a>
                        <a href="{{ route('admin.rooms.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs('admin.rooms.*') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }}">
                            Manage Rooms
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs('dashboard') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }}">
                            My Bookings
                        </a>
                        <a href="{{ route('rooms.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors
                                  {{ request()->routeIs('rooms.*') ? 'bg-blue-600/20 text-blue-300' : 'text-slate-300 hover:text-white hover:bg-slate-700/50' }}">
                            Browse Rooms
                        </a>
                        <a href="{{ route('bookings.create') }}"
                            class="ml-1 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold transition-colors shadow-lg shadow-blue-600/20">
                            + Book a Room
                        </a>
                    @endif
                </div>

                <!-- User Menu -->
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-xs font-bold text-slate-200 uppercase">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <span class="text-sm text-slate-300">{{ auth()->user()->name }}</span>
                        @if (auth()->user()->is_admin)
                            <span
                                class="px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                Admin
                            </span>
                        @endif
                    </div>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-700/50 transition-colors border border-slate-700 hover:border-slate-500">
                            Logout
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </nav>

    <!-- ── Flash Alerts ── -->
    @if (session('success') || session('error'))
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-4">
            @if (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif
            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif
            @if ($errors->any())
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-4">
                    @foreach ($errors->all() as $error)
                        <x-alert type="error" :message="$error" />
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- ── Main Content ── -->
    <main class="flex-1 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        @yield('content')
    </main>

    <!-- ── Footer ── -->
    <footer class="border-t border-slate-800 py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-600">
            &copy; {{ date('Y') }} RoomSense — Campus Room Booking System
        </div>
    </footer>

</body>

</html>
