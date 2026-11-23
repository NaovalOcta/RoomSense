<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay();
        $demoEmail = config('app.demo_user_email');
        $borrowers = User::where('is_admin', false)->whereNotNull('email_verified_at');
        $demoUser = (clone $borrowers)->where('email', $demoEmail)->first()
            ?? $borrowers->orderBy('id')->first();
        $adminUser = User::where('is_admin', true)->first();

        if (!$demoUser || !$adminUser) {
            $this->command->warn('Users not found, skipping BookingSeeder.');
            return;
        }

        $activeRooms = Room::where('is_active', true)->orderBy('id')->get();
        if ($activeRooms->count() < 2) {
            $this->command->warn('Not enough active rooms to seed bookings.');
            return;
        }

        $roomA = $activeRooms[0];
        $roomB = $activeRooms[1];

        // A new synthetic demonstration lifecycle for the sole default borrower.
        // Admin approves bookings; it does not stand in for a removed borrower.
        // These fixtures do not transfer ownership of archived legacy records.

        // ---------------------------------------------------------------------
        // A. Data Spesifik untuk Uji Coba Algoritma (4 Data)
        // ---------------------------------------------------------------------

        // 1. Approved Booking (Blokir Jadwal) - Besok jam 09:00 - 11:00 di Ruang A
        Booking::create([
            'user_id'    => $demoUser->id,
            'room_id'    => $roomA->id,
            'start_time' => $today->copy()->addDay()->setTime(9, 0)->utc(),
            'end_time'   => $today->copy()->addDay()->setTime(11, 0)->utc(),
            'purpose'    => 'Rapat Divisi Akademik (Data Deterministic - Approved)',
            'status'     => Booking::STATUS_APPROVED,
        ]);

        // 2. Pending Booking (Peringatan/Warning) - Besok jam 13:00 - 15:00 di Ruang A
        Booking::create([
            'user_id'    => $demoUser->id,
            'room_id'    => $roomA->id,
            'start_time' => $today->copy()->addDay()->setTime(13, 0)->utc(),
            'end_time'   => $today->copy()->addDay()->setTime(15, 0)->utc(),
            'purpose'    => 'Diskusi Kelompok Pengantar IT (Data Deterministic - Pending)',
            'status'     => Booking::STATUS_PENDING,
        ]);

        // 3. Rejected Booking (Jadwal Tersedia) - Lusa jam 10:00 - 12:00 di Ruang B
        Booking::create([
            'user_id'    => $demoUser->id,
            'room_id'    => $roomB->id,
            'start_time' => $today->copy()->addDay()->addDay()->setTime(10, 0)->utc(),
            'end_time'   => $today->copy()->addDay()->addDay()->setTime(12, 0)->utc(),
            'purpose'    => 'Seminar Proposal (Data Deterministic - Rejected)',
            'status'     => Booking::STATUS_REJECTED,
            'notes'      => 'Ruangan sudah di-booking oleh pihak fakultas untuk acara internal.',
        ]);

        // 4. Cancelled Booking (Jadwal Tersedia) - Lusa jam 14:00 - 16:00 di Ruang B
        Booking::create([
            'user_id'    => $demoUser->id,
            'room_id'    => $roomB->id,
            'start_time' => $today->copy()->addDay()->addDay()->setTime(14, 0)->utc(),
            'end_time'   => $today->copy()->addDay()->addDay()->setTime(16, 0)->utc(),
            'purpose'    => 'Persiapan Acara BEM (Data Deterministic - Cancelled)',
            'status'     => Booking::STATUS_CANCELLED,
            'cancelled_by' => $demoUser->id,
            'cancelled_at' => now('UTC'),
        ]);

        // ---------------------------------------------------------------------
        // B. Data Acak/Random untuk UI & Pagination (~30 Data)
        // ---------------------------------------------------------------------
        $faker = \Faker\Factory::create('id_ID');
        $faker->seed(1101);
        $users = [$demoUser];
        
        $purposes = [
            'Kuliah Pengganti', 'Rapat Koordinasi BEM', 'Diskusi Kelompok AI',
            'Seminar Nasional', 'Workshop Web Development', 'Rapat Himpunan Mahasiswa',
            'Ujian Susulan', 'Presentasi Proyek Akhir', 'Pelatihan Soft Skill',
            'Gladi Bersih Wisuda'
        ];

        // 20 Data Historis (Masa Lalu)
        for ($i = 0; $i < 20; $i++) {
            $user = $faker->randomElement($users);
            $room = $faker->randomElement($activeRooms);
            $start = $today->copy()->subDays($i + 1)->setTime($faker->numberBetween(8, 16), 0);
            $end = (clone $start)->addHours($faker->numberBetween(1, 3));
            $status = $faker->randomElement([Booking::STATUS_APPROVED, Booking::STATUS_REJECTED, Booking::STATUS_CANCELLED, Booking::STATUS_APPROVED]); // Lebih banyak approved
            
            $bookingData = [
                'user_id'    => $user->id,
                'room_id'    => $room->id,
                'start_time' => $start->copy()->utc(),
                'end_time'   => $end->copy()->utc(),
                'purpose'    => $faker->randomElement($purposes) . ' (Historical)',
                'status'     => $status,
                'created_at' => $start->copy()->subDays($faker->numberBetween(1, 5))->utc(),
            ];

            if ($status === Booking::STATUS_REJECTED) {
                $bookingData['notes'] = 'Ditolak karena ruangan sedang dalam perbaikan.';
            } else if ($status === Booking::STATUS_CANCELLED) {
                $bookingData['cancelled_by'] = $user->id;
                $bookingData['cancelled_at'] = $start->copy()->subDay()->utc();
            }

            Booking::create($bookingData);
        }

        // 10 Data Masa Depan (Future)
        for ($i = 0; $i < 10; $i++) {
            $user = $faker->randomElement($users);
            $room = $faker->randomElement($activeRooms);
            // 3-14 hari ke depan
            $start = $today->copy()->addDays($i + 3)->setTime($faker->numberBetween(8, 15), 0);
            $end = (clone $start)->addHours($faker->numberBetween(1, 4));
            
            // Periksa agar tidak konflik (secara sederhana, walau faker mungkin saja bentrok, 
            // tapi karena rentangnya jauh peluangnya kecil).
            $status = $faker->randomElement([Booking::STATUS_PENDING, Booking::STATUS_PENDING, Booking::STATUS_APPROVED]); // Lebih banyak pending

            Booking::create([
                'user_id'    => $user->id,
                'room_id'    => $room->id,
                'start_time' => $start->copy()->utc(),
                'end_time'   => $end->copy()->utc(),
                'purpose'    => $faker->randomElement($purposes) . ' (Future)',
                'status'     => $status,
                'created_at' => now('UTC')->subHours($faker->numberBetween(1, 24)),
            ]);
        }
    }
}
