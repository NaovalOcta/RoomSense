# 📊 RoomSense — Software Quality Testing Report

## 📌 Deskripsi Sistem

**RoomSense** adalah sistem berbasis web untuk peminjaman ruangan kampus yang dikembangkan menggunakan:

* **Backend**: Laravel 13
* **Frontend**: Tailwind CSS, JavaScript
* **Database**: MySQL

Sistem ini mendukung dua peran utama:

* **User**: melakukan booking ruangan dan melihat status
* **Admin**: mengelola ruangan dan melakukan approval booking

---

## 🎯 Tujuan Pengujian

Pengujian dilakukan untuk mengevaluasi kualitas sistem berdasarkan aspek:

* Performance (Kinerja)
* Reliability (Keandalan)
* Usability (Kemudahan penggunaan)
* Security (Keamanan)
* Data Consistency (Konsistensi data)

Pendekatan pengujian berfokus pada:

* Skenario penggunaan nyata
* Edge cases
* Validasi logika sistem
* Role-based access control

> ⚠️ Catatan: Pengujian dilakukan pada lingkungan free hosting, sehingga tidak mencakup stress testing skala besar.

---

## 🧪 Hasil Pengujian

| No | Aspek Kualitas   | Skenario Uji                      | Hasil yang Diharapkan | Status                   |
| -- | ---------------- | --------------------------------- | --------------------- | ------------------------ |
| 1  | Performance      | Load halaman daftar ruangan       | < 2 detik             | ⚠️ Perlu optimasi        |
| 2  | Performance      | Dashboard user dengan banyak data | Responsif             | ✅ Baik                   |
| 3  | Performance      | Dashboard admin (banyak booking)  | < 2 detik             | ⚠️ Perlu optimasi query  |
| 4  | Performance      | Upload banyak gambar              | Tidak timeout         | ⚠️ Tergantung hosting    |
| 5  | Performance      | Rekomendasi ruangan alternatif    | Cepat & efisien       | ⚠️ Perlu optimasi        |
| 6  | Reliability      | Booking di tanggal hari ini       | Valid                 | ⚠️ Risiko timezone       |
| 7  | Reliability      | Validasi waktu booking            | Tidak boleh sama      | ✅ Valid                  |
| 8  | Reliability      | Cancel booking approved           | Ditolak               | ✅ Valid                  |
| 9  | Reliability      | Cancel booking user lain          | Ditolak (403)         | ✅ Aman                   |
| 10 | Reliability      | Nonaktifkan ruangan aktif         | Konsisten             | ⚠️ Perlu notifikasi      |
| 11 | Usability        | Login salah password              | Error jelas           | ✅ Baik                   |
| 12 | Usability        | Booking dari detail ruangan       | Auto select           | ⚠️ UX bisa membingungkan |
| 13 | Usability        | Approve booking                   | Berhasil & jelas      | ✅ Baik                   |
| 14 | Usability        | Reject tanpa alasan               | Harus ditolak         | ⚠️ Validasi server perlu |
| 15 | Usability        | Redirect berdasarkan role         | Benar                 | ✅ Baik                   |
| 16 | Security         | Akses admin oleh user biasa       | Ditolak               | ✅ Aman                   |
| 17 | Security         | Akses tanpa login                 | Redirect login        | ✅ Aman                   |
| 18 | Security         | SQL Injection                     | Tidak berhasil        | ✅ Aman                   |
| 19 | Security         | XSS                               | Tidak dieksekusi      | ✅ Aman                   |
| 20 | Security         | CSRF attack                       | Ditolak               | ✅ Aman                   |
| 21 | Security         | Mass assignment                   | Aman                  | ✅ Aman                   |
| 22 | Security         | Upload file berbahaya             | Ditolak               | ✅ Aman                   |
| 23 | Data Consistency | Double booking (pending)          | Masih diperbolehkan   | ✅ Sesuai desain          |
| 24 | Data Consistency | Double approval (race condition)  | Harus dicegah         | ❌ Perlu perbaikan        |
| 25 | Data Consistency | Hapus ruangan dengan histori      | Data tetap aman       | ⚠️ Perlu soft delete     |

---

## 🔴 Temuan Utama

### 1. Potensi Race Condition pada Approval Booking

Jika dua admin melakukan approval secara bersamaan pada booking yang konflik, sistem berpotensi menyetujui keduanya.

**Dampak:**

* Double booking pada waktu yang sama

---

### 2. Validasi Alasan Penolakan (Reject) Belum Konsisten

Validasi hanya dilakukan di sisi frontend (HTML), belum di backend.

**Dampak:**

* Booking bisa ditolak tanpa alasan

---

### 3. Optimasi Query (N+1 Problem)

Beberapa fitur seperti:

* Daftar ruangan
* Rekomendasi alternatif

masih melakukan query berulang.

**Dampak:**

* Performa menurun saat data banyak

---

## 🛠️ Rekomendasi Perbaikan

### ✔ 1. Perbaikan Race Condition

Gunakan:

* Database transaction
* Locking (`lockForUpdate()` jika memungkinkan)

---

### ✔ 2. Validasi Backend

Tambahkan validasi:

* Alasan reject wajib diisi saat status = rejected

---

### ✔ 3. Optimasi Query

Gunakan:

* Eager loading (`with()`)
* Query aggregation
* Hindari loop query (N+1)

---

### ✔ 4. Pengaturan Timezone

Set:

```env
APP_TIMEZONE=Asia/Jakarta
```

---

### ✔ 5. Gunakan Soft Delete

Agar histori data tidak hilang saat ruangan dihapus.

---

## 📊 Ringkasan Kualitas Sistem

| Aspek            | Status               |
| ---------------- | -------------------- |
| Performance      | ⚠️ Perlu optimasi    |
| Reliability      | ✅ Baik               |
| Usability        | ✅ Baik               |
| Security         | ✅ Aman               |
| Data Consistency | ⚠️ Perlu peningkatan |

---

## 📈 Kesimpulan

Secara keseluruhan, sistem **RoomSense**:

* ✔ Sudah berjalan dengan baik secara fungsional
* ✔ Aman dari sisi keamanan dasar
* ✔ Memiliki struktur sistem yang solid

Namun masih terdapat beberapa hal yang perlu ditingkatkan:

* Optimasi performa
* Penanganan concurrency
* Validasi backend

Dengan perbaikan tersebut, sistem akan menjadi lebih stabil, scalable, dan siap digunakan dalam skenario nyata.

---

## 📌 Keterkaitan dengan Daily Project 6

Pengujian ini dilakukan berdasarkan hasil analisis kompetitor pada Daily Project 6, yang menghasilkan identifikasi aspek kualitas utama:

* Performance
* Reliability
* Usability
* Security
* Data Consistency

Sehingga pengujian ini merupakan implementasi langsung dari kebutuhan kualitas sistem yang telah dianalisis sebelumnya.
