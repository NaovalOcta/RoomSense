<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description"
        content="RoomSense — Campus Room Booking System. Book labs, seminar rooms, and lecture halls with ease.">

    <title>@yield('title', 'RoomSense') — RoomSense</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js (loaded AFTER Vite bundle so window.Echo exists when Alpine initialises) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Consistent background grid matching auth pages */
        .bg-grid {
            background-image:
                linear-gradient(rgba(148, 163, 184, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Nav link active underline */
        .nav-link-active {
            position: relative;
        }
        .nav-link-active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            right: 50%;
            height: 2px;
            background: #3b82f6;
            border-radius: 2px;
            transition: left 0.2s, right 0.2s;
        }

        /* Page content fade-in */
        @keyframes page-in {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .page-animate { animation: page-in 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }

        /* Alert auto-dismiss */
        [data-auto-dismiss] {
            animation: slide-in 0.3s ease forwards;
        }
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(12px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
</head>

<body class="flex flex-col min-h-screen bg-slate-950 bg-grid text-white">

    <!-- ── Navigation Bar ── -->
    <nav x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between gap-4">

                <!-- Brand -->
                <a href="{{ auth()->check() ? (auth()->user()->is_admin ? route('admin.bookings.index') : route('dashboard')) : route('login') }}"
                    class="flex items-center gap-2.5 font-bold text-white text-lg tracking-tight flex-shrink-0 group">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-600/30 group-hover:shadow-blue-500/40 transition-shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M2 3h20v4H2V3zm2 5v13h3V8H4zm6 0v13h4V8h-4zm7 0v13h3V8h-3z" />
                        </svg>
                    </div>
                    <span>Room<span class="text-blue-400">Sense</span></span>
                </a>

                <!-- Nav Links (Authenticated) -->
                @auth
                <div class="hidden md:flex items-center gap-1">
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.bookings.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                  {{ request()->routeIs('admin.bookings.*')
                                      ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Booking Approvals
                        </a>
                        <a href="{{ route('admin.rooms.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                  {{ request()->routeIs('admin.rooms.*')
                                      ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Manage Rooms
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                  {{ request()->routeIs('dashboard')
                                      ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            My Bookings
                        </a>
                        <a href="{{ route('rooms.index') }}"
                            class="px-3 py-2 rounded-lg text-sm font-medium transition-all duration-150
                                  {{ request()->routeIs('rooms.*')
                                      ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                      : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                            Browse Rooms
                        </a>
                    @endif
                </div>
                @endauth

                <!-- Right Side -->
                <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                    @auth
                        <!-- Notification Bell -->
                        <x-notification-bell />

                        <!-- User Info -->
                        <div class="hidden sm:flex items-center gap-2.5">
                            <div
                                class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-slate-600 to-slate-700 text-xs font-bold text-slate-200 uppercase ring-1 ring-slate-600">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="hidden lg:block">
                                <p class="text-sm font-medium text-slate-200 leading-none">{{ auth()->user()->name }}</p>
                                @if (auth()->user()->is_admin)
                                    <p class="text-xs text-amber-400 mt-0.5">Administrator</p>
                                @else
                                    <p class="text-xs text-slate-500 mt-0.5">Mahasiswa</p>
                                @endif
                            </div>
                            @if (auth()->user()->is_admin)
                                <span
                                    class="hidden sm:inline-flex lg:hidden px-1.5 py-0.5 rounded text-xs font-semibold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                                    Admin
                                </span>
                            @endif
                        </div>

                        <!-- Logout (Desktop) -->
                        <form action="{{ route('logout') }}" method="POST" class="hidden md:block">
                            @csrf
                            <button type="submit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-all border border-slate-800 hover:border-slate-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                <span class="hidden sm:inline">Keluar</span>
                            </button>
                        </form>

                        <!-- Hamburger Button (Mobile) -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" 
                                aria-label="Open main menu"
                                class="md:hidden inline-flex items-center justify-center p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition-colors focus:outline-none">
                            <svg x-show="!mobileMenuOpen" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg x-show="mobileMenuOpen" style="display:none;" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-all border border-slate-800 hover:border-slate-700">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}"
                            class="px-3 py-1.5 rounded-lg text-xs font-medium text-white bg-blue-600 hover:bg-blue-500 transition-all shadow-lg shadow-blue-600/20">
                            Daftar
                        </a>
                    @endauth
                </div>

            </div>
        </div>

        <!-- ── Mobile Menu Panel ── -->
        @auth
        <div x-show="mobileMenuOpen" 
             style="display: none;" 
             x-transition:enter="transition ease-out duration-100" 
             x-transition:enter-start="opacity-0 -translate-y-2" 
             x-transition:enter-end="opacity-100 translate-y-0"
             class="md:hidden border-t border-slate-800/80 bg-slate-950/95 backdrop-blur-md">
            <div class="px-4 py-3 space-y-1.5">
                @if (auth()->user()->is_admin)
                    <a href="{{ route('admin.bookings.index') }}"
                        class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                              {{ request()->routeIs('admin.bookings.*')
                                  ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                  : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        Booking Approvals
                    </a>
                    <a href="{{ route('admin.rooms.index') }}"
                        class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                              {{ request()->routeIs('admin.rooms.*')
                                  ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                  : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        Manage Rooms
                    </a>
                @else
                    <a href="{{ route('dashboard') }}"
                        class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                              {{ request()->routeIs('dashboard')
                                  ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                  : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        My Bookings
                    </a>
                    <a href="{{ route('rooms.index') }}"
                        class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                              {{ request()->routeIs('rooms.*')
                                  ? 'bg-blue-600/15 text-blue-300 ring-1 ring-blue-500/20'
                                  : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        Browse Rooms
                    </a>
                @endif

                <!-- Logout for Mobile -->
                <div class="border-t border-slate-800/80 pt-2.5 mt-2.5">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-sm font-medium text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 transition-colors text-left">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Keluar Akun
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endauth
    </nav>

    <!-- ── Flash Alerts ── -->
    @if (session('success') || session('error') || session('warning') || $errors->any())
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 pt-4 space-y-2">
            @if (session('success'))
                <x-alert type="success" :message="session('success')" />
            @endif
            @if (session('warning'))
                <x-alert type="warning" :message="session('warning')" />
            @endif
            @if (session('error'))
                <x-alert type="error" :message="session('error')" />
            @endif
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <x-alert type="error" :message="$error" />
                @endforeach
            @endif
        </div>
    @endif

    <!-- ── Main Content ── -->
    <main class="flex-1 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-8 page-animate">
        @yield('content')
    </main>

    <!-- ── Footer ── -->
    <footer class="border-t border-slate-800/60 py-5 mt-4">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div class="flex items-center gap-2 text-xs text-slate-600">
                <div class="flex h-5 w-5 items-center justify-center rounded bg-blue-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2 3h20v4H2V3zm2 5v13h3V8H4zm6 0v13h4V8h-4zm7 0v13h3V8h-3z" />
                    </svg>
                </div>
                <span>&copy; {{ date('Y') }} RoomSense</span>
            </div>
            <p class="text-xs text-slate-700">Universitas Muhammadiyah Malang</p>
        </div>
    </footer>

</body>

</html>
