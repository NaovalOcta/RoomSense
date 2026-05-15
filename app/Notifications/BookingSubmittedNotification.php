<?php
// app/Notifications/BookingSubmittedNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingSubmittedNotification extends Notification implements ShouldQueue
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
            ->subject("Booking Submitted - #{$this->booking->id}")
            ->line("Your booking request for {$this->booking->room->name} has been submitted successfully.")
            ->line("Date/Time: {$this->booking->start_time->format('d M Y, H:i')} - {$this->booking->end_time->format('H:i')} (UTC)")
            ->line("Purpose: " . ($this->booking->purpose ?? 'No purpose provided'))
            ->line("Status: Waiting for admin approval.")
            ->action('View Bookings', route('dashboard'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Booking Submitted',
            'message' => "Booking #{$this->booking->id} for {$this->booking->room->name} is pending approval.",
            'action_url' => route('dashboard', [], false),
            'icon' => '⏳',
            'created_at' => now()->toISOString(),
        ];
    }
}
