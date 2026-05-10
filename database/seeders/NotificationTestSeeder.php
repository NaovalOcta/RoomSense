<?php
// database/seeders/NotificationTestSeeder.php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create test user (non-admin)
        $user = User::firstWhere('email', 'test@webmail.umm.ac.id');

        if (! $user) {
            $user = User::factory()->create([
                'name'              => 'Test User',
                'email'             => 'test@webmail.umm.ac.id',
                'is_admin'          => false,
                'password'          => bcrypt('password123'),
                'email_verified_at' => now(),
            ]);
        }

        // Get first active room (RoomFactory not available — use existing data)
        $room = Room::where('is_active', true)->firstOrFail();

        // ─ Test Case 1: Booking for H-24 reminder test ─
        // start_time in window: (now+23h, now+24h] UTC
        Booking::updateOrCreate(
            [
                'user_id' => $user->id,
                'room_id' => $room->id,
                'purpose' => 'Test H-24 Reminder',
            ],
            [
                'start_time'         => now()->addHours(23)->addMinutes(30), // UTC: dalam jendela 23-24 jam
                'end_time'           => now()->addHours(24)->addMinutes(30),
                'status'             => Booking::STATUS_APPROVED,
                'reminder_24h_sent'  => false, // target flag for this test
                'reminder_2h_sent'   => true,  // already sent
                'post_booking_sent'  => false,
            ]
        );

        // ─ Test Case 2: Booking for H-2 reminder test ─
        // start_time in window: (now+90min, now+2h] UTC
        Booking::updateOrCreate(
            [
                'user_id' => $user->id,
                'room_id' => $room->id,
                'purpose' => 'Test H-2 Reminder',
            ],
            [
                'start_time'         => now()->addMinutes(100), // UTC: dalam jendela 90-120 menit
                'end_time'           => now()->addMinutes(160),
                'status'             => Booking::STATUS_APPROVED,
                'reminder_24h_sent'  => true,  // already sent
                'reminder_2h_sent'   => false, // target flag for this test
                'post_booking_sent'  => false,
            ]
        );

        // ─ Test Case 3: Booking for post-booking notification test ─
        // end_time in window: (now-2h, now-1h] UTC (already ended 65 min ago)
        Booking::updateOrCreate(
            [
                'user_id' => $user->id,
                'room_id' => $room->id,
                'purpose' => 'Test Post-Booking',
            ],
            [
                'start_time'         => now()->subHours(2)->subMinutes(30), // already ended
                'end_time'           => now()->subMinutes(65),              // ended 65 minutes ago (in 1-2h window)
                'status'             => Booking::STATUS_APPROVED,
                'reminder_24h_sent'  => true,
                'reminder_2h_sent'   => true,
                'post_booking_sent'  => false, // target flag for this test
            ]
        );

        // ─ Test Case 4: Pending booking for submission flow test ─
        Booking::updateOrCreate(
            [
                'user_id' => $user->id,
                'room_id' => $room->id,
                'purpose' => 'Test Submission Flow',
            ],
            [
                'start_time'         => now()->addDays(2),
                'end_time'           => now()->addDays(2)->addHour(),
                'status'             => Booking::STATUS_PENDING,
                'reminder_24h_sent'  => false,
                'reminder_2h_sent'   => false,
                'post_booking_sent'  => false,
            ]
        );

        $this->command->info('✅ Notification test data seeded successfully.');
        $this->command->info('');
        $this->command->info('📋 Next steps:');
        $this->command->info('   1. Run scheduler:  php artisan schedule:run --force');
        $this->command->info('   2. Check jobs:     php artisan queue:work --once');
        $this->command->info('   3. Monitor logs:   tail -f storage/logs/laravel.log');
        $this->command->info('');
        $this->command->info('👤 Test user credentials:');
        $this->command->info('   Email:    test@webmail.umm.ac.id');
        $this->command->info('   Password: password123');
    }
}
