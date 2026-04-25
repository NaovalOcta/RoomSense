@extends('layouts.app')
@section('title', 'Booking Approvals')

@section('content')

    <!-- Page Header + Stats -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Booking Approvals</h1>
        <p class="mt-1 text-slate-400">Review and manage room booking requests.</p>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pending</p>
            <p class="text-3xl font-bold text-amber-400 mt-1">{{ $pendingCount }}</p>
        </div>
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Approved</p>
            <p class="text-3xl font-bold text-emerald-400 mt-1">{{ $approvedCount }}</p>
        </div>
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-xl p-5">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rejected</p>
            <p class="text-3xl font-bold text-red-400 mt-1">{{ $rejectedCount }}</p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 border-b border-slate-700/50 pb-4">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.bookings.index', ['status' => $key]) }}"
               class="px-4 py-2 rounded-lg text-sm font-medium transition-colors
                      {{ $status === $key
                          ? 'bg-blue-600/20 text-blue-300 border border-blue-500/30'
                          : 'text-slate-400 hover:text-white hover:bg-slate-700/50' }}">
                {{ $label }}
                @if($key === 'pending' && $pendingCount > 0)
                    <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-400">
                        {{ $pendingCount }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <!-- Bookings Table -->
    <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden">
        @if($bookings->isEmpty())
            <div class="p-16 text-center">
                <p class="text-slate-500">No bookings found for the selected filter.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($bookings as $booking)
                            <tr class="hover:bg-slate-700/20 transition-colors {{ $booking->isPending() ? 'bg-amber-500/5' : '' }}">
                                <td class="px-6 py-4 text-slate-500 text-xs">#{{ $booking->id }}</td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-slate-200">{{ $booking->user->name }}</p>
                                        <p class="text-xs text-slate-500">{{ $booking->user->email }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-white">{{ $booking->room->name }}</p>
                                        @if($booking->room->building)
                                            <p class="text-xs text-slate-500">{{ $booking->room->building }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                    <p>{{ $booking->start_time->format('M j, Y') }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $booking->start_time->format('g:i A') }} – {{ $booking->end_time->format('g:i A') }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-slate-400 max-w-xs truncate">
                                    {{ $booking->purpose ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <x-badge :status="$booking->status" />
                                    @if($booking->notes)
                                        <p class="text-xs text-slate-500 mt-1 max-w-[140px] truncate" title="{{ $booking->notes }}">
                                            {{ $booking->notes }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($booking->isPending())
                                        <div class="flex flex-col gap-1.5">
                                            <!-- Approve -->
                                            <form action="{{ route('admin.bookings.status', $booking) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button type="submit"
                                                        data-confirm="Approve booking #{{ $booking->id }} for {{ $booking->user->name }}?"
                                                        class="w-full px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/40
                                                               border border-emerald-600/30 hover:border-emerald-500/50
                                                               text-xs font-semibold text-emerald-300 hover:text-emerald-200
                                                               transition-all cursor-pointer">
                                                    ✓ Approve
                                                </button>
                                            </form>

                                            <!-- Reject -->
                                            <form action="{{ route('admin.bookings.status', $booking) }}" method="POST">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button type="submit"
                                                        data-confirm="Reject booking #{{ $booking->id }}? The user will see 'Rejected' status."
                                                        class="w-full px-3 py-1.5 rounded-lg bg-red-600/20 hover:bg-red-600/40
                                                               border border-red-600/30 hover:border-red-500/50
                                                               text-xs font-semibold text-red-400 hover:text-red-300
                                                               transition-all cursor-pointer">
                                                    ✕ Reject
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($bookings->hasPages())
                <div class="px-6 py-4 border-t border-slate-700/50">
                    {{ $bookings->links() }}
                </div>
            @endif
        @endif
    </div>

@endsection
