<?php
// app/Notifications/BookingRejectedNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedNotification extends Notification implements ShouldQueue
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
        $reason = $this->booking->notes ?? 'No reason provided';
        return (new MailMessage)
            ->subject("❌ Booking Rejected - #{$this->booking->id}")
            ->line("We regret to inform you that your booking request for {$this->booking->room->name} has been rejected.")
            ->line("Reason: {$reason}")
            ->line("If you wish, you can submit another request for a different room or time slot.")
            ->action('Book Another Room', route('rooms.index'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        $reason = $this->booking->notes ?? 'No reason provided';
        return [
            'title' => 'Booking Rejected',
            'message' => "Booking #{$this->booking->id} was rejected. Reason: {$reason}",
            'action_url' => route('rooms.index', [], false),
            'icon' => '❌',
            'created_at' => now()->toISOString(),
        ];
    }
}
