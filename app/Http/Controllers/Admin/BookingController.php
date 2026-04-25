<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Display all bookings with optional status filter.
     */
    public function index(Request $request): View
    {
        $status   = $request->query('status', 'all');
        $query    = Booking::with(['user', 'room'])->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $bookings       = $query->paginate(15)->withQueryString();
        $pendingCount   = Booking::where('status', 'pending')->count();
        $approvedCount  = Booking::where('status', 'approved')->count();
        $rejectedCount  = Booking::where('status', 'rejected')->count();

        return view('admin.bookings.index', compact(
            'bookings', 'status', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    /**
     * Approve or reject a booking.
     */
    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        // Extra conflict check when approving: ensure no other approved booking exists
        if ($validated['status'] === 'approved') {
            $conflict = Booking::where('room_id', $booking->room_id)
                ->where('id', '!=', $booking->id)
                ->where('status', 'approved')
                ->where('start_time', '<', $booking->end_time)
                ->where('end_time', '>', $booking->start_time)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Cannot approve: there is already an approved booking for this room at the same time.');
            }
        }

        $booking->update([
            'status' => $validated['status'],
            'notes'  => $validated['notes'] ?? null,
        ]);

        $action = $validated['status'] === 'approved' ? 'approved' : 'rejected';

        return back()->with('success', "Booking #{$booking->id} has been {$action} successfully.");
    }
}
