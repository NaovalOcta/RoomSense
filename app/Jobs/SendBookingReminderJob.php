<?php
// app/Jobs/SendBookingReminderJob.php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBookingReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now(); // UTC otomatis dari config/app.php

        // ─ Reminder H-24 (jendela 23-24 jam dari sekarang) ─
        Booking::approved()
            ->where('reminder_24h_sent', false)
            ->where('start_time', '<=', $now->copy()->addHours(24))
            ->where('start_time', '>', $now->copy()->addHours(23))
            ->cursor() // Process one record at a time to be memory-efficient
            ->each(function (Booking $booking) {
                $booking->user->notifyNow(new BookingReminderNotification($booking, 'H-24'));
                // Update flag LANGSUNG setelah dispatch — idempotent & aman jika worker crash
                $booking->update(['reminder_24h_sent' => true]);
            });

        // ─ Reminder H-2 (jendela 1.5-2 jam dari sekarang) ─
        Booking::approved()
            ->where('reminder_2h_sent', false)
            ->where('start_time', '<=', $now->copy()->addHours(2))
            ->where('start_time', '>', $now->copy()->addMinutes(90))
            ->cursor()
            ->each(function (Booking $booking) {
                $booking->user->notifyNow(new BookingReminderNotification($booking, 'H-2'));
                $booking->update(['reminder_2h_sent' => true]);
            });
    }
}
