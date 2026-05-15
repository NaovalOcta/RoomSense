<?php
// app/Notifications/BookingReminderNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Booking $booking, public string $type)
    {
        $this->connection = 'database';
        $this->queue = 'notifications';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $timeString = $this->booking->start_time->timezone('Asia/Jakarta')->format('H:i');

        if ($this->type === 'H-24') {
            return (new MailMessage)
                ->subject("⏰ Reminder: Booking Tomorrow - {$this->booking->room->name}")
                ->line("Your booking starts tomorrow at {$timeString} WIB. Please prepare accordingly.")
                ->action('View Bookings', route('dashboard'));
        }

        return (new MailMessage)
            ->subject("🔔 Starting Soon: {$this->booking->room->name}")
            ->line("Your booking begins in 2 hours at {$timeString} WIB. See you there!")
            ->action('View Bookings', route('dashboard'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => $this->type === 'H-24' ? 'Reminder: Tomorrow' : 'Starting Soon',
            'message' => $this->type === 'H-24'
                ? "Booking {$this->booking->room->name} starts tomorrow at {$this->booking->start_time->timezone('Asia/Jakarta')->format('H:i')} WIB"
                : "Booking {$this->booking->room->name} starts in 2 hours!",
            'action_url' => route('dashboard', [], false),
            'icon' => $this->type === 'H-24' ? '⏰' : '🔔',
            'created_at' => now()->toISOString(),
        ];
    }
}
