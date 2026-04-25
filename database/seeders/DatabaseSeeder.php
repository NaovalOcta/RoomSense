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
        $user1 = User::create([
            'name'     => 'Alice Johnson',
            'email'    => 'alice@roomsense.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $user2 = User::create([
            'name'     => 'Bob Smith',
            'email'    => 'bob@roomsense.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        // --- Create Rooms ---
        $rooms = [
            [
                'name'        => 'Lecture Hall A',
                'capacity'    => 120,
                'building'    => 'Block A',
                'description' => 'Large lecture hall equipped with projector, microphone, and air conditioning.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Seminar Room B-101',
                'capacity'    => 30,
                'building'    => 'Block B',
                'description' => 'Mid-sized seminar room with whiteboard and smart TV for presentations.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Computer Lab C',
                'capacity'    => 50,
                'building'    => 'Block C',
                'description' => 'Computer lab with 50 workstations, high-speed internet, and air conditioning.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Meeting Room D-02',
                'capacity'    => 10,
                'building'    => 'Block D',
                'description' => 'Small executive meeting room with video conferencing capabilities.',
                'is_active'   => true,
            ],
            [
                'name'        => 'Auditorium Main',
                'capacity'    => 500,
                'building'    => 'Main Building',
                'description' => 'Main campus auditorium suitable for large events, graduations, and conferences.',
                'is_active'   => true,
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }

        // --- Create Sample Bookings ---
        $room1 = Room::first();
        $room2 = Room::skip(1)->first();

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
