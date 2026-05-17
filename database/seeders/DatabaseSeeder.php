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

        // --- Create Sample Bookings ---
        $activeRooms = Room::where('is_active', true)->inRandomOrder()->limit(2)->get();

        if ($activeRooms->count() >= 2) {
            $room1 = $activeRooms[0];
            $room2 = $activeRooms[1];

            Booking::create([
                'user_id'    => $user1->id,
                'room_id'    => $room1->id,
                'start_time' => now()->addDay()->setTime(9, 0),
                'end_time'   => now()->addDay()->setTime(11, 0),
                'purpose'    => 'Software Engineering Group Project Presentation',
                'status'     => 'approved',
            ]);

            Booking::create([
                'user_id'    => $user2->id,
                'room_id'    => $room2->id,
                'start_time' => now()->addDays(2)->setTime(14, 0),
                'end_time'   => now()->addDays(2)->setTime(16, 0),
                'purpose'    => 'Study Group Session — Database Systems',
                'status'     => 'pending',
            ]);

            Booking::create([
                'user_id'    => $user1->id,
                'room_id'    => $room2->id,
                'start_time' => now()->subDay()->setTime(10, 0),
                'end_time'   => now()->subDay()->setTime(12, 0),
                'purpose'    => 'AI & Machine Learning Seminar',
                'status'     => 'rejected',
                'notes'      => 'Room was already reserved for faculty use.',
            ]);
        }
    }
}

