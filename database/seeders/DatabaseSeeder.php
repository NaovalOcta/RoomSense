<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- Create Admin User ---
        $admin = User::create([
            'name'     => 'Admin',
            'email'    => 'admin@roomsense.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // --- Create Regular Users ---
        $demoEmail = config('app.demo_user_email');

        $user1 = User::create([
            'name'              => 'Dosen Penguji (Demo)',
            'email'             => $demoEmail,
            'password'          => Hash::make('password'),
            'is_admin'          => false,
            'is_demo'           => true,
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name'     => 'Bob Smith',
            'email'    => 'bob@roomsense.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        // --- Create Rooms ---
        $this->call([
            RoomSeeder::class,
        ]);

        // --- Create Bookings ---
        $this->call([
            BookingSeeder::class,
        ]);
    }
}

