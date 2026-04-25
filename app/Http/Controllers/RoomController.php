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
}
