@extends('layouts.app')
@section('title', 'My Bookings')

@section('content')

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">My Bookings</h1>
            <p class="mt-1 text-slate-400">Track and manage your room reservations.</p>
        </div>
        <a href="{{ route('bookings.create') }}"
           class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5
                  text-sm font-semibold text-white transition-all duration-200 shadow-lg shadow-blue-600/20 self-start sm:self-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Book a Room
        </a>
    </div>

    @if($bookings->isEmpty())
        <!-- Empty State -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-16 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-700 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-300 mb-2">No bookings yet</h3>
            <p class="text-slate-500 mb-6">You haven't made any room bookings. Start by browsing available rooms.</p>
            <a href="{{ route('rooms.index') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-500 px-5 py-2.5
                      text-sm font-semibold text-white transition-colors">
                Browse Rooms
            </a>
        </div>
    @else
        <!-- Bookings Table -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-700/50">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Room</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Time</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Purpose</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/30">
                        @foreach($bookings as $booking)
                            <tr class="hover:bg-slate-700/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-white">{{ $booking->room->name }}</p>
                                        @if($booking->room->building)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $booking->room->building }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                    {{ $booking->start_time->format('D, M j, Y') }}
                                </td>
                                <td class="px-6 py-4 text-slate-300 whitespace-nowrap">
                                    {{ $booking->start_time->format('g:i A') }} – {{ $booking->end_time->format('g:i A') }}
                                </td>
                                <td class="px-6 py-4 text-slate-400 max-w-xs truncate">
                                    {{ $booking->purpose ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <x-badge :status="$booking->status" />

                                    @if($booking->notes)
                                        <p class="text-xs text-slate-500 mt-1 max-w-xs truncate" title="{{ $booking->notes }}">
                                            Note: {{ $booking->notes }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($booking->isPending())
                                        <form id="cancel-form-{{ $booking->id }}"
                                              action="{{ route('bookings.destroy', $booking) }}"
                                              method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    data-confirm="Cancel this booking request? This action cannot be undone."
                                                    class="text-xs text-red-400 hover:text-red-300 font-medium transition-colors cursor-pointer">
                                                Cancel
                                            </button>
                                        </form>
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
        </div>
    @endif

@endsection
