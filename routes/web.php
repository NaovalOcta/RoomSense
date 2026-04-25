<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────────────────────────────────────
// GUEST ROUTES — Login
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
    Route::get('/login', [AuthController::class, 'showLoginForm']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// ─────────────────────────────────────────────────────────────────────────────
// AUTHENTICATED USER ROUTES
// ─────────────────────────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard — My Bookings
    Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');

    // Room Listing (browse available rooms)
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

    // Booking (create & cancel)
    Route::get('/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/book', [BookingController::class, 'store'])->name('bookings.store');
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
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
