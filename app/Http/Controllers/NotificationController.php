<?php

// app/Http/Controllers/NotificationController.php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of the notifications.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filter = $request->query('filter', 'all');

        $query = $user->notifications();

        if ($filter === 'unread') {
            $query = $user->unreadNotifications();
        } elseif ($filter === 'read') {
            $query = $user->readNotifications();
        }

        $notifications = $query->paginate(15)->withQueryString();

        return view('notifications.index', compact('notifications', 'filter'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
        }

        return redirect()->back();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['count' => $request->user()->unreadNotifications()->count()]);
        }

        return redirect()->back();
    }

    /**
     * Get the unread notifications count as JSON.
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function recent(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
            'notifications' => $request->user()->notifications()->take(5)->get()->map(fn ($notification) => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? 'Notification',
                'message' => $notification->data['message'] ?? '',
                'icon' => $notification->data['icon'] ?? '🔔',
                'read_at' => $notification->read_at,
                'created_at_wib' => $notification->created_at->timezone('Asia/Jakarta')->diffForHumans(),
            ]),
        ]);
    }

    public function show(Request $request, string $notification): View
    {
        return view('notifications.show', $this->details($request, $notification));
    }

    public function open(Request $request, string $notification): JsonResponse
    {
        $details = $this->details($request, $notification);
        // Render before marking read, so failed rendering cannot consume an unread item.
        $html = view('notifications._detail', $details)->render();
        $details['notification']->markAsRead();

        return response()->json([
            'html' => $html,
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    private function details(Request $request, string $id): array
    {
        // Scope both id and notifiable_type through the authenticated user's relationship.
        $notification = $request->user()->notifications()->findOrFail($id);
        $booking = null;
        $bookingUrl = null;
        $bookingId = $notification->data['booking_id'] ?? null;
        if (is_int($bookingId) || (is_string($bookingId) && ctype_digit($bookingId))) {
            $query = Booking::with('room')->whereKey($bookingId);
            if (! $request->user()->isAdmin()) {
                $query->where('user_id', $request->user()->id);
            }
            $booking = $query->first();
            if ($booking) {
                $bookingUrl = route($request->user()->isAdmin() ? 'admin.bookings.index' : 'dashboard', ['booking' => $booking->id]);
            }
        }

        return compact('notification', 'booking', 'bookingUrl');
    }
}
