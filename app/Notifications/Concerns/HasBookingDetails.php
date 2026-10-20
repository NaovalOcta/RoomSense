<?php

namespace App\Notifications\Concerns;

trait HasBookingDetails
{
    protected function bookingDetails(string $eventStatus): array
    {
        return [
            'booking_id' => $this->booking->id,
            'event_status' => $eventStatus,
            'booking_snapshot' => [
                'room_name' => $this->booking->room?->name,
                'start_time' => $this->booking->start_time->toIso8601String(),
                'end_time' => $this->booking->end_time->toIso8601String(),
                'rejection_reason' => $eventStatus === 'rejected' ? $this->booking->notes : null,
            ],
        ];
    }
}
