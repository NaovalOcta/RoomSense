<?php
// app/Notifications/BookingApprovedNotification.php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApprovedNotification extends Notification implements ShouldQueue
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
        $mailMessage = (new MailMessage)
            ->subject("✅ Booking Approved - #{$this->booking->id}")
            ->line("Your booking request for {$this->booking->room->name} has been APPROVED.")
            ->line("Approved Time: {$this->booking->start_time->format('d M Y, H:i')} - {$this->booking->end_time->format('H:i')} (UTC)");

        if ($this->booking->notes) {
            $mailMessage->line("Admin Notes: {$this->booking->notes}");
        }

        return $mailMessage
            ->line("Please follow the guidelines for using the room properly.")
            ->action('View Bookings', route('dashboard'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toDatabase(mixed $notifiable): array
    {
        return [
            'title' => 'Booking Approved',
            'message' => "Booking #{$this->booking->id} for {$this->booking->room->name} has been APPROVED.",
            'action_url' => route('dashboard', [], false),
            'icon' => '✅',
            'created_at' => now()->toISOString(),
        ];
    }
}
