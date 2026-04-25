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
                                            <!-- Approve Button -->
                                            <button type="button" onclick="document.getElementById('approve-modal-{{ $booking->id }}').classList.remove('hidden')"
                                                    class="w-full px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/40
                                                           border border-emerald-600/30 hover:border-emerald-500/50
                                                           text-xs font-semibold text-emerald-300 hover:text-emerald-200
                                                           transition-all cursor-pointer text-center">
                                                ✓ Approve
                                            </button>

                                            <!-- Reject Button -->
                                            <button type="button" onclick="document.getElementById('reject-modal-{{ $booking->id }}').classList.remove('hidden')"
                                                    class="w-full px-3 py-1.5 rounded-lg bg-red-600/20 hover:bg-red-600/40
                                                           border border-red-600/30 hover:border-red-500/50
                                                           text-xs font-semibold text-red-400 hover:text-red-300
                                                           transition-all cursor-pointer text-center">
                                                ✕ Reject
                                            </button>
                                        </div>

                                        <!-- Approve Modal -->
                                        <div id="approve-modal-{{ $booking->id }}" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm transition-opacity text-left">
                                            <div class="bg-slate-800 border border-slate-700/50 rounded-2xl p-6 shadow-2xl max-w-md w-full mx-4 transform transition-all">
                                                <h3 class="text-xl font-bold text-white mb-2">Approve Booking #{{ $booking->id }}</h3>
                                                <p class="text-sm text-slate-400 mb-6">Are you sure you want to approve this booking for <strong>{{ $booking->user->name }}</strong>? This action will notify the user.</p>
                                                
                                                <form action="{{ route('admin.bookings.status', $booking) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="approved">
                                                    
                                                    <div class="mb-6">
                                                        <label class="block text-sm font-medium text-slate-300 mb-1">Approval Note <span class="text-slate-500">(Optional)</span></label>
                                                        <input type="text" name="notes"
                                                               class="w-full rounded-xl border border-slate-700 focus:border-emerald-500 bg-slate-900/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 transition-colors outline-none"
                                                               placeholder="E.g., Please ensure to lock the door after use.">
                                                    </div>

                                                    <div class="flex justify-end gap-3 mt-6">
                                                        <button type="button" onclick="document.getElementById('approve-modal-{{ $booking->id }}').classList.add('hidden')"
                                                                class="px-4 py-2 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-sm font-semibold text-white transition-all shadow-lg shadow-emerald-600/20">
                                                            Confirm Approve
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- Reject Modal -->
                                        <div id="reject-modal-{{ $booking->id }}" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/80 backdrop-blur-sm transition-opacity text-left">
                                            <div class="bg-slate-800 border border-slate-700/50 rounded-2xl p-6 shadow-2xl max-w-md w-full mx-4 transform transition-all">
                                                <h3 class="text-xl font-bold text-white mb-2">Reject Booking #{{ $booking->id }}</h3>
                                                <p class="text-sm text-slate-400 mb-4">Please provide a reason for rejecting this booking. This will be visible to the user.</p>
                                                
                                                <form action="{{ route('admin.bookings.status', $booking) }}" method="POST">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="rejected">
                                                    
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-slate-300 mb-1">Rejection Note <span class="text-red-400">*</span></label>
                                                        <textarea name="notes" required rows="3"
                                                                  class="w-full rounded-xl border border-slate-700 focus:border-red-500 bg-slate-900/50 px-4 py-3 text-sm text-white placeholder:text-slate-500 transition-colors outline-none"
                                                                  placeholder="E.g., Room is under maintenance or booked for an internal event."></textarea>
                                                    </div>
                                                    
                                                    <div class="flex justify-end gap-3 mt-6">
                                                        <button type="button" onclick="document.getElementById('reject-modal-{{ $booking->id }}').classList.add('hidden')"
                                                                class="px-4 py-2 rounded-xl text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700/50 transition-colors">
                                                            Cancel
                                                        </button>
                                                        <button type="submit"
                                                                class="px-4 py-2 rounded-xl bg-red-600 hover:bg-red-500 text-sm font-semibold text-white transition-all shadow-lg shadow-red-600/20">
                                                            Confirm Reject
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
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
