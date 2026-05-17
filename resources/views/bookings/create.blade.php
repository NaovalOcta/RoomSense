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

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6 items-start">
            <div class="space-y-6">
                <form action="{{ route('bookings.store') }}" method="POST" id="booking-form"
                      class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 md:p-8 space-y-6">
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

            <!-- Mobile: Inline Status Bar -->
            <div id="mobile-status-bar" class="hidden lg:hidden rounded-xl px-4 py-3 text-sm font-medium items-center gap-2 mt-4 bg-slate-800/50 border border-slate-700/50">
                <span id="mobile-status-dot" class="h-2 w-2 rounded-full flex-shrink-0"></span>
                <span id="mobile-status-text"></span>
            </div>

            <!-- Mobile: Trigger Bottom Sheet -->
            <button id="mobile-sheet-trigger" type="button" class="hidden lg:hidden w-full rounded-xl border border-slate-700 bg-slate-800/50 hover:bg-slate-700 text-sm font-medium text-slate-300 hover:text-white transition-all duration-200 flex items-center justify-center gap-2 py-3 mt-3" onclick="openBottomSheet()">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Lihat jadwal &amp; detail ruangan
            </button>

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

    <!-- Right Column: Reactive Panel (Desktop) -->
    <aside id="booking-panel" class="hidden lg:flex flex-col gap-4 sticky top-6">
        <!-- State: Empty -->
        <div id="panel-empty-state" class="bg-slate-800/50 border border-slate-700/50 rounded-2xl p-8 text-center flex flex-col items-center justify-center h-[300px]">
            <div class="h-12 w-12 rounded-full bg-slate-700/50 flex items-center justify-center mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-300">Belum ada ruangan yang dipilih</p>
            <p class="text-xs text-slate-500 mt-1">Pilih ruangan dan tanggal di form untuk melihat detail dan jadwal.</p>
        </div>

        <!-- Loading State -->
        <div id="panel-loading" class="hidden bg-slate-800/50 border border-slate-700/50 rounded-2xl p-6 h-[300px] flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
        </div>

        <!-- Room Details Card -->
        <div id="room-details-card" class="hidden bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden">
           <div class="p-5">
               <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                   <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                   </svg>
                   Room Details
               </h3>
               <div id="rd-image-container" class="w-full h-32 bg-slate-700/50 rounded-xl mb-4 overflow-hidden relative flex items-center justify-center">
                   <img id="rd-image" src="" alt="Room Image" class="w-full h-full object-cover hidden">
                   <svg id="rd-no-image" class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                   </svg>
               </div>
               <h4 id="rd-name" class="font-bold text-white text-base"></h4>
               <p id="rd-location" class="text-xs text-slate-400 mt-0.5 mb-4"></p>
               
               <div class="grid grid-cols-2 gap-2 mb-4">
                   <div class="bg-slate-900/50 border border-slate-700/50 rounded-xl p-3">
                       <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider mb-1">Kapasitas</p>
                       <p id="rd-capacity" class="text-sm font-semibold text-slate-200"></p>
                   </div>
                   <div class="bg-slate-900/50 border border-slate-700/50 rounded-xl p-3">
                       <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider mb-1">Tipe Booking</p>
                       <p id="rd-approval" class="text-sm font-semibold text-slate-200"></p>
                   </div>
               </div>

               <div id="rd-facilities-container">
                   <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider mb-2">Fasilitas</p>
                   <div id="rd-facilities" class="flex flex-wrap gap-1.5">
                   </div>
               </div>
           </div>
        </div>

        <!-- Schedule Card -->
        <div id="schedule-card" class="hidden bg-slate-800/50 border border-slate-700/50 rounded-2xl overflow-hidden flex-col">
            <div class="p-5 border-b border-slate-700/50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Jadwal Ruangan
                </h3>
            </div>
            
            <div class="p-5 flex-1 overflow-hidden flex flex-col">
                <p id="schedule-date" class="text-sm font-medium text-slate-300 mb-8"></p>
                
                <div class="relative flex-1 mt-2" id="timeline-container" style="min-height: 200px;">
                    <!-- Timeline will be injected here -->
                </div>

                <div class="mt-4 pt-4 border-t border-slate-700/50 flex items-center justify-between text-xs text-slate-400">
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Approved</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Pending</div>
                    <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span>Pilihan Anda</div>
                </div>
            </div>
            
            <!-- Desktop Status Bar -->
            <div id="desk-status-bar" class="px-5 py-3 border-t border-slate-700/50 flex items-center gap-2 text-sm font-medium">
                <span id="desk-status-dot" class="h-2 w-2 rounded-full flex-shrink-0"></span>
                <span id="desk-status-text"></span>
            </div>
        </div>
    </aside>

</div>

<!-- MOBILE BOTTOM SHEET OVERLAY -->
<div id="bottom-sheet-overlay" class="fixed inset-0 z-[100] hidden lg:hidden bg-slate-900/80 backdrop-blur-sm transition-opacity opacity-0" onclick="closeBottomSheet()"></div>

<!-- MOBILE BOTTOM SHEET -->
<div id="bottom-sheet" class="fixed bottom-0 left-0 right-0 z-[101] hidden lg:hidden bg-slate-900 rounded-t-2xl max-h-[85vh] flex-col translate-y-full transition-transform duration-300 border-t border-slate-700">
    <!-- Drag handle -->
    <div class="flex justify-center pt-3 pb-2" onclick="closeBottomSheet()">
        <div class="w-12 h-1.5 rounded-full bg-slate-600"></div>
    </div>

    <!-- Tab Strip -->
    <div class="flex border-b border-slate-700/50 px-4">
        <button type="button" id="tab-schedule" class="flex-1 pb-3 text-sm font-medium text-blue-400 border-b-2 border-blue-500" onclick="switchTab('schedule')">
            Jadwal
        </button>
        <button type="button" id="tab-details" class="flex-1 pb-3 text-sm font-medium text-slate-400 border-b-2 border-transparent" onclick="switchTab('details')">
            Detail Ruangan
        </button>
    </div>

    <!-- Tab: Jadwal -->
    <div id="tab-content-schedule" class="flex-1 overflow-y-auto p-4 flex flex-col">
        <p id="bs-schedule-date" class="text-sm font-medium text-slate-300 mb-8 text-center"></p>
        <div id="bs-timeline-container" class="relative flex-1 mt-2" style="min-height: 250px;">
            <!-- Timeline injected here -->
        </div>
        <div class="mt-4 pt-4 border-t border-slate-700/50 flex flex-wrap items-center justify-center gap-4 text-xs text-slate-400">
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-red-500"></span>Approved</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-500"></span>Pending</div>
            <div class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-500"></span>Pilihan Anda</div>
        </div>
    </div>

    <!-- Tab: Detail Ruangan -->
    <div id="tab-content-details" class="flex-1 overflow-y-auto p-4 hidden">
        <div id="bs-image-container" class="w-full h-40 bg-slate-800 rounded-xl mb-4 overflow-hidden relative flex items-center justify-center">
           <img id="bs-image" src="" alt="Room Image" class="w-full h-full object-cover hidden">
           <svg id="bs-no-image" class="h-10 w-10 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
           </svg>
        </div>
        <h4 id="bs-name" class="font-bold text-white text-lg"></h4>
        <p id="bs-location" class="text-sm text-slate-400 mt-0.5 mb-5"></p>
        
        <div class="grid grid-cols-2 gap-3 mb-5">
           <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-3 flex items-start gap-3">
               <svg class="h-5 w-5 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
               </svg>
               <div>
                   <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Kapasitas</p>
                   <p id="bs-capacity" class="text-sm font-semibold text-slate-200"></p>
               </div>
           </div>
           <div class="bg-slate-800 border border-slate-700/50 rounded-xl p-3 flex items-start gap-3">
               <svg class="h-5 w-5 text-slate-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
               </svg>
               <div>
                   <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">Tipe Booking</p>
                   <p id="bs-approval" class="text-sm font-semibold text-slate-200"></p>
               </div>
           </div>
        </div>

        <div id="bs-facilities-container">
           <p class="text-sm text-white font-semibold mb-3">Fasilitas</p>
           <div id="bs-facilities" class="flex flex-wrap gap-2 mb-4">
           </div>
        </div>
    </div>

    <div class="p-4 border-t border-slate-700/50 bg-slate-900">
        <button type="button" onclick="closeBottomSheet()" class="w-full rounded-xl bg-blue-600 hover:bg-blue-500 py-3.5 text-sm font-semibold text-white transition-all shadow-lg shadow-blue-600/20">
            Lanjutkan Pemesanan
        </button>
    </div>
</div>

@push('scripts')
<script>
    // Elements - Inputs
    const roomSelect = document.getElementById('room_id');
    const dateInput = document.getElementById('date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const form = document.getElementById('booking-form');

    // Elements - Desktop Panel
    const emptyState = document.getElementById('panel-empty-state');
    const loadingState = document.getElementById('panel-loading');
    const roomDetailsCard = document.getElementById('room-details-card');
    const scheduleCard = document.getElementById('schedule-card');
    
    // Elements - Mobile 
    const mobileStatusBar = document.getElementById('mobile-status-bar');
    const mobileStatusDot = document.getElementById('mobile-status-dot');
    const mobileStatusText = document.getElementById('mobile-status-text');
    const mobileSheetTrigger = document.getElementById('mobile-sheet-trigger');
    const bottomSheet = document.getElementById('bottom-sheet');
    const bottomSheetOverlay = document.getElementById('bottom-sheet-overlay');

    // Data State
    let currentRoom = null;
    let currentSchedule = [];
    
    // Initialize if values exist (e.g. back navigation or validation fail)
    document.addEventListener('DOMContentLoaded', () => {
        if (roomSelect.value) {
            fetchRoomDetails();
        }
    });

    // Event Listeners
    roomSelect.addEventListener('change', fetchRoomDetails);
    dateInput.addEventListener('change', () => {
        if (roomSelect.value) fetchSchedule();
    });
    
    const updateTimeline = () => {
        if (roomSelect.value && dateInput.value) {
            renderSchedule();
        }
    };
    startTimeInput.addEventListener('change', updateTimeline);
    endTimeInput.addEventListener('change', updateTimeline);

    // Form submission hook
    form.addEventListener('submit', (e) => {
        const start = startTimeInput.value;
        const end = endTimeInput.value;
        if (start >= end) {
            e.preventDefault();
            alert('Waktu selesai harus setelah waktu mulai.');
        }
    });

    // Fetch API
    async function fetchRoomDetails() {
        if (!roomSelect.value) return;
        
        showLoading();
        
        try {
            const res = await fetch(`/api/rooms/${roomSelect.value}/details`);
            if (!res.ok) throw new Error('Failed to fetch');
            const data = await res.json();
            
            currentRoom = data;
            renderRoomDetails();
            
            if (dateInput.value) {
                await fetchSchedule();
            } else {
                hideLoading();
                scheduleCard.classList.add('hidden');
                roomDetailsCard.classList.remove('hidden');
            }
        } catch (error) {
            console.error(error);
            showEmptyState();
        }
    }

    async function fetchSchedule() {
        if (!roomSelect.value || !dateInput.value) return;
        
        try {
            const res = await fetch(`/api/rooms/${roomSelect.value}/schedule?date=${dateInput.value}`);
            if (!res.ok) throw new Error('Failed to fetch schedule');
            const data = await res.json();
            
            currentSchedule = data.bookings;
            renderSchedule();
            
            hideLoading();
            roomDetailsCard.classList.remove('hidden');
            scheduleCard.classList.remove('hidden');
            mobileSheetTrigger.classList.remove('hidden');
            mobileSheetTrigger.classList.add('flex');
            
        } catch (error) {
            console.error(error);
        }
    }

    // Render Logic
    function renderRoomDetails() {
        const data = currentRoom;
        if (!data) return;

        // Desktop Map
        document.getElementById('rd-name').textContent = data.name;
        document.getElementById('rd-location').textContent = data.building ? data.building : 'Tanpa Keterangan Gedung';
        document.getElementById('rd-capacity').textContent = data.capacity + ' kursi';
        document.getElementById('rd-approval').textContent = data.approval_type;
        
        const rdImg = document.getElementById('rd-image');
        const rdNoImg = document.getElementById('rd-no-image');
        if (data.image_url) {
            rdImg.src = data.image_url;
            rdImg.classList.remove('hidden');
            rdNoImg.classList.add('hidden');
        } else {
            rdImg.classList.add('hidden');
            rdNoImg.classList.remove('hidden');
        }

        const rdFac = document.getElementById('rd-facilities');
        const rdFacCont = document.getElementById('rd-facilities-container');
        if (data.facilities && data.facilities.length > 0) {
            rdFacCont.classList.remove('hidden');
            rdFac.innerHTML = data.facilities.map(f => `<span class="px-2 py-1 bg-slate-700/50 text-slate-300 text-xs rounded border border-slate-600/50">${f}</span>`).join('');
        } else {
            rdFacCont.classList.add('hidden');
        }

        // Mobile Map
        document.getElementById('bs-name').textContent = data.name;
        document.getElementById('bs-location').textContent = data.building ? data.building : 'Tanpa Keterangan Gedung';
        document.getElementById('bs-capacity').textContent = data.capacity + ' kursi';
        document.getElementById('bs-approval').textContent = data.approval_type;
        
        const bsImg = document.getElementById('bs-image');
        const bsNoImg = document.getElementById('bs-no-image');
        if (data.image_url) {
            bsImg.src = data.image_url;
            bsImg.classList.remove('hidden');
            bsNoImg.classList.add('hidden');
        } else {
            bsImg.classList.add('hidden');
            bsNoImg.classList.remove('hidden');
        }

        const bsFac = document.getElementById('bs-facilities');
        const bsFacCont = document.getElementById('bs-facilities-container');
        if (data.facilities && data.facilities.length > 0) {
            bsFacCont.classList.remove('hidden');
            bsFac.innerHTML = data.facilities.map(f => `<span class="px-3 py-1.5 bg-slate-800/80 border border-slate-700 text-slate-300 text-xs font-medium rounded-lg">${f}</span>`).join('');
        } else {
            bsFacCont.classList.add('hidden');
        }
    }

    function renderSchedule() {
        const deskContainer = document.getElementById('timeline-container');
        const bsContainer = document.getElementById('bs-timeline-container');
        const deskDate = document.getElementById('schedule-date');
        const bsDate = document.getElementById('bs-schedule-date');
        
        // Format Date
        const dateObj = new Date(dateInput.value);
        const dateStr = dateObj.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        deskDate.textContent = dateStr;
        bsDate.textContent = dateStr;
        
        // Timeline config
        const startHour = 7;
        const endHour = 21;
        const totalMinutes = (endHour - startHour) * 60;
        
        const toMin = (timeStr) => {
            const [h, m] = timeStr.split(':').map(Number);
            return (h - startHour) * 60 + m;
        };
        
        const getStyle = (start, end) => {
            const startM = Math.max(0, toMin(start));
            const endM = Math.min(totalMinutes, toMin(end));
            const left = (startM / totalMinutes) * 100;
            const width = ((endM - startM) / totalMinutes) * 100;
            return `left: ${left}%; width: ${width}%;`;
        };

        let html = '';
        
        // Grid lines
        for(let h=startHour; h<=endHour; h+=2) {
            const pct = ((h-startHour)*60 / totalMinutes) * 100;
            html += `<div class="absolute top-0 bottom-0 border-l border-slate-700/50 z-0" style="left: ${pct}%;">
                        <span class="absolute -top-5 -left-3 text-[10px] text-slate-500">${h}:00</span>
                     </div>`;
        }
        html += `<div class="absolute top-6 bottom-0 left-0 right-0 bg-slate-800/50 rounded z-0"></div>`;

        let hasApprovedConflict = false;
        let hasPendingConflict = false;
        const selStart = startTimeInput.value;
        const selEnd = endTimeInput.value;

        // Existing Bookings
        currentSchedule.forEach(b => {
            const style = getStyle(b.start, b.end);
            let bgClass = '';
            let badgeClass = '';
            let badgeText = '';

            if (b.status === 'approved') {
                bgClass = 'bg-red-500/80 border-red-500/50';
                badgeClass = 'bg-red-600';
                badgeText = 'Approved';
            } else {
                bgClass = 'bg-amber-500/70 border-amber-500/50';
                badgeClass = 'bg-amber-600';
                badgeText = 'Pending';
            }
            
            // Check conflict
            if (selStart && selEnd && selStart < selEnd) {
                if (toMin(b.start) < toMin(selEnd) && toMin(b.end) > toMin(selStart)) {
                    if (b.status === 'approved') hasApprovedConflict = true;
                    if (b.status === 'pending') hasPendingConflict = true;
                }
            }

            html += `
                <div class="absolute top-6 bottom-2 border rounded shadow-sm z-10 flex flex-col justify-between overflow-hidden ${bgClass} transition-all hover:z-20 hover:-translate-y-0.5 hover:shadow-lg" style="${style}">
                    <div class="p-1.5 flex items-start justify-between">
                        <span class="text-[10px] font-bold text-white whitespace-nowrap overflow-hidden text-ellipsis px-1 drop-shadow-md">Sudah dibooking</span>
                    </div>
                    <div class="px-1.5 pb-1.5 text-right">
                        <span class="${badgeClass} text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow-sm">${badgeText}</span>
                    </div>
                </div>
            `;
        });

        // User Selection
        if (selStart && selEnd && selStart < selEnd) {
            const style = getStyle(selStart, selEnd);
            let userClass = 'bg-blue-500/80 border-blue-400 border-2 z-20 shadow-blue-500/20 shadow-lg';
            
            html += `
                <div class="absolute top-6 bottom-2 rounded flex flex-col justify-between overflow-hidden ${userClass} transition-all" style="${style}">
                    <div class="p-1.5">
                        <span class="text-[10px] font-bold text-white whitespace-nowrap overflow-hidden text-ellipsis drop-shadow-md block">Pilihan Anda</span>
                        <span class="text-[9px] font-medium text-blue-100 block opacity-80">${selStart}-${selEnd}</span>
                    </div>
                </div>
            `;
            
            updateStatusUI(hasApprovedConflict, hasPendingConflict, selStart, selEnd);
        } else {
            hideStatusUI();
        }

        deskContainer.innerHTML = html;
        bsContainer.innerHTML = html;
    }

    function updateStatusUI(approvedConflict, pendingConflict, start, end) {
        let text = '';
        let dotColor = '';
        let barColor = '';
        
        mobileStatusBar.classList.remove('hidden');
        mobileStatusBar.classList.add('flex');

        if (approvedConflict) {
            text = 'Slot tidak tersedia — sudah dibooking';
            dotColor = 'bg-red-400';
            barColor = 'border-red-500/30 bg-red-500/10 text-red-300';
        } else if (pendingConflict) {
            text = 'Tersedia, namun ada request pending lain';
            dotColor = 'bg-amber-400';
            barColor = 'border-amber-500/30 bg-amber-500/10 text-amber-300';
        } else {
            text = `Slot ${start}–${end} tersedia · ${currentRoom ? currentRoom.capacity : ''} kursi`;
            dotColor = 'bg-emerald-400';
            barColor = 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300';
        }

        // Apply desktop
        const deskText = document.getElementById('desk-status-text');
        const deskDot = document.getElementById('desk-status-dot');
        const deskBar = document.getElementById('desk-status-bar');
        
        deskText.textContent = text;
        deskDot.className = `h-2 w-2 rounded-full flex-shrink-0 ${dotColor}`;
        deskBar.className = `px-5 py-3 border-t flex items-center gap-2 text-sm font-medium ${barColor}`;

        // Apply mobile
        mobileStatusText.textContent = text;
        mobileStatusDot.className = `h-2 w-2 rounded-full flex-shrink-0 ${dotColor}`;
        mobileStatusBar.className = `lg:hidden rounded-xl px-4 py-3 text-sm font-medium flex items-center gap-2 mt-4 ${barColor}`;
    }

    function hideStatusUI() {
        document.getElementById('desk-status-bar').className = 'hidden';
        mobileStatusBar.classList.add('hidden');
        mobileStatusBar.classList.remove('flex');
    }

    function showLoading() {
        emptyState.classList.add('hidden');
        roomDetailsCard.classList.add('hidden');
        scheduleCard.classList.add('hidden');
        loadingState.classList.remove('hidden');
        loadingState.classList.add('flex');
    }

    function hideLoading() {
        loadingState.classList.add('hidden');
        loadingState.classList.remove('flex');
    }

    function showEmptyState() {
        hideLoading();
        roomDetailsCard.classList.add('hidden');
        scheduleCard.classList.add('hidden');
        emptyState.classList.remove('hidden');
        emptyState.classList.add('flex');
        mobileSheetTrigger.classList.add('hidden');
        mobileSheetTrigger.classList.remove('flex');
        hideStatusUI();
    }

    // Bottom Sheet Logic
    window.openBottomSheet = function() {
        bottomSheetOverlay.classList.remove('hidden');
        bottomSheet.classList.remove('hidden');
        
        // Timeout for transition to work
        setTimeout(() => {
            bottomSheetOverlay.classList.remove('opacity-0');
            bottomSheet.classList.remove('translate-y-full');
        }, 10);
    }

    window.closeBottomSheet = function() {
        bottomSheetOverlay.classList.add('opacity-0');
        bottomSheet.classList.add('translate-y-full');
        
        setTimeout(() => {
            bottomSheetOverlay.classList.add('hidden');
            bottomSheet.classList.add('hidden');
        }, 300);
    }

    window.switchTab = function(tab) {
        const tabSchedule = document.getElementById('tab-schedule');
        const tabDetails = document.getElementById('tab-details');
        const contentSchedule = document.getElementById('tab-content-schedule');
        const contentDetails = document.getElementById('tab-content-details');

        if (tab === 'schedule') {
            tabSchedule.classList.replace('text-slate-400', 'text-blue-400');
            tabSchedule.classList.replace('border-transparent', 'border-blue-500');
            tabDetails.classList.replace('text-blue-400', 'text-slate-400');
            tabDetails.classList.replace('border-blue-500', 'border-transparent');
            
            contentSchedule.classList.remove('hidden');
            contentSchedule.classList.add('flex');
            contentDetails.classList.add('hidden');
        } else {
            tabDetails.classList.replace('text-slate-400', 'text-blue-400');
            tabDetails.classList.replace('border-transparent', 'border-blue-500');
            tabSchedule.classList.replace('text-blue-400', 'text-slate-400');
            tabSchedule.classList.replace('border-blue-500', 'border-transparent');
            
            contentDetails.classList.remove('hidden');
            contentSchedule.classList.add('hidden');
            contentSchedule.classList.remove('flex');
        }
    }
</script>
@endpush
@endsection
