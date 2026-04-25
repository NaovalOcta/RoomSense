@extends('layouts.app')
@section('title', 'Browse Rooms')

@section('content')

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-white">Available Rooms</h1>
        <p class="mt-1 text-slate-400">Browse campus rooms and book a space for your needs.</p>
    </div>

    <!-- Search and Filter -->
    <form action="{{ route('rooms.index') }}" method="GET" class="mb-8 flex flex-col sm:flex-row gap-4">
        <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search rooms by name or building..." 
                   class="w-full pl-10 pr-4 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
        </div>
        <div class="w-full sm:w-48">
            <select name="capacity" class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-xl text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors appearance-none">
                <option value="">Any Capacity</option>
                <option value="10" {{ request('capacity') == '10' ? 'selected' : '' }}>10+ Seats</option>
                <option value="20" {{ request('capacity') == '20' ? 'selected' : '' }}>20+ Seats</option>
                <option value="50" {{ request('capacity') == '50' ? 'selected' : '' }}>50+ Seats</option>
                <option value="100" {{ request('capacity') == '100' ? 'selected' : '' }}>100+ Seats</option>
            </select>
        </div>
        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-medium transition-colors shadow-lg shadow-blue-600/20">
            Filter
        </button>
        @if(request()->hasAny(['search', 'capacity']))
            <a href="{{ route('rooms.index') }}" class="px-6 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-xl font-medium transition-colors text-center">
                Clear
            </a>
        @endif
    </form>

    @if($rooms->isEmpty())
        <div class="text-center py-20">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-800 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                </svg>
            </div>
            <h3 class="text-slate-300 font-medium">No rooms found</h3>
            <p class="text-slate-500 text-sm mt-1">Try adjusting your search or filters.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach($rooms as $room)
                <div class="group relative flex flex-col bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden
                            hover:border-blue-500/50 hover:bg-slate-800 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-blue-600/10">

                    <!-- Room colour strip -->
                    <div class="h-1.5 w-full bg-gradient-to-r from-blue-600 to-indigo-600 opacity-60 group-hover:opacity-100 transition-opacity"></div>

                    <div class="p-6 flex flex-col flex-1">

                        <!-- Icon + Name + Status -->
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-blue-600/20 text-blue-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-white group-hover:text-blue-300 transition-colors leading-tight">
                                        {{ $room->name }}
                                    </h3>
                                    @if($room->building)
                                        <p class="text-xs text-slate-500 mt-0.5">{{ $room->building }}</p>
                                    @endif
                                </div>
                            </div>
                            <!-- Status Badge -->
                            <div>
                                @php
                                    $status = $room->current_status;
                                    $statusColors = [
                                        'available' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                        'booked' => 'bg-red-500/10 text-red-400 border-red-500/20',
                                        'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                    ];
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium border {{ $statusColors[$status] }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Stats -->
                        <div class="flex items-center gap-4 mb-4">
                            <div class="flex items-center gap-1.5 text-slate-400 text-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                <span>{{ $room->capacity }} seats</span>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($room->description)
                            <p class="text-sm text-slate-400 leading-relaxed flex-1 line-clamp-2">
                                {{ $room->description }}
                            </p>
                        @endif

                        <!-- Actions -->
                        <div class="flex gap-2 mt-5 pt-4 border-t border-slate-700/50">
                            <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}"
                               class="flex-1 text-center rounded-xl bg-blue-600 hover:bg-blue-500 py-2.5 text-sm font-semibold
                                      text-white transition-all duration-200 hover:shadow-lg hover:shadow-blue-600/20">
                                Book Now
                            </a>
                            <a href="{{ route('rooms.show', $room) }}"
                               class="px-4 rounded-xl border border-slate-700 hover:border-slate-500 bg-slate-800/50
                                      hover:bg-slate-700 text-sm font-medium text-slate-300 hover:text-white
                                      transition-all duration-200 flex items-center">
                                Details
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

@endsection
