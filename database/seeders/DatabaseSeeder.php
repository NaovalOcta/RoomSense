<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- Create Admin User ---
        // Initial random secrets are intentionally not published. Provision separate
        // owner-managed credentials before opening the rebuilt development instance.
        User::create([
            'name'     => 'Admin',
            'email'    => 'admin@roomsense.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now('UTC'),
        ]);

        // --- Create Regular Users ---
        $demoEmail = config('app.demo_user_email');

        User::create([
            'name'              => 'Dosen Penguji (Demo)',
            'email'             => $demoEmail,
            'password'          => Hash::make('password'),
            'is_admin'          => false,
            'is_demo'           => true,
            'email_verified_at' => now(),
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
