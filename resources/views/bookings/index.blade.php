@extends('layouts.app')
@section('title', 'My Bookings')

@section('content')

    {{-- ── Page Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            {{-- Greeting --}}
            <p class="text-xs font-semibold text-blue-400 uppercase tracking-widest mb-1">
                @php
                    $hour = now()->hour;
                    $greeting = $hour < 12 ? 'Selamat Pagi' : ($hour < 17 ? 'Selamat Siang' : 'Selamat Malam');
                @endphp
                {{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }} 👋
            </p>
            <h1 class="text-2xl font-bold text-white">Peminjaman Saya</h1>
            <p class="mt-1 text-sm text-slate-400">Kelola dan pantau status peminjaman ruangan Anda.</p>
        </div>
        <a href="{{ route('bookings.create') }}"
           class="inline-flex justify-center w-full sm:w-auto items-center gap-2 rounded-xl bg-blue-600
                  hover:bg-blue-500 px-5 py-2.5
                  text-sm font-semibold text-white transition-all duration-200
                  shadow-lg shadow-blue-600/25 hover:shadow-blue-500/35 hover:-translate-y-0.5 active:translate-y-0
                  flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Pinjam Ruangan
        </a>
    </div>

    {{-- ── Summary Stats ── --}}
    @php
        $total    = $bookings->total();
        $pending  = $bookings->getCollection()->filter(fn($b) => $b->status === 'pending')->count();
        $approved = $bookings->getCollection()->filter(fn($b) => $b->status === 'approved')->count();
        $rejected = $bookings->getCollection()->filter(fn($b) => $b->status === 'rejected')->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-8">
        {{-- Total --}}
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-700/60">
                    <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-white">{{ $bookings->total() }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Total Peminjaman</p>
        </div>

        {{-- Pending --}}
        <div class="bg-slate-800/50 border border-amber-500/20 rounded-2xl p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-500/15">
                    <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-amber-400">{{ $pending }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Menunggu</p>
        </div>

        {{-- Approved --}}
        <div class="bg-slate-800/50 border border-emerald-500/20 rounded-2xl p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-emerald-400">{{ $approved }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Disetujui</p>
        </div>

        {{-- Rejected --}}
        <div class="bg-slate-800/50 border border-red-500/20 rounded-2xl p-4 backdrop-blur-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500/15">
                    <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-red-400">{{ $rejected }}</p>
            <p class="text-xs text-slate-500 mt-0.5">Ditolak</p>
        </div>
    </div>

    @if($bookings->isEmpty())
        {{-- ── Empty State ── --}}
        <div class="bg-slate-800/40 border border-slate-700/40 rounded-2xl p-16 text-center backdrop-blur-sm">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-700/60 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-200 mb-2">Belum ada peminjaman</h3>
            <p class="text-sm text-slate-500 mb-6 max-w-sm mx-auto">Anda belum memiliki peminjaman ruangan. Mulai dengan menjelajahi ruangan yang tersedia.</p>
            <a href="{{ route('rooms.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5
                      text-sm font-semibold text-white transition-all shadow-lg shadow-blue-600/25">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Jelajahi Ruangan
            </a>
        </div>
    @else
        {{-- ── Bookings Table ── --}}
        <div class="bg-slate-800/40 border border-slate-700/50 rounded-2xl overflow-hidden backdrop-blur-sm">

            {{-- Table header info --}}
            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                <p class="text-sm font-medium text-slate-300">
                    Riwayat Peminjaman
                    <span class="ml-2 px-2 py-0.5 rounded-full text-xs bg-slate-700 text-slate-400">{{ $bookings->total() }} total</span>
                </p>
            </div>

            {{-- Desktop View (Table) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/40">
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Ruangan</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Waktu</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Keperluan</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($bookings as $booking)
                            <tr class="group hover:bg-slate-700/20 transition-all duration-150 {{ $booking->isPending() ? 'bg-amber-500/[0.03]' : '' }}">
                                {{-- Room --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-700/60 group-hover:bg-slate-700 transition-colors">
                                            <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-medium text-white">{{ $booking->room->name }}</p>
                                            @if($booking->room->building)
                                                <p class="text-xs text-slate-500 mt-0.5">{{ $booking->room->building }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Date --}}
                                <td class="px-6 py-4">
                                    <p class="text-slate-300 whitespace-nowrap">{{ $booking->start_time->format('D, j M Y') }}</p>
                                </td>

                                {{-- Time --}}
                                <td class="px-6 py-4">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-700/50 text-xs text-slate-300 whitespace-nowrap">
                                        <svg class="h-3 w-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $booking->start_time->format('g:i A') }} – {{ $booking->end_time->format('g:i A') }}
                                    </div>
                                </td>

                                {{-- Purpose --}}
                                <td class="px-6 py-4 text-slate-400 max-w-[180px]">
                                    <p class="truncate" title="{{ $booking->purpose }}">{{ $booking->purpose ?? '—' }}</p>
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4">
                                    <x-badge :status="$booking->status" />
                                    @if($booking->notes)
                                        <p class="text-xs text-slate-600 mt-1.5 max-w-[140px] truncate" title="{{ $booking->notes }}">
                                            {{ $booking->notes }}
                                        </p>
                                    @endif
                                </td>

                                {{-- Action --}}
                                <td class="px-6 py-4">
                                    @if($booking->isPending())
                                        <form id="cancel-form-{{ $booking->id }}"
                                              action="{{ route('bookings.destroy', $booking) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    data-confirm="Cancel this booking request? This action cannot be undone."
                                                    class="inline-flex items-center gap-1 text-xs font-medium text-red-400 hover:text-red-300
                                                           bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 hover:border-red-500/30
                                                           px-2.5 py-1 rounded-lg transition-all cursor-pointer">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                                Batalkan
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-slate-700">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile View (Card List) --}}
            <div class="md:hidden divide-y divide-slate-700/30">
                @foreach($bookings as $booking)
                    <div class="p-4 flex flex-col gap-3.5 transition-colors {{ $booking->isPending() ? 'bg-amber-500/[0.03]' : '' }}">
                        {{-- Card Header: Room & Status --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-700/60 border border-slate-600/30 flex-shrink-0">
                                    <svg class="h-4.5 w-4.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-white text-base">{{ $booking->room->name }}</h4>
                                    @if($booking->room->building)
                                        <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $booking->room->building }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <x-badge :status="$booking->status" />
                            </div>
                        </div>

                        {{-- Card Details: Time & Purpose --}}
                        <div class="space-y-2">
                            {{-- Date & Time --}}
                            <div class="flex flex-wrap gap-2 text-xs">
                                <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-800 border border-slate-700/50 rounded-lg text-slate-300">
                                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5"/>
                                    </svg>
                                    {{ $booking->start_time->format('D, j M Y') }}
                                </div>
                                <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-800 border border-slate-700/50 rounded-lg text-slate-300">
                                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $booking->start_time->format('g:i A') }} – {{ $booking->end_time->format('g:i A') }}
                                </div>
                            </div>
                            
                            {{-- Purpose --}}
                            @if($booking->purpose)
                                <div class="text-xs text-slate-400 flex items-start gap-2 bg-slate-800/30 p-2 rounded-lg">
                                    <span class="font-medium text-slate-500 select-none">Keperluan:</span>
                                    <span class="text-slate-300 font-light">{{ $booking->purpose }}</span>
                                </div>
                            @endif

                            {{-- Admin Notes --}}
                            @if($booking->notes)
                                <div class="text-xs text-amber-400/90 bg-amber-500/10 border border-amber-500/20 p-2.5 rounded-lg">
                                    <span class="font-bold block mb-0.5">Catatan:</span>
                                    {{ $booking->notes }}
                                </div>
                            @endif
                        </div>

                        {{-- Card Action --}}
                        @if($booking->isPending())
                            <div class="border-t border-slate-700/30 pt-3 mt-1">
                                <form id="cancel-form-mobile-{{ $booking->id }}"
                                      action="{{ route('bookings.destroy', $booking) }}"
                                      method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            data-confirm="Cancel this booking request? This action cannot be undone."
                                            class="w-full flex items-center justify-center gap-2 text-xs font-semibold text-red-400 bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 rounded-xl py-2 transition-all">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Batalkan Permintaan
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            @if($bookings->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $bookings->links() }}
                </div>
            @endif
        </div>
    @endif

@endsection
