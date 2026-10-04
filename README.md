<div align="center">

<br>

<img src="https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-8.3+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white" alt="Tailwind CSS">
<img src="https://img.shields.io/badge/Vite-8.x-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
<img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="MIT License">

<br><br>

# 📊 RoomSense

### *Sistem Manajemen Peminjaman Ruangan Kampus Premium*

**RoomSense** adalah aplikasi web full-stack yang berfungsi sebagai platform manajemen peminjaman ruangan kampus yang efisien dan terstruktur. Mengakomodasi kebutuhan sivitas akademika dalam mencari, mereservasi, dan mengelola jadwal penggunaan ruangan dengan mudah, transparan, dan bebas bentrok.

[🌐 Demo Live](#) · [🐛 Laporkan Bug](../../issues) · [✨ Request Fitur](../../issues)

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Tech Stack](#-tech-stack)
- [Arsitektur & Struktur Proyek](#-arsitektur--struktur-proyek)
- [Instalasi & Setup](#-instalasi--setup)
- [Konfigurasi Environment](#-konfigurasi-environment)
- [Database & Seeding](#-database--seeding)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Panduan Pengguna](#-panduan-pengguna)
- [Panduan Admin](#-panduan-admin)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

---

## 📊 Tentang Proyek

**RoomSense** lahir dari kebutuhan akan sistem reservasi fasilitas kampus yang andal. Platform ini tidak hanya menjadi direktori ruangan yang tersedia, tetapi juga mengotomatisasi alur persetujuan peminjaman sehingga birokrasi menjadi lebih cepat dan efisien.

### Mengapa RoomSense?

- 🏫 **Manajemen Terpusat** — Seluruh data ruangan kampus dan fasilitasnya terkelola dalam satu platform.
- 🗓️ **Sistem Booking Aman** — Mencegah *double booking* dengan validasi *race condition* dan pengecekan ketersediaan jadwal secara ketat.
- 🔔 **Notifikasi Terintegrasi** — Memberikan informasi kepada pengguna mengenai status *approval* peminjaman ruangan.
- 🔒 **Keamanan & Role-Based** — Memisahkan hak akses antara Admin (persetujuan & manajemen) dan User biasa (peminjam) dengan *access control* berlapis.

---

## ✨ Fitur Utama

### 👤 Untuk Peminjam (User)
| Fitur | Deskripsi |
|---|---|
| **Daftar Ruangan** | Halaman pencarian & filter ruangan yang tersedia berdasarkan waktu, kapasitas, maupun fasilitas. |
| **Detail Ruangan** | Informasi lengkap mengenai fasilitas ruangan, foto, kapasitas maksimal, dan jadwal ketersediaan. |
| **Form Reservasi** | Fitur untuk melakukan *booking* pada rentang waktu tertentu. Sistem akan memvalidasi agar jadwal tidak beririsan. |
| **Dashboard Peminjam** | Ringkasan riwayat peminjaman, status *booking* (pending, approved, rejected), dan manajemen profil. |
| **Notifikasi Status** | Notifikasi kepada user jika peminjaman disetujui atau ditolak (beserta alasan penolakan). |

### 🛡️ Untuk Admin
| Fitur | Deskripsi |
|---|---|
| **Dashboard Admin** | Statistik ringkasan: total ruangan, *booking* masuk, *booking* disetujui, dan yang masih berstatus pending. |
| **Manajemen Ruangan (CRUD)** | Tambah, edit, nonaktifkan, dan hapus data ruangan kampus beserta fasilitasnya. |
| **Approval Peminjaman** | Fitur persetujuan peminjaman (*approve/reject*). Admin dapat menyetujui peminjaman atau menolaknya dengan wajib memberikan alasan. |
| **Manajemen Pengguna** | Melihat dan mengelola data sivitas akademika atau pengguna sistem. |

---

## 🛠️ Tech Stack

### Backend
- **[Laravel 13](https://laravel.com/)** — PHP Framework (MVC)
- **PHP 8.3+**
- **[Pusher / Laravel Echo](https://laravel.com/docs/broadcasting)** — Untuk fitur notifikasi real-time

### Frontend
- **Blade Templates** — Templating engine Laravel
- **[Tailwind CSS v4](https://tailwindcss.com/)** — Utility-first CSS framework
- **[Vite 8](https://vite.dev/)** — Build tool & dev server frontend cepat
- **Vanilla JavaScript** — Interaktivitas UI dan penanganan form dinamis

### Database & Storage
- **SQLite** (default development) / **MySQL** (production)
- **Laravel File Storage** — Manajemen upload gambar/foto ruangan

### Development Tools
- **[Laravel Pail](https://github.com/laravel/pail)** — Log viewer real-time di terminal
- **[Laravel Pint](https://github.com/laravel/pint)** — PHP code style fixer
- **[PHPUnit 12](https://phpunit.de/)** — Unit testing

---

## 📁 Arsitektur & Struktur Proyek

Proyek ini mengikuti arsitektur standar **Laravel MVC (Model-View-Controller)** dengan pemisahan akses untuk logika admin dan pengguna biasa.

```
room-sense/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/                  # Controller khusus Admin (RoomController, BookingController)
│   │   │   ├── Auth/                   # Controller Autentikasi
│   │   │   ├── AuthController.php      # Login, Register
│   │   │   ├── BookingController.php   # Manajemen reservasi user
│   │   │   ├── RoomController.php      # Daftar & pencarian ruangan
│   │   │   └── NotificationController.php # Manajemen notifikasi user
│   ├── Models/
│   │   ├── User.php            # Model Pengguna & Role
│   │   ├── Room.php            # Model Ruangan & Fasilitas
│   │   └── Booking.php         # Model Peminjaman Ruangan
│
├── database/
│   ├── factories/              # Factory untuk seeder data
│   ├── migrations/             # Skema database (Users, Rooms, Bookings, dsb)
│   └── seeders/                # Data awal ruangan dan akun dummy
│
├── resources/
│   ├── views/                  # UI aplikasi menggunakan Blade
│   └── js/ & css/              # Konfigurasi TailwindCSS v4 dan Vite
│
└── tests/                      # Skenario pengujian kualitas & unit tests
```

### Skema Database Utama

| Tabel | Kolom Kunci |
|---|---|
| `users` | `name`, `email`, `password`, `role (admin/user)` |
| `rooms` | `name`, `capacity`, `facilities`, `image`, `status (active/inactive)` |
| `bookings` | `user_id`, `room_id`, `start_time`, `end_time`, `purpose`, `status (pending/approved/rejected)`, `reject_reason` |

---

## 🚀 Instalasi & Setup

### Prasyarat

Pastikan lingkungan pengembangan Anda memiliki:

- **PHP** `>= 8.3`
- **Composer** `>= 2.x`
- **Node.js** `>= 18.x` & **npm**
- **Git**

### Langkah 1: Clone Repositori

```bash
git clone https://github.com/NaovalOcta/RoomSense.git
cd room-sense
```

### Langkah 2: Setup Otomatis (Direkomendasikan)

Jalankan script instalasi bawaan yang akan mengurus instalasi package, file `.env`, generate key, migrasi, dan build aset frontend:

```bash
composer run setup
```

### Langkah 3: Setup Manual (Alternatif)

Jika ingin melakukan instalasi manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

---

## ⚙️ Konfigurasi Environment

Buka file `.env` dan sesuaikan pengaturan seperti database atau URL jika diperlukan:

```env
APP_NAME=RoomSense
APP_ENV=local
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=sqlite
# Gunakan MySQL untuk production:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=roomsense
```

---

## 🗃️ Database & Seeding

Jika Anda ingin mengisi database dengan data dummy (ruangan contoh dan akun default):

```bash
php artisan migrate --seed
```

Atau untuk me-reset ulang seluruh data:

```bash
php artisan migrate:fresh --seed
```

---

## ▶️ Menjalankan Aplikasi

Jalankan server aplikasi dan Vite asset compiler melalui perintah ini:

```bash
composer run dev
```

Perintah di atas akan menjalankan secara paralel:
1. `php artisan serve` (Web Server di http://localhost:8000)
2. `php artisan queue:listen` (Antrean background)
3. `php artisan pail` (Log Terminal)
4. `npm run dev` (Vite Server HMR)

---

## 📖 Panduan Pengguna

### Meminjam Ruangan
1. **Daftar/Login** menggunakan akun Anda.
2. Buka menu **Daftar Ruangan** dan cari ruangan yang sesuai dengan kebutuhan.
3. Klik **Booking**, lalu pilih jadwal (Tanggal & Jam mulai - selesai).
4. Isi tujuan kegiatan.
5. Klik **Submit Booking**. Peminjaman Anda akan masuk dengan status **Pending**.
6. Anda bisa melihat status peminjaman di **Dashboard**. Jika ditolak, Anda dapat melihat alasan penolakan dari admin.

---

## 🛡️ Panduan Admin

### Approval Peminjaman
1. Login dengan kredensial Admin.
2. Masuk ke panel **Dashboard Admin**.
3. Buka menu **Approval Booking** untuk melihat daftar peminjaman yang masuk (*Pending*).
4. Klik **Approve** jika jadwal dan tujuan sesuai.
5. Klik **Reject** jika ruangan tidak dapat digunakan, dan **wajib** mengisi alasan penolakan.

### Mengelola Ruangan
1. Di panel Admin, buka menu **Manajemen Ruangan**.
2. Klik **Tambah Ruangan** dan lengkapi detail seperti kapasitas, nama, serta foto ruangan.
3. Ruangan yang sedang dalam perbaikan bisa di-set statusnya menjadi tidak aktif agar tidak bisa di-booking.

---

## 🤝 Kontribusi

Kontribusi sangat disambut! Ikuti langkah berikut:

1. **Fork** repositori ini
2. Buat branch fitur baru: `git checkout -b feat/nama-fitur-baru`
3. Commit perubahan Anda: `git commit -m 'feat: tambah fitur baru'`
4. Push ke branch: `git push origin feat/nama-fitur-baru`
5. Buat **Pull Request**

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah **MIT License**. Lihat file `LICENSE` untuk detail lengkap.

---

<div align="center">

**RoomSense** — *Smart & Easy Campus Room Booking System* 📊

</div>
