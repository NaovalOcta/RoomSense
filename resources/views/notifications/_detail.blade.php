@php
    $notificationType = match(class_basename($notification->type)) {
        'BookingSubmittedNotification' => 'Booking submitted',
        'BookingApprovedNotification' => 'Booking approved',
        'BookingRejectedNotification' => 'Booking rejected',
        'BookingCancelledNotification' => 'Booking cancelled',
        'BookingCompletedNotification' => 'Booking completed',
        'BookingReminderNotification' => 'Booking reminder',
        'AdminNewBookingNotification' => 'New booking request',
        default => 'System notification',
    };
@endphp
<div class="space-y-5">
    <div><h2 class="break-words text-xl font-semibold text-white">{{ $notification->data['title'] ?? 'Notification' }}</h2><p class="mt-1 text-xs text-slate-400">{{ $notificationType }} · {{ $notification->created_at->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</p></div>
    <p class="whitespace-pre-wrap break-words text-sm leading-relaxed text-slate-300">{{ $notification->data['message'] ?? 'Tidak ada pesan tambahan.' }}</p>
    @if(isset($notification->data['event_status']))
        <p class="text-sm text-slate-400">Original notification event: <strong class="text-slate-200">{{ $notification->data['event_status'] }}</strong></p>
    @endif
    @if($booking)
        <section class="space-y-3 rounded-xl border border-slate-700 bg-slate-950/40 p-4" aria-label="Current booking information">
            <h3 class="font-semibold text-white">Current booking #{{ $booking->id }}</h3>
            <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">Room</dt><dd class="break-words text-slate-200">{{ $booking->room?->name ?? 'Ruangan tidak tersedia' }}</dd></div>
                <div><dt class="text-slate-500">Booking date</dt><dd class="text-slate-200">{{ $booking->start_time->format('d M Y') }}</dd></div>
                <div><dt class="text-slate-500">Start</dt><dd class="text-slate-200">{{ $booking->start_time->format('d M Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">End</dt><dd class="text-slate-200">{{ $booking->end_time->format('d M Y H:i') }}</dd></div>
                <div><dt class="text-slate-500">Current status</dt><dd class="text-slate-200">{{ $booking->status }}</dd></div>
                @if($booking->isRejected() && $booking->notes)<div class="sm:col-span-2"><dt class="text-slate-500">Current rejection reason</dt><dd class="whitespace-pre-wrap break-words text-slate-200">{{ $booking->notes }}</dd></div>@endif
            </dl>
            @if($notification->data['booking_snapshot']['rejection_reason'] ?? null)
                <p class="whitespace-pre-wrap break-words text-sm text-slate-400">Original rejection reason: {{ $notification->data['booking_snapshot']['rejection_reason'] }}</p>
            @endif
            <a href="{{ $bookingUrl }}" class="inline-flex rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">View Booking</a>
        </section>
    @elseif(isset($notification->data['booking_id']))
        <p class="rounded-xl border border-slate-700 p-4 text-sm text-slate-400">Booking tidak tersedia atau Anda tidak memiliki akses ke booking terkait.</p>
    @else
        <p class="text-sm text-slate-500">Notifikasi ini tidak memiliki referensi booking tersimpan.</p>
    @endif
</div>
