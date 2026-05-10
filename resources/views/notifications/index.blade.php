@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Notifications</h1>
            <p class="text-sm text-slate-400 mt-1">Manage and view all your system and booking alerts.</p>
        </div>

        @if(auth()->user()->unreadNotifications()->count() > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST" class="inline-block">
                @csrf
                @method('PATCH')
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white text-sm font-semibold rounded-xl border border-slate-700 hover:border-slate-500 transition-colors shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Mark all as read
                </button>
            </form>
        @endif
    </div>

    <!-- Filter Tabs -->
    <div class="border-b border-slate-800/80 flex items-center gap-2">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors focus:outline-none {{ $filter === 'all' ? 'border-blue-500 text-blue-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            All
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors focus:outline-none {{ $filter === 'unread' ? 'border-blue-500 text-blue-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            Unread
            @if(auth()->user()->unreadNotifications()->count() > 0)
                <span class="ml-1.5 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400">{{ auth()->user()->unreadNotifications()->count() }}</span>
            @endif
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'read']) }}" 
           class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors focus:outline-none {{ $filter === 'read' ? 'border-blue-500 text-blue-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' }}">
            Read
        </a>
    </div>

    <!-- Notifications List -->
    <div class="bg-slate-900/40 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        @if($notifications->isEmpty())
            <div class="px-6 py-16 text-center max-w-sm mx-auto space-y-4">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-800/50 text-slate-500 text-2xl">
                    🔔
                </div>
                <div>
                    <h3 class="text-base font-semibold text-white">No notifications</h3>
                    <p class="text-sm text-slate-500 mt-1">There are no notifications in this category yet.</p>
                </div>
            </div>
        @else
            <div class="divide-y divide-slate-800/60">
                @foreach($notifications as $notification)
                    <div class="p-4 sm:p-5 flex items-start gap-4 hover:bg-slate-800/20 transition-colors {{ is_null($notification->read_at) ? 'bg-blue-500/5' : '' }}">
                        <span class="text-2xl flex-shrink-0 p-2 bg-slate-800/40 rounded-xl" aria-hidden="true">
                            {{ $notification->data['icon'] ?? '🔔' }}
                        </span>
                        
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-white truncate">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </h3>
                                <span class="text-xs text-slate-500 flex-shrink-0" aria-label="Received time">
                                    {{ $notification->created_at->timezone('Asia/Jakarta')->diffForHumans() }}
                                </span>
                            </div>
                            
                            <p class="text-sm text-slate-400 leading-relaxed">
                                {{ $notification->data['message'] ?? '' }}
                            </p>

                            <div class="pt-2.5 flex flex-wrap items-center justify-between gap-3">
                                <span class="text-xs text-slate-500 font-medium">
                                    {{ $notification->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB
                                </span>

                                <div class="flex items-center gap-2">
                                    @if(is_null($notification->read_at))
                                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs text-blue-400 hover:text-blue-300 font-semibold px-2.5 py-1.5 hover:bg-blue-500/10 rounded-lg transition-colors">
                                                Mark as read
                                            </button>
                                        </form>
                                    @endif

                                    @if(isset($notification->data['action_url']) && $notification->data['action_url'] !== '#')
                                        <a href="{{ $notification->data['action_url'] }}" class="inline-flex items-center justify-center text-xs bg-slate-800 hover:bg-slate-700 text-slate-200 hover:text-white font-semibold px-3 py-1.5 rounded-lg border border-slate-700 hover:border-slate-500 transition-all">
                                            View Details
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($notifications->hasPages())
        <div class="pt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
