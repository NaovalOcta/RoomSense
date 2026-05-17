<!-- resources/views/components/notification-bell.blade.php -->
{{-- 1. Safe JSON Injection (Prevents HTML attribute breaking) --}}
<script type="application/json" id="initial-notifications" style="display:none;">
    {!! json_encode(auth()->user()->notifications()->take(5)->get()->map(fn($n) => [
        'id' => $n->id,
        'title' => $n->data['title'] ?? 'Notification',
        'message' => $n->data['message'] ?? '',
        'icon' => $n->data['icon'] ?? '🔔',
        'action_url' => $n->data['action_url'] ?? '#',
        'read_at' => $n->read_at,
        'created_at_wib' => $n->created_at->timezone('Asia/Jakarta')->diffForHumans()
    ]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!}
</script>

{{-- 2. Alpine Component with Safe Initialization --}}
<div x-data="{
    open: false,
    count: {{ auth()->user()->unreadNotifications()->count() }},
    notifications: JSON.parse(document.getElementById('initial-notifications').textContent),
    async markAsRead(id, actionUrl) {
        await fetch(`/notifications/${id}/read`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        window.location.href = actionUrl;
    },
    async markAllAsRead() {
        await fetch('/notifications/read-all', {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        this.count = 0;
        this.notifications.forEach(n => n.read_at = new Date());
        this.open = false;
        window.location.reload();
    },
    initEcho() {
        if (typeof window.Echo !== 'undefined') {
            window.Echo.private('App.Models.User.' + {{ auth()->id() }})
                .listen('.notification.received', (data) => {
                    this.count++;
                    this.notifications.unshift({
                        id: Date.now(),
                        title: data.title,
                        message: data.message,
                        icon: data.icon,
                        action_url: data.action_url,
                        read_at: null,
                        created_at_wib: 'Baru saja'
                    });

                    // ─── ROLE-BASED BEHAVIOR ───
                    // Detect admin by checking for the sticky bar element that only renders for admins.
                    // Admin: Show sticky accumulator bar + update tab title. NO auto-reload.
                    // User (mahasiswa): Auto-reload as before.
                    const isAdmin = document.getElementById('admin-new-booking-bar') !== null;

                    if (isAdmin) {
                        const bar = document.getElementById('admin-new-booking-bar');
                        const counterEl = document.getElementById('admin-booking-bar-count');
                        let currentCount = parseInt(bar.dataset.count || '0') + 1;
                        bar.dataset.count = currentCount;
                        counterEl.textContent = currentCount;
                        bar.classList.remove('hidden');

                        const titleEl = document.querySelector('title');
                        if (!titleEl.dataset.baseTitle) {
                            titleEl.dataset.baseTitle = document.title;
                        }
                        document.title = '(+' + currentCount + ') ' + titleEl.dataset.baseTitle;
                    } else {
                        window.location.reload();
                    }
                });

            window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                if (states.current === 'disconnected' || states.current === 'unavailable') {
                    console.warn('⚠️ Real-time connection lost. Reconnecting...');
                } else if (states.current === 'connected') {
                    console.log('✅ WebSocket reconnected successfully.');
                }
            });
        } else {
            console.error('❌ window.Echo is undefined. Run `npm run build` and check resources/js/app.js');
        }
    }
}"
x-init="initEcho()"
class="relative"
@click.away="open = false">
    <button @click="open = !open"
            class="relative p-2 text-slate-400 hover:text-white rounded-full hover:bg-slate-800 transition-all focus:outline-none focus:ring-2 focus:ring-blue-500/50"
            aria-label="Notification Center"
            :aria-expanded="open.toString()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        <span x-show="count > 0"
              x-text="count"
              class="absolute top-1.5 right-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white ring-2 ring-slate-900 animate-pulse">
        </span>
    </button>

    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 mt-2 w-[300px] sm:w-80 md:w-96 rounded-2xl border border-slate-800 bg-slate-900/95 backdrop-blur-md shadow-2xl z-50 overflow-hidden origin-top-right"
         role="menu"
         style="display: none;">
        <div class="px-4 py-3.5 border-b border-slate-800 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-white">Notifications</h3>
            <span x-show="count > 0" x-text="`${count} unread`" class="text-xs font-medium text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full flex-shrink-0"></span>
        </div>

        <div class="max-h-[350px] overflow-y-auto divide-y divide-slate-800/50">
            <template x-if="notifications.length === 0">
                <div class="px-4 py-8 text-center">
                    <p class="text-slate-500 text-sm">No notifications yet</p>
                </div>
            </template>

            <template x-for="notif in notifications" :key="notif.id">
                <div @click="markAsRead(notif.id, notif.action_url)"
                     class="px-4 py-3.5 hover:bg-slate-800/40 cursor-pointer transition-colors flex items-start gap-3"
                     :class="notif.read_at === null ? 'bg-blue-500/5 hover:bg-blue-500/10' : ''">
                    <span class="text-xl flex-shrink-0" x-text="notif.icon"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-slate-200 truncate" x-text="notif.title"></p>
                        <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5 leading-relaxed line-clamp-2 break-words" x-text="notif.message"></p>
                        <span class="text-[10px] text-slate-500 mt-1 block" x-text="notif.created_at_wib"></span>
                    </div>
                    <div x-show="notif.read_at === null" class="h-2 w-2 rounded-full bg-blue-500 flex-shrink-0 mt-1.5"></div>
                </div>
            </template>
        </div>

        <div class="p-2 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between gap-2">
            <button x-show="count > 0"
                    @click="markAllAsRead()"
                    class="text-xs text-slate-400 hover:text-white px-3 py-1.5 rounded-xl hover:bg-slate-800 transition-colors whitespace-nowrap">
                Mark all as read
            </button>
            <a href="{{ route('notifications.index') }}"
               class="text-xs text-blue-400 hover:text-blue-300 font-semibold px-3 py-1.5 rounded-xl hover:bg-blue-500/10 transition-colors ml-auto whitespace-nowrap">
                View all
            </a>
        </div>
    </div>
</div>
