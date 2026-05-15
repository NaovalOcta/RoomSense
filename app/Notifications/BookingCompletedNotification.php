<?php
// app/Notifications/BookingCompletedNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompletedNotification extends Notification implements ShouldQueue
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
            ->subject("📋 Booking Completed - Thank You!")
            ->line("Your booking of {$this->booking->room->name} has ended.")
            ->line("Thank you for using the room. Please make sure the room was left clean and the lights are turned off.")
            ->action('Book Again', route('rooms.index'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Booking Completed',
            'message' => "Thank you! Your booking of {$this->booking->room->name} on {$this->booking->start_time->timezone('Asia/Jakarta')->format('d M')} has ended.",
            'action_url' => route('rooms.index', [], false),
            'icon' => '🎉',
            'created_at' => now()->toISOString(),
        ];
    }
}
