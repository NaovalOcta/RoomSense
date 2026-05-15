<?php
// app/Notifications/AdminNewBookingNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AdminNewBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Booking $booking)
    {
        $this->connection = 'database';
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => '📋 New Booking Request',
            'message' => "{$this->booking->user->name} booked {$this->booking->room->name} on {$this->booking->start_time->format('d M Y')}.",
            'action_url' => route('admin.bookings.index', [], false),
            'icon' => '🔔',
            'created_at' => now()->toISOString(),
        ];
    }
}
