<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'capacity',
        'building',
        'description',
        'facilities',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'capacity'   => 'integer',
            'facilities' => 'array',
            'images'     => 'array',
        ];
    }

    /**
     * A room has many bookings.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Check if this room has a booking conflict with the given time range.
     */
    public function hasConflict(string $startTime, string $endTime, ?int $excludeBookingId = null): bool
    {
        $query = $this->bookings()
            ->whereIn('status', ['pending', 'approved'])
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($excludeBookingId) {
            $query->where('id', '!=', $excludeBookingId);
        }

        return $query->exists();
    }

    /**
     * Get the current status of the room (available, booked, pending).
     */
    public function getCurrentStatusAttribute(): string
    {
        $now = now();
        $currentBookings = $this->relationLoaded('bookings')
            ? $this->bookings->filter(fn($b) => $b->start_time <= $now && $b->end_time > $now && in_array($b->status, ['approved', 'pending']))
            : $this->bookings()
                ->whereIn('status', ['approved', 'pending'])
                ->where('start_time', '<=', $now)
                ->where('end_time', '>', $now)
                ->get();

        if ($currentBookings->where('status', 'approved')->isNotEmpty()) {
            return 'booked';
        }

        if ($currentBookings->where('status', 'pending')->isNotEmpty()) {
            return 'pending';
        }

        return 'available';
    }
}
