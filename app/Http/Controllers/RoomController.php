<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display all active rooms available for booking.
     */
    public function index(Request $request): View
    {
        $query = Room::where('is_active', true)
            ->with(['bookings' => function ($q) {
                $now = now();
                $q->whereIn('status', ['approved', 'pending'])
                  ->where('start_time', '<=', $now)
                  ->where('end_time', '>', $now);
            }]);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('building', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        $rooms = $query->orderBy('name')->get();

        return view('rooms.index', compact('rooms'));
    }

    /**
     * Show details of a single room.
     */
    public function show(Room $room): View
    {
        abort_if(! $room->is_active, 404);

        // Load today's bookings for this room so users can see schedule
        $todayBookings = $room->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_time', today())
            ->orderBy('start_time')
            ->get();

        return view('rooms.show', compact('room', 'todayBookings'));
    }

    /**
     * API: Get room details for the reactive booking panel.
     */
    public function apiDetails(Room $room)
    {
        abort_if(! $room->is_active, 404);

        return response()->json([
            'id' => $room->id,
            'name' => $room->name,
            'building' => $room->building,
            'capacity' => $room->capacity,
            'approval_type' => 'Perlu persetujuan', // Assuming all require approval for now based on context
            'facilities' => $room->facilities ?? [],
            'image_url' => $room->imageUrl($room->images[0] ?? null),
        ]);
    }

    /**
     * API: Get room schedule for a specific date for the reactive booking panel.
     */
    public function apiSchedule(Request $request, Room $room)
    {
        abort_if(! $room->is_active, 404);
        
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = $request->date;

        $bookings = $room->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('start_time', $date)
            ->orderBy('start_time')
            ->get()
            ->map(function ($booking) {
                return [
                    'start' => $booking->start_time->format('H:i'),
                    'end' => $booking->end_time->format('H:i'),
                    'status' => $booking->status,
                ];
            });

        return response()->json([
            'date' => $date,
            'bookings' => $bookings,
        ]);
    }
}
