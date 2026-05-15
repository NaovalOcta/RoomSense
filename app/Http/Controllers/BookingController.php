<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingSubmittedNotification;
use App\Notifications\AdminNewBookingNotification;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Show the user's dashboard — their list of bookings.
     */
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $bookings = $user->bookings()
            ->with('room')
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('bookings.index', compact('bookings'));
    }

    /**
     * Show the booking creation form.
     * If a room_id is passed via query string, pre-select that room.
     */
    public function create(Request $request): View
    {
        $rooms = Room::where('is_active', true)->orderBy('name')->get();
        $selectedRoom = $request->query('room_id')
            ? Room::find($request->query('room_id'))
            : null;

        return view('bookings.create', compact('rooms', 'selectedRoom'));
    }

    /**
     * Store a new booking after validating for time conflicts.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_id'    => ['required', 'exists:rooms,id'],
            'date'       => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string'],
            'end_time'   => ['required', 'string', 'after:start_time'],
            'purpose'    => ['nullable', 'string', 'max:255'],
        ], [
            'date.after_or_equal' => 'The booking date cannot be in the past.',
            'end_time.after'      => 'End time must be after start time.',
        ]);

        // Build full datetime strings
        $startDateTime = $validated['date'] . ' ' . $validated['start_time'] . ':00';
        $endDateTime   = $validated['date'] . ' ' . $validated['end_time'] . ':00';

        // Verify end time is strictly after start time
        if ($endDateTime <= $startDateTime) {
            return back()->withInput()->withErrors([
                'end_time' => 'End time must be after start time.',
            ]);
        }

        $room = Room::findOrFail($validated['room_id']);

        // Check for booking conflicts on this room
        $hasApprovedConflict = $room->hasConflict($startDateTime, $endDateTime, null, [Booking::STATUS_APPROVED]);
        $hasPendingConflict  = $room->hasConflict($startDateTime, $endDateTime, null, [Booking::STATUS_PENDING]);

        if ($hasApprovedConflict) {
            $suggestions = $this->suggestAlternatives($startDateTime, $endDateTime, $room->capacity);
            return back()->withInput()->withErrors([
                'room_id' => 'This room is already booked and approved for the selected time slot. Please see suggested alternatives below or choose a different time.',
            ])->with('suggestions', $suggestions);
        }

        $booking = Booking::create([
            'user_id'    => Auth::id(),
            'room_id'    => $room->id,
            'start_time' => $startDateTime,
            'end_time'   => $endDateTime,
            'purpose'    => $validated['purpose'] ?? null,
            'status'     => Booking::STATUS_PENDING,
        ]);

        // Dispatch notification to USER (In-App + Email) — notifyNow() bypasses queue
        $booking->user->notifyNow(new BookingSubmittedNotification($booking));

        // Dispatch notification to ALL ADMINS (In-App ONLY, batch strategy)
        User::where('is_admin', true)->each(function (User $admin) use ($booking) {
            $admin->notifyNow(new AdminNewBookingNotification($booking));

            event(new \App\Events\RealtimeNotificationEvent(
                user: $admin,
                title: '📋 New Booking Request',
                message: "{$booking->user->name} booked {$booking->room->name}.",
                actionUrl: route('admin.bookings.index', [], false)
            ));
        });

        if ($hasPendingConflict) {
            return redirect()->route('dashboard')
                ->with('success', 'Your booking request has been submitted!')
                ->with('warning', 'Please note that there is another pending request for this room at the same time. The admin will review all requests.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Your booking request has been submitted! Please wait for admin approval.');
    }

    /**
     * Cancel (delete) a user's own booking if it is still pending.
     */
    public function destroy(Booking $booking): RedirectResponse
    {
        // Users can only cancel their own pending bookings
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $booking->isPending()) {
            return back()->with('error', 'Only pending bookings can be cancelled.');
        }

        // Send cancellation notification BEFORE updating status — notifyNow() bypasses queue
        $booking->user->notifyNow(new BookingCancelledNotification($booking));

        // Soft-cancel with audit fields
        $booking->update([
            'status'       => Booking::STATUS_CANCELLED,
            'cancelled_by' => Auth::id(),
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Your booking has been cancelled.');
    }

    /**
     * Suggest available rooms based on time and capacity.
     */
    protected function suggestAlternatives(string $startDateTime, string $endDateTime, int $requestedCapacity)
    {
        return Room::where('capacity', '>=', $requestedCapacity)
            ->where('is_active', true)
            ->get()
            ->filter(function($room) use ($startDateTime, $endDateTime) {
                return !$room->hasConflict($startDateTime, $endDateTime);
            })
            ->sortBy('capacity') // Closest match first
            ->take(3);
    }
}
