# RoomSense Notification Feature — Testing Checklist

> 📌 **Prerequisites**

- [ ] `QUEUE_CONNECTION=database` ada di `.env`
- [ ] `MAIL_MAILER=log` ada di `.env` (untuk development)
- [ ] Queue worker berjalan: `composer run dev` atau `php artisan queue:work`
- [ ] Scheduler berjalan: `php artisan schedule:work` (di terminal terpisah)
- [ ] Run seeder: `php artisan db:seed --class=NotificationTestSeeder`
- [ ] Kredensial siap: 1 admin user, 1 regular user (`test@smartspace.app` / `password123`)

---

## 🔹 Test Flow 1: Submission Notification (Real-time)

1. Login sebagai user biasa (`test@smartspace.app` / `password123`)
2. Submit booking baru via `/book` dengan tanggal besok
3. ✅ Verifikasi database:
   - [ ] Tabel `notifications` memiliki 2 entry baru (1 untuk user, 1 untuk admin)
   - [ ] `storage/logs/laravel.log` memiliki log email untuk user dengan subject "Booking Submitted"
   - [ ] Bell icon di navbar menampilkan badge merah "1"
4. Klik bell icon → dropdown muncul
5. ✅ Verifikasi UI:
   - [ ] Notifikasi "Booking Submitted" muncul dengan icon ⏳
   - [ ] Waktu ditampilkan dalam WIB (Asia/Jakarta)
   - [ ] Klik notifikasi → redirect ke `/dashboard` + badge berkurang jadi 0

---

## 🔹 Test Flow 2: Approval Notification (Admin Action)

1. Login sebagai admin
2. Buka `/admin/bookings` → temukan booking dengan status `pending`
3. Approve booking dengan notes opsional (e.g., "Disetujui untuk rapat fakultas")
4. ✅ Verifikasi (di akun user):
   - [ ] User mendapat notifikasi In-App "Booking Approved" dengan icon ✅
   - [ ] Bell icon user menampilkan badge baru
   - [ ] Email log berisi subject "✅ Booking Approved" + detail ruangan + admin notes
   - [ ] Status booking di database berubah menjadi `approved`

---

## 🔹 Test Flow 3: Rejection Notification (Admin Action)

1. Admin reject booking dengan notes "Alasan: bentrok jadwal"
2. ✅ Verifikasi (di akun user):
   - [ ] User mendapat notifikasi rejection dengan icon ❌
   - [ ] Message berisi alasan: "Reason: bentrok jadwal"
   - [ ] Email log berisi subject "❌ Booking Rejected"
   - [ ] Status booking di database berubah menjadi `rejected`

---

## 🔹 Test Flow 4: Reminder H-24 (via Scheduler)

1. Pastikan ada booking dengan `start_time = now() + 23.5 jam` (UTC) — sudah di-seed sebagai "Test H-24 Reminder"
2. Verifikasi data seed: `SELECT purpose, start_time, reminder_24h_sent FROM bookings WHERE purpose = 'Test H-24 Reminder';`
3. Jalankan scheduler manual: `php artisan schedule:run --force`
4. Proses queue: `php artisan queue:work --once`
5. ✅ Verifikasi:
   - [ ] User mendapat notifikasi "⏰ Reminder: Booking Tomorrow"
   - [ ] Tabel `bookings`: kolom `reminder_24h_sent` = `1` (true) untuk booking tersebut
   - [ ] Email log berisi subject dengan kata "Tomorrow"
   - [ ] Jalankan ulang scheduler → tidak ada duplikasi notifikasi (idempotent)

---

## 🔹 Test Flow 5: Reminder H-2 (via Scheduler)

1. Pastikan ada booking dengan `start_time = now() + 100 menit` (UTC) — sudah di-seed sebagai "Test H-2 Reminder"
2. Verifikasi data seed: `SELECT purpose, start_time, reminder_2h_sent FROM bookings WHERE purpose = 'Test H-2 Reminder';`
3. Jalankan scheduler manual: `php artisan schedule:run --force`
4. Proses queue: `php artisan queue:work --once`
5. ✅ Verifikasi:
   - [ ] User mendapat notifikasi "🔔 Starting Soon"
   - [ ] Tabel `bookings`: kolom `reminder_2h_sent` = `1` (true)
   - [ ] Email log berisi subject "Starting Soon"
   - [ ] Jalankan ulang scheduler → tidak ada duplikasi notifikasi (idempotent)

---

## 🔹 Test Flow 6: Post-Booking Notification (via Scheduler)

1. Pastikan ada booking dengan `end_time = now() - 65 menit` (UTC) — sudah di-seed sebagai "Test Post-Booking"
2. Verifikasi data seed: `SELECT purpose, end_time, post_booking_sent FROM bookings WHERE purpose = 'Test Post-Booking';`
3. Jalankan scheduler manual: `php artisan schedule:run --force`
4. Proses queue: `php artisan queue:work --once`
5. ✅ Verifikasi:
   - [ ] User mendapat notifikasi "📋 Booking Completed - Thank You!"
   - [ ] Tabel `bookings`: kolom `post_booking_sent` = `1` (true)
   - [ ] Email log berisi subject "Booking Completed"
   - [ ] Jalankan ulang scheduler → tidak ada duplikasi notifikasi (idempotent)

---

## 🔹 Test Flow 7: Cancellation Notification (User Action)

1. Login sebagai user → buka `/dashboard`
2. Cancel booking yang masih `pending` (booking "Test Submission Flow" dari Flow 1)
3. ✅ Verifikasi:
   - [ ] User mendapat notifikasi "🚫 Booking Cancelled" dengan icon 🚫
   - [ ] Tabel `bookings`: `status` = `cancelled`, `cancelled_by` = user ID, `cancelled_at` terisi (UTC)
   - [ ] Booking tidak bisa dicancel ulang (guard berfungsi → muncul error "Only pending bookings can be cancelled")
   - [ ] Email log berisi subject "🚫 Booking Cancelled"

---

## 🔹 Test Flow 8: In-App UI Interactions

1. Buka `/notifications` sebagai user
2. ✅ Verifikasi halaman notifikasi:
   - [ ] Filter tab **All** menampilkan semua notifikasi
   - [ ] Filter tab **Unread** (`?filter=unread`) hanya tampilkan yang belum dibaca + badge jumlah
   - [ ] Filter tab **Read** (`?filter=read`) hanya tampilkan yang sudah dibaca
   - [ ] Tombol "Mark all as read" berfungsi → semua notifikasi jadi read + badge hilang dari navbar
   - [ ] Pagination links berfungsi jika terdapat lebih dari 15 notifikasi
   - [ ] Tombol "Mark as read" per-item berfungsi (form PATCH)
   - [ ] Tombol "View Details" redirect ke `action_url` yang sesuai
3. Klik bell icon di navbar → dropdown muncul
4. ✅ Verifikasi dropdown bell:
   - [ ] Maksimal 5 notifikasi terbaru ditampilkan di dropdown
   - [ ] Waktu ditampilkan dalam format WIB (Asia/Jakarta) — e.g., "2 menit lalu"
   - [ ] Notifikasi yang belum dibaca memiliki dot biru dan latar sedikit lebih terang
   - [ ] Klik item → redirect ke `action_url` + notifikasi ter-mark as read (via AJAX PATCH)
   - [ ] Badge count berkurang tanpa page reload penuh
   - [ ] Klik "Mark all as read" di dropdown → badge hilang + dropdown tertutup + halaman reload

---

## 🔹 Test Flow 9: Admin Notification (In-App Only)

1. Login sebagai user biasa → submit booking baru
2. Login sebagai admin di browser/tab lain
3. ✅ Verifikasi (di akun admin):
   - [ ] Admin mendapat notifikasi In-App "📋 New Booking Request" (channel: `database` only)
   - [ ] Bell icon admin menampilkan badge baru
   - [ ] Klik notifikasi → redirect ke `/admin/bookings`
   - [ ] **Tidak ada** email log untuk admin (hanya user yang mendapat email)

---

## 🚀 Production Readiness Checklist

> ⚠️ Item berikut **WAJIB** diperiksa sebelum deploy ke production

- [ ] Ganti `MAIL_MAILER=log` ke `smtp` di `.env` production
- [ ] Konfigurasi SMTP: `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`
- [ ] Verifikasi domain email sudah terverifikasi (SPF/DKIM/DMARC records di DNS)
- [ ] Daftarkan scheduler di crontab server: `* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Setup Supervisor/Systemd untuk queue worker: `php artisan queue:work --queue=notifications,default --sleep=3 --tries=3`
- [ ] Pastikan direktori `storage/logs/` writable oleh web server user
- [ ] Set `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] Jalankan `php artisan config:cache` dan `php artisan route:cache` setelah deploy
- [ ] Test end-to-end di staging environment dengan data real sebelum go-live

---

## 🐛 Troubleshooting Guide

| Issue | Possible Cause | Solution |
|-------|----------------|----------|
| Notifikasi tidak terkirim | Queue worker tidak running | Jalankan `php artisan queue:work` atau cek `composer run dev` |
| Email tidak muncul di log | `MAIL_MAILER` bukan `log` | Set `MAIL_MAILER=log` di `.env` untuk development |
| Scheduler tidak jalan | `schedule:work` tidak aktif / cron tidak terdaftar | Jalankan `php artisan schedule:work` di terminal terpisah |
| Reminder terkirim duplikat | Flag tidak terupdate / job crash sebelum `update()` | Cek kolom `reminder_24h_sent` / `reminder_2h_sent` / `post_booking_sent` di tabel `bookings` |
| Badge tidak update (AJAX) | CSRF token missing / route 404 | Buka DevTools → Network tab → cek header `X-CSRF-TOKEN` dan response status |
| Dropdown bell tidak muncul | Alpine.js tidak terload | Pastikan `@vite(['resources/js/app.js'])` ada di layout dan Alpine diimport di `app.js` |
| Waktu salah timezone | Konversi dilakukan di luar Blade | Pastikan `->timezone('Asia/Jakarta')` hanya digunakan di Blade/View, bukan Model atau Controller |
| `abort(403)` saat mark-as-read | User coba akses notifikasi milik orang lain | Guard sudah benar — ini perilaku yang diharapkan |
| `STATUS_CANCELLED` typo error | Nama constant salah | Gunakan `Booking::STATUS_CANCELLED` (bukan `CANCELED` tanpa huruf L) |

---

## 🎭 Demo Account
- Email: `user@webmail.umm.demo` (atau sesuai `DEMO_USER_EMAIL` di `.env`)
- Password: `password`
- Catatan: Akun pre-verified, tidak menerima email sungguhan. Gunakan `MAIL_MAILER=log` dan `QUEUE_CONNECTION=sync` di `.env` untuk demo.
- Checklist:
  - [ ] Login demo sukses tanpa verifikasi email
  - [ ] Flash message warning muncul di dashboard
  - [ ] Flow booking berjalan normal
  - [ ] Notifikasi in-app (bell) berfungsi
  - [ ] Tidak ada error mail di `storage/logs/laravel.log`
