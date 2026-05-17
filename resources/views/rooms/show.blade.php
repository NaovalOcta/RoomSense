@extends('layouts.app')
@section('title', $room->name)

@section('content')

    <!-- Breadcrumb -->
    <nav class="mb-6 flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm text-slate-400">
            <li>
                <a href="{{ route('rooms.index') }}" class="hover:text-blue-400 transition-colors">Rooms</a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-slate-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                    <span class="ml-1 text-slate-300 md:ml-2">{{ $room->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    <!-- Page Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-white">{{ $room->name }}</h1>
                @php
                    $status = $room->current_status;
                    $statusColors = [
                        'available' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                        'booked' => 'bg-red-500/10 text-red-400 border-red-500/20',
                        'pending' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                    ];
                @endphp
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium border {{ $statusColors[$status] }}">
                    {{ ucfirst($status) }}
                </span>
            </div>
            @if($room->building)
                <p class="mt-1 text-slate-400 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    {{ $room->building }}
                </p>
            @endif
        </div>
        <a href="{{ route('bookings.create', ['room_id' => $room->id]) }}" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl font-semibold shadow-lg shadow-blue-600/20 transition-all hover:-translate-y-0.5">
            Book This Room
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Room Details -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Image Gallery -->
            @if($room->images && count($room->images) > 0)
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div id="main-image-container" class="w-full h-64 md:h-96 rounded-2xl bg-slate-800 flex items-center justify-center border border-slate-700/50 overflow-hidden relative cursor-zoom-in group/main shadow-lg">
                        <img id="main-room-image" src="{{ $room->imageUrl($room->images[0]) }}" alt="{{ $room->name }}" class="w-full h-full object-cover transition-opacity duration-300">
                        <div class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover/main:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                            <span class="flex items-center gap-2 bg-slate-900/80 border border-slate-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl backdrop-blur-sm transform translate-y-2 group-hover/main:translate-y-0 transition-all duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                                View Fullscreen
                            </span>
                        </div>
                    </div>
                    <!-- Thumbnails -->
                    @if(count($room->images) > 1)
                        <div class="flex gap-4 overflow-x-auto pb-2 snap-x scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                            @foreach($room->images as $img)
                                <button type="button" 
                                        onmouseover="document.getElementById('main-room-image').src='{{ $room->imageUrl($img) }}'"
                                        onclick="document.getElementById('main-room-image').src='{{ $room->imageUrl($img) }}'"
                                        class="thumbnail-btn flex-shrink-0 w-32 h-24 md:w-40 md:h-28 rounded-xl overflow-hidden border border-slate-700/50 snap-start hover:border-blue-500 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <img src="{{ $room->imageUrl($img) }}" alt="{{ $room->name }} view" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                <!-- Placeholder -->
                <div class="w-full h-64 md:h-96 rounded-2xl bg-slate-800 flex items-center justify-center border border-slate-700/50 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-900/20 to-slate-900/50"></div>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif

            <!-- Overview -->
            <div class="bg-slate-800/50 rounded-2xl border border-slate-700/50 p-6 md:p-8">
                <h3 class="text-xl font-semibold text-white mb-4">Overview</h3>
                
                <div class="flex flex-wrap gap-4 mb-6">
                    <div class="flex items-center gap-2 bg-slate-800 px-4 py-2 rounded-lg border border-slate-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span class="text-slate-300 font-medium">Capacity: {{ $room->capacity }} people</span>
                    </div>
                </div>

                <div class="prose prose-invert max-w-none text-slate-400">
                    <p>{{ $room->description ?: 'No description provided for this room.' }}</p>
                </div>

                <!-- Facilities -->
                @if($room->facilities && count($room->facilities) > 0)
                    <div class="mt-8">
                        <h4 class="text-sm uppercase tracking-wider font-semibold text-slate-500 mb-4">Facilities</h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($room->facilities as $facility)
                                <span class="px-3 py-1 bg-slate-700/50 text-slate-300 rounded-lg text-sm border border-slate-600/50">
                                    {{ $facility }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right Column: Schedule (Phase 3 implementation here) -->
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-slate-800/50 rounded-2xl border border-slate-700/50 p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white">Today's Schedule</h3>
                    <span class="text-xs text-slate-400 bg-slate-800 px-2.5 py-1 rounded-md">{{ now()->format('M j, Y') }}</span>
                </div>

                @if($todayBookings->isEmpty())
                    <div class="text-center py-8">
                        <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-800 mb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p class="text-slate-300 font-medium">No bookings today</p>
                        <p class="text-slate-500 text-sm mt-1">This room is completely available.</p>
                    </div>
                @else
                    <!-- Calendar View (Lightweight Grid) -->
                    <div class="relative border-l-2 border-slate-700/50 ml-3 pl-4 space-y-6">
                        @foreach($todayBookings as $booking)
                            <div class="relative">
                                <!-- Dot -->
                                <div class="absolute -left-[23px] top-1 h-3 w-3 rounded-full border-2 border-slate-800 {{ $booking->status === 'approved' ? 'bg-red-500' : 'bg-amber-500' }}"></div>
                                
                                <div class="bg-slate-800 rounded-xl p-4 border border-slate-700 border-l-4 {{ $booking->status === 'approved' ? 'border-l-red-500' : 'border-l-amber-500' }}">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="font-medium text-white text-sm">
                                            {{ $booking->start_time->format('H:i') }} - {{ $booking->end_time->format('H:i') }}
                                        </div>
                                        <span class="text-[10px] uppercase font-bold tracking-wider {{ $booking->status === 'approved' ? 'text-red-400' : 'text-amber-400' }}">
                                            {{ $booking->status }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-400 truncate">{{ $booking->purpose }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Fullscreen Image Modal Overlay -->
    @if($room->images && count($room->images) > 0)
        <div id="fullscreen-modal" class="fixed inset-0 z-[100] hidden bg-slate-950/95 backdrop-blur-md flex-col items-center justify-center p-4 transition-all duration-300">
            <!-- Close button -->
            <button id="close-modal" type="button" class="absolute top-6 right-6 text-slate-400 hover:text-white p-2.5 bg-slate-800/80 hover:bg-slate-800 rounded-full border border-slate-700/50 transition-colors focus:outline-none z-50 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            
            <!-- Modal Content Wrapper -->
            <div class="relative w-full max-w-5xl max-h-[85vh] flex items-center justify-center">
                <!-- Image -->
                <img id="modal-image" src="" alt="Fullscreen view" class="max-w-full max-h-[80vh] object-contain rounded-2xl shadow-2xl border border-slate-800/80">
                
                <!-- Navigation arrows (if multiple images) -->
                @if(count($room->images) > 1)
                    <button id="prev-modal-image" type="button" class="absolute left-4 md:-left-20 text-slate-400 hover:text-white p-3.5 bg-slate-800/80 hover:bg-slate-700 rounded-full border border-slate-700/50 transition-all focus:outline-none shadow-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <button id="next-modal-image" type="button" class="absolute right-4 md:-right-20 text-slate-400 hover:text-white p-3.5 bg-slate-800/80 hover:bg-slate-700 rounded-full border border-slate-700/50 transition-all focus:outline-none shadow-xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                @endif
            </div>
            
            <!-- Caption / Pagination Counter -->
            <div class="mt-6 text-center text-slate-400 text-sm font-medium bg-slate-900/60 border border-slate-800 px-4 py-1.5 rounded-full backdrop-blur-sm">
                <span id="modal-caption-index" class="text-white font-bold">1</span> <span class="text-slate-600">/</span> {{ count($room->images) }}
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const images = @json(array_map(fn($img) => $room->imageUrl($img), $room->images));
                let currentIdx = 0;

                const mainImageContainer = document.getElementById('main-image-container');
                const mainRoomImage = document.getElementById('main-room-image');
                const fullscreenModal = document.getElementById('fullscreen-modal');
                const modalImage = document.getElementById('modal-image');
                const closeModal = document.getElementById('close-modal');
                const prevBtn = document.getElementById('prev-modal-image');
                const nextBtn = document.getElementById('next-modal-image');
                const captionIdx = document.getElementById('modal-caption-index');

                // Sync thumbnail selections with currentIdx
                const thumbnailButtons = document.querySelectorAll('.thumbnail-btn');
                thumbnailButtons.forEach((btn, index) => {
                    btn.addEventListener('mouseover', () => {
                        currentIdx = index;
                    });
                    btn.addEventListener('click', () => {
                        currentIdx = index;
                    });
                });

                if (mainImageContainer) {
                    mainImageContainer.addEventListener('click', () => {
                        openFullscreen();
                    });
                }

                function openFullscreen() {
                    if (images.length === 0) return;
                    updateModalImage();
                    fullscreenModal.classList.remove('hidden');
                    fullscreenModal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                }

                function closeFullscreen() {
                    fullscreenModal.classList.add('hidden');
                    fullscreenModal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                }

                function updateModalImage() {
                    modalImage.classList.add('opacity-0');
                    setTimeout(() => {
                        modalImage.src = images[currentIdx];
                        modalImage.classList.remove('opacity-0');
                        modalImage.classList.add('transition-opacity', 'duration-300');
                    }, 100);

                    if (captionIdx) {
                        captionIdx.textContent = currentIdx + 1;
                    }
                    if (mainRoomImage) {
                        mainRoomImage.src = images[currentIdx];
                    }
                }

                if (closeModal) {
                    closeModal.addEventListener('click', closeFullscreen);
                }

                if (fullscreenModal) {
                    fullscreenModal.addEventListener('click', (e) => {
                        if (e.target === fullscreenModal) {
                            closeFullscreen();
                        }
                    });
                }

                // Keyboard navigation
                document.addEventListener('keydown', (e) => {
                    if (!fullscreenModal.classList.contains('hidden')) {
                        if (e.key === 'Escape') {
                            closeFullscreen();
                        } else if (e.key === 'ArrowLeft' && images.length > 1) {
                            prevImage();
                        } else if (e.key === 'ArrowRight' && images.length > 1) {
                            nextImage();
                        }
                    }
                });

                function prevImage() {
                    currentIdx = (currentIdx - 1 + images.length) % images.length;
                    updateModalImage();
                }

                function nextImage() {
                    currentIdx = (currentIdx + 1) % images.length;
                    updateModalImage();
                }

                if (prevBtn) prevBtn.addEventListener('click', prevImage);
                if (nextBtn) nextBtn.addEventListener('click', nextImage);
            });
        </script>
    @endif

@endsection
