@extends('layouts.app')
@section('title', 'Book a Room')

@section('content')

    <!-- Back link -->
    <div class="mb-6">
        <a href="{{ route('rooms.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-400 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
            </svg>
            Browse Rooms
        </a>
    </div>

    <div class="max-w-2xl">

        <div class="mb-8">
            <h1 class="text-2xl font-bold text-white">Book a Room</h1>
            <p class="mt-1 text-slate-400">Fill in the form below to submit a booking request.</p>
        </div>

        <!-- Validation errors summary -->
        @if($errors->any())
            <x-alert type="error" message="Please fix the errors below before submitting." />
        @endif

        <!-- Suggested Alternatives -->
        @if(session('suggestions') && collect(session('suggestions'))->isNotEmpty())
            <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-6">
                <div class="flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <h3 class="text-lg font-bold text-emerald-400">Available Alternatives</h3>
                </div>
                <p class="text-sm text-emerald-200/70 mb-4">The room you selected is unavailable, but these rooms fit your capacity and are free for your selected time:</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach(session('suggestions') as $suggestion)
                        <div class="bg-slate-900/50 border border-emerald-500/20 rounded-xl p-4 cursor-pointer hover:bg-slate-800 hover:border-emerald-500/50 transition-all duration-200 group"
                             onclick="document.getElementById('room_id').value = '{{ $suggestion->id }}'; window.scrollTo({top: 0, behavior: 'smooth'});">
                            <h4 class="font-bold text-white group-hover:text-emerald-400 transition-colors">{{ $suggestion->name }}</h4>
                            <p class="text-xs text-slate-400 mt-1">{{ $suggestion->capacity }} seats @if($suggestion->building) • {{ $suggestion->building }}@endif</p>
                            <span class="text-xs font-semibold text-emerald-500 mt-3 inline-block">Select Room &rarr;</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <form action="{{ route('bookings.store') }}" method="POST"
              class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 space-y-6">
            @csrf

            <!-- Room Selection -->
            <div>
                <label for="room_id" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Select Room <span class="text-red-400">*</span>
                </label>
                <select name="room_id" id="room_id" required
                        class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                               transition-colors outline-none cursor-pointer
                               {{ $errors->has('room_id') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
                    <option value="" disabled {{ old('room_id', $selectedRoom?->id) ? '' : 'selected' }}>— Choose a room —</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}"
                                {{ old('room_id', $selectedRoom?->id) == $room->id ? 'selected' : '' }}>
                            {{ $room->name }} ({{ $room->capacity }} seats)
                            @if($room->building) — {{ $room->building }}@endif
                        </option>
                    @endforeach
                </select>
                @error('room_id')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Date -->
            <div>
                <label for="date" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Date <span class="text-red-400">*</span>
                </label>
                <input type="date" id="date" name="date"
                       value="{{ old('date', now()->format('Y-m-d')) }}"
                       min="{{ now()->format('Y-m-d') }}"
                       required
                       class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                              transition-colors outline-none
                              {{ $errors->has('date') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
                @error('date')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Time Range -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-sm font-medium text-slate-300 mb-1.5">
                        Start Time <span class="text-red-400">*</span>
                    </label>
                    <input type="time" id="start_time" name="start_time"
                           value="{{ old('start_time', '08:00') }}"
                           required
                           class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                                  transition-colors outline-none
                                  {{ $errors->has('start_time') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
                    @error('start_time')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="end_time" class="block text-sm font-medium text-slate-300 mb-1.5">
                        End Time <span class="text-red-400">*</span>
                    </label>
                    <input type="time" id="end_time" name="end_time"
                           value="{{ old('end_time', '10:00') }}"
                           required
                           class="w-full rounded-xl border bg-slate-900/50 px-4 py-3 text-sm text-white
                                  transition-colors outline-none
                                  {{ $errors->has('end_time') ? 'border-red-500' : 'border-slate-700 focus:border-blue-500' }}">
                    @error('end_time')
                        <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Purpose -->
            <div>
                <label for="purpose" class="block text-sm font-medium text-slate-300 mb-1.5">
                    Purpose <span class="text-slate-500">(optional)</span>
                </label>
                <input type="text" id="purpose" name="purpose"
                       value="{{ old('purpose') }}"
                       maxlength="255"
                       placeholder="e.g., Group study, Presentation, Lecture..."
                       class="w-full rounded-xl border border-slate-700 focus:border-blue-500
                              bg-slate-900/50 px-4 py-3 text-sm text-white
                              placeholder:text-slate-500 transition-colors outline-none">
                @error('purpose')
                    <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info box -->
            <div class="flex items-start gap-3 rounded-xl bg-blue-500/10 border border-blue-500/30 p-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <p class="text-xs text-blue-300 leading-relaxed">
                    Booking requests are reviewed by an admin. You'll see the status update in your
                    <a href="{{ route('dashboard') }}" class="underline underline-offset-2 hover:text-blue-200">My Bookings</a> dashboard.
                </p>
            </div>

            <!-- Submit -->
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 rounded-xl bg-blue-600 hover:bg-blue-500 py-3 text-sm font-semibold
                               text-white transition-all duration-200 shadow-lg shadow-blue-600/20">
                    Submit Booking Request
                </button>
                <a href="{{ route('rooms.index') }}"
                   class="px-6 rounded-xl border border-slate-700 hover:border-slate-500
                          bg-slate-800/50 hover:bg-slate-700 text-sm font-medium text-slate-300
                          hover:text-white transition-all duration-200 flex items-center">
                    Cancel
                </a>
            </div>
        </form>
    </div>

@endsection
