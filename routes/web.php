<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// GUEST ROUTES — Login & Register
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [AuthController::class, 'showLoginForm']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

// ─────────────────────────────────────────────────────────────────────────────
// EMAIL VERIFICATION ROUTES — Auth, but not yet verified
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/email/verify', [VerificationController::class, 'notice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');
});

// ─────────────────────────────────────────────────────────────────────────────
// AUTHENTICATED & VERIFIED USER ROUTES
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard — My Bookings
    Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');

    // Room Listing (browse available rooms)
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

    // Booking (create & cancel)
    Route::get('/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/book', [BookingController::class, 'store'])->name('bookings.store');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

    // Reactive Panel API
    Route::get('/api/rooms/{room}/details', [RoomController::class, 'apiDetails'])->name('api.rooms.details');
    Route::get('/api/rooms/{room}/schedule', [RoomController::class, 'apiSchedule'])->name('api.rooms.schedule');

    // In-App Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::patch('{notification}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('read');
        Route::patch('read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::get('unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('unread-count');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN ROUTES — Protected by auth + is_admin middleware
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware(['auth', 'is_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // Admin dashboard redirect
        Route::get('/', fn () => redirect()->route('admin.bookings.index'))->name('home');

        // Manage Rooms (Resource CRUD)
        Route::delete('rooms/{room}/image', [App\Http\Controllers\Admin\RoomController::class, 'deleteImage'])
            ->name('rooms.image.delete');
        Route::resource('rooms', App\Http\Controllers\Admin\RoomController::class);

        // Manage Bookings (Approval)
        Route::get('bookings', [App\Http\Controllers\Admin\BookingController::class, 'index'])
            ->name('bookings.index');
        Route::patch('bookings/{booking}/status', [App\Http\Controllers\Admin\BookingController::class, 'updateStatus'])
            ->name('bookings.status');
    });
