<?php
// app/Jobs/SendPostBookingNotificationJob.php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPostBookingNotificationJob implements ShouldQueue
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
        $now = now();

        Booking::approved()
            ->where('post_booking_sent', false)
            ->where('end_time', '<=', $now->copy()->subHour())      // sudah lewat 1 jam
            ->where('end_time', '>', $now->copy()->subHours(2))      // belum lewat 2 jam
            ->cursor()
            ->each(function (Booking $booking) {
                $booking->user->notifyNow(new BookingCompletedNotification($booking));
                $booking->update(['post_booking_sent' => true]);
            });
    }
}
