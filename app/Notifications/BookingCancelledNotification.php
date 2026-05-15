<?php
// app/Notifications/BookingCancelledNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚫 Booking Cancelled - #{$this->booking->id}")
            ->line("The booking request #{$this->booking->id} for {$this->booking->room->name} has been cancelled.")
            ->line("If this was an error, please submit another request.")
            ->action('Book Another Room', route('rooms.index'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Booking Cancelled',
            'message' => "Booking #{$this->booking->id} for {$this->booking->room->name} has been cancelled.",
            'action_url' => route('rooms.index', [], false),
            'icon' => '🚫',
            'created_at' => now()->toISOString(),
        ];
    }
}
