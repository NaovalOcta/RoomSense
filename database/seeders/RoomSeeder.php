<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'name'        => 'Ruang Kuliah Teori B-201',
                'capacity'    => 40,
                'building'    => 'GKB 1',
                'description' => 'Ruang kuliah standar dengan fasilitas pendukung pembelajaran yang lengkap, sangat nyaman untuk perkuliahan rutin.',
                'facilities'  => ['AC', 'Proyektor LCD', 'Papan Tulis Kaca', 'Soket Listrik Tiap Meja'],
                'images'      => [
                    'https://picsum.photos/seed/GKB1B201_1/800/600',
                    'https://picsum.photos/seed/GKB1B201_2/800/600',
                    'https://picsum.photos/seed/GKB1B201_3/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Ruang Seminar GKB 2-101',
                'capacity'    => 20,
                'building'    => 'GKB 2',
                'description' => 'Ruangan khusus untuk kegiatan seminar kecil, presentasi skripsi, atau rapat koordinasi departemen.',
                'facilities'  => ['AC', 'Smart TV 65 Inch', 'Whiteboard', 'Sound System Mini'],
                'images'      => [
                    'https://picsum.photos/seed/GKB2Seminar_1/800/600',
                    'https://picsum.photos/seed/GKB2Seminar_2/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Laboratorium Komputer Terpadu 3',
                'capacity'    => 50,
                'building'    => 'GKB 3',
                'description' => 'Laboratorium dengan spesifikasi PC tinggi untuk kegiatan praktikum pemrograman dan pengolahan data.',
                'facilities'  => ['AC', 'PC Core i7', 'High Speed Internet', 'Proyektor LCD'],
                'images'      => [
                    'https://picsum.photos/seed/GKB3Lab_1/800/600',
                    'https://picsum.photos/seed/GKB3Lab_2/800/600',
                    'https://picsum.photos/seed/GKB3Lab_3/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Auditorium GKB 4 (Lantai 9)',
                'capacity'    => 300,
                'building'    => 'GKB 4',
                'description' => 'Aula besar yang terletak di lantai teratas GKB 4, cocok untuk acara yudisium, seminar nasional, atau pertemuan besar.',
                'facilities'  => ['AC Central', 'Stage', 'Professional Sound System', 'Large LED Screen'],
                'images'      => [
                    'https://picsum.photos/seed/GKB4Audit_1/800/600',
                    'https://picsum.photos/seed/GKB4Audit_2/800/600',
                    'https://picsum.photos/seed/GKB4Audit_3/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Ruang Sidang Senat',
                'capacity'    => 30,
                'building'    => 'Gedung Rektorat',
                'description' => 'Ruangan prestisius yang biasa digunakan untuk rapat pimpinan universitas dan tamu penting.',
                'facilities'  => ['AC', 'Microphone Delegasi', 'Kursi Eksekutif', 'Video Conferencing'],
                'images'      => [
                    'https://picsum.photos/seed/RektoratSenat_1/800/600',
                    'https://picsum.photos/seed/RektoratSenat_2/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Laboratorium Multimedia & Animasi',
                'capacity'    => 60,
                'building'    => 'Lab Terpadu',
                'description' => 'Ruang kreatif yang dilengkapi dengan peralatan produksi media dan desain grafis.',
                'facilities'  => ['AC', 'Mac Studio', 'Pen Display Tablet', 'Green Screen Wall'],
                'images'      => [
                    'https://picsum.photos/seed/LabMultimedia_1/800/600',
                    'https://picsum.photos/seed/LabMultimedia_2/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Ruang Kelas B-302 (Maintenance)',
                'capacity'    => 40,
                'building'    => 'GKB 1',
                'description' => 'Ruangan ini sementara tidak tersedia untuk umum karena sedang dalam masa renovasi sistem pendingin udara.',
                'facilities'  => ['Proyektor LCD', 'Papan Tulis'],
                'images'      => [
                    'https://picsum.photos/seed/GKB1B302_1/800/600',
                    'https://picsum.photos/seed/GKB1B302_2/800/600',
                ],
                'is_active'   => false,
            ],
            [
                'name'        => 'Aula Mini FEB',
                'capacity'    => 100,
                'building'    => 'GKB 2',
                'description' => 'Aula serbaguna dengan kapasitas menengah untuk kegiatan organisasi mahasiswa atau kuliah tamu.',
                'facilities'  => ['AC', 'Sound System Standard', 'Proyektor LCD', 'Panggung Kecil'],
                'images'      => [
                    'https://picsum.photos/seed/AulaFEB_1/800/600',
                    'https://picsum.photos/seed/AulaFEB_2/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Ruang Diskusi Mandiri',
                'capacity'    => 15,
                'building'    => 'GKB 3',
                'description' => 'Ruang santai namun formal untuk diskusi kelompok kecil atau bimbingan akademik.',
                'facilities'  => ['AC', 'Whiteboard', 'Bean Bag Corner', 'Soket Listrik'],
                'images'      => [
                    'https://picsum.photos/seed/GKB3Diskusi_1/800/600',
                    'https://picsum.photos/seed/GKB3Diskusi_2/800/600',
                ],
                'is_active'   => true,
            ],
            [
                'name'        => 'Laboratorium Robotika & IoT (Internal)',
                'capacity'    => 30,
                'building'    => 'Lab Terpadu',
                'description' => 'Ruangan khusus penelitian internal yang tidak dibuka untuk peminjaman umum melalui aplikasi.',
                'facilities'  => ['AC', '3D Printer', 'Oscilloscope', 'High Speed Internet'],
                'images'      => [
                    'https://picsum.photos/seed/LabRobot_1/800/600',
                    'https://picsum.photos/seed/LabRobot_2/800/600',
                ],
                'is_active'   => false,
            ],
        ];

        foreach ($rooms as $roomData) {
            Room::create($roomData);
        }
    }
}
