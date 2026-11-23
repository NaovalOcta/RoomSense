<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\View\View;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /**
     * Display all active rooms available for booking.
     */
    public function index(Request $request): View|Response
    {
        $search = $request->query('search', '');
        $search = $search === null ? '' : $search;
        if (is_string($search) && mb_check_encoding($search, 'UTF-8')) {
            $search = preg_replace('/^[\x{0009}-\x{000D}\x{0020}\x{0085}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+|[\x{0009}-\x{000D}\x{0020}\x{0085}\x{00A0}\x{1680}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+$/u', '', $search);
        }

        $validator = Validator::make(['search' => $search], [
            'search' => ['bail', 'present', 'string', function ($attribute, $value, $fail) {
                if (! mb_check_encoding($value, 'UTF-8') || mb_strlen($value, 'UTF-8') > 100) {
                    $fail('Search must be valid UTF-8 and at most 100 Unicode code points.');
                }
            }],
        ]);
        if ($validator->fails()) {
            if ($request->expectsJson()) {
                throw new ValidationException($validator);
            }

            return response()->view('rooms.index', [
                'rooms' => collect(), 'searchValue' => is_string($search) && mb_check_encoding($search, 'UTF-8') ? $search : '',
                'searchError' => $validator->errors()->first('search'),
            ], 422);
        }

        $query = Room::where('is_active', true)
            ->with(['bookings' => function ($q) {
                $now = now('UTC');
                $q->whereIn('status', ['approved', 'pending'])
                  ->where('start_time', '<=', $now)
                  ->where('end_time', '>', $now);
            }]);

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('building', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        $rooms = $query->orderBy('name')->get();

        return view('rooms.index', ['rooms' => $rooms, 'searchValue' => $search]);
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
            ->onBusinessDate(now('Asia/Jakarta')->format('Y-m-d'))
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
            'date' => ['bail', 'required', 'string', 'regex:/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', 'date_format:Y-m-d'],
        ]);

        $date = $request->date;

        $bookings = $room->bookings()
            ->whereIn('status', ['approved', 'pending'])
            ->onBusinessDate($date)
            ->orderBy('start_time')
            ->get()
            ->map(function ($booking) {
                return [
                    'start' => $booking->start_wib->format('H:i'),
                    'end' => $booking->end_wib->format('H:i'),
                    'status' => $booking->status,
                ];
            });

        return response()->json([
            'date' => $date,
            'bookings' => $bookings,
        ]);
    }
}
