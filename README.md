# Sangkuriang Equipment

Sistem peminjaman alat sekolah (multimedia & presentasi) untuk **SMK Sangkuriang 1
Cimahi**. Dipakai oleh siswa, guru, dan staff untuk meminjam alat secara online
maupun offline, dengan persetujuan petugas, pengembalian tercatat, dan denda
sebagai penalti (bukan biaya sewa).

## 1. Deskripsi

- Peminjaman **online**: peminjam memilih alat dan JP (jam pelajaran) hari itu;
  status awal `diajukan`, menunggu persetujuan admin/petugas.
- Peminjaman **offline**: petugas mencatat langsung; status langsung `dipinjam`.
- Tanggal peminjaman **selalu mengikuti tanggal server** (tidak ada date picker);
  jam mulai/selesai diambil otomatis dari tabel jadwal KBM berdasarkan JP yang
  dipilih.
- Keterlambatan dihitung dari **jadwal KBM sesungguhnya** (bukan "1 jam = 1 JP").
- Alat yang tidak dikembalikan sampai akhir KBM pada harinya otomatis berstatus
  **hilang** (denda tetap tercatat walau alat ditemukan kemudian).
- **Tidak ada biaya sewa, checkout, atau payment gateway.** Denda hanya
  penalti: keterlambatan, kerusakan, kehilangan.

## 2. Teknologi

PHP 8+ (OOP, custom MVC, PDO prepared statements) &middot; MySQL/MariaDB &middot;
HTML5/CSS3/JavaScript vanilla. Tanpa framework, tanpa Composer/npm wajib -
cukup XAMPP.

## 3. Struktur folder

```
peminjaman-alat-sekolah/
├── app/
│   ├── controllers/   # 1 class per fitur (AuthController, AlatController, ...)
│   ├── models/        # query PDO (1 class per tabel/domain)
│   └── core/           # Auth, Csrf, Database, Model, Controller, View,
│                       # Validator, JadwalService, SyncService, AppLog, ...
├── assets/
│   ├── css/app.css
│   ├── js/app.js
│   └── images/logo.svg
├── config/
│   ├── config.php       # koneksi DB, konstanta denda, dsb
│   ├── permissions.php  # matrix RBAC (permission per role)
│   ├── routes.php       # daftar route -> controller/method/permission
│   └── menu.php         # menu sidebar per role
├── database/
│   ├── database.sql     # 12 tabel + FK + function + procedure + trigger + event
│   ├── seed.sql          # data awal (jadwal KBM asli, kategori, alat, akun demo)
│   └── README.txt
├── storage/logs/         # log error server (dibuat otomatis)
├── views/                # 1 file per halaman + layouts/ (main, public, print)
├── index.php             # front controller tunggal
├── .htaccess
└── .gitignore
```

## 4. Cara menjalankan (XAMPP)

1. Salin folder ini ke `htdocs/` (atau `git clone` langsung ke dalamnya).
2. Buat database: import `database/database.sql` lalu `database/seed.sql`
   (lihat `database/README.txt` untuk detail & akun demo).
3. Sesuaikan kredensial di `config/config.php` bila perlu (`DB_HOST`,
   `DB_USER`, `DB_PASS`; default cocok untuk XAMPP root tanpa password).
4. Pastikan modul Apache `mod_rewrite` aktif (untuk `.htaccess`).
5. Buka `http://localhost/peminjaman-alat-sekolah/`.

Untuk hosting: upload seluruh isi folder ke document root, import database yang
sama, sesuaikan `config/config.php`, pastikan `.htaccess` didukung (Apache) atau
sesuaikan rule setara untuk Nginx (arahkan semua request ke `index.php`).

## 5. Akun demo

| Username   | Password      | Role     |
|------------|---------------|----------|
| `admin`    | `admin123`    | admin    |
| `petugas`  | `petugas123`  | petugas  |
| `peminjam` | `peminjam123` | peminjam (siswa, X RPL 1) |
| `guru`     | `guru12345`   | peminjam (guru) |

Registrasi publik (`/index.php?route=register`) hanya membuat akun **peminjam**;
role tidak pernah berasal dari input pengguna.

## 6. Role & RBAC

RBAC diterapkan lewat matrix permission (`config/permissions.php`) dan dicek di
**server** pada setiap route (`Auth::requirePermission()` di `index.php`), bukan
sekadar menyembunyikan menu.

- **Admin**: dashboard, CRUD user/kategori/alat, seluruh peminjaman, approval,
  pengembalian, laporan, log aktivitas.
- **Petugas**: dashboard, menyetujui/menolak peminjaman, mencatat peminjaman
  offline, memproses pengembalian, laporan.
- **Peminjam**: melihat alat, mengajukan peminjaman, melihat riwayat/status
  miliknya sendiri saja.

## 7. Keamanan

- Password: `password_hash()` / `password_verify()` (bcrypt), tidak pernah MD5/SHA1/plaintext.
- Login: percobaan gagal dibatasi per IP (lihat `LOGIN_MAX_GAGAL` di config), waktu
  respons disamakan (dummy hash) agar username valid/tidak tidak mudah dibedakan.
- **CSRF**: token per sesi, wajib pada semua POST (dicek di `index.php`, satu titik
  untuk seluruh aplikasi).
- **IDOR**: query milik peminjam selalu menyertakan `WHERE id_peminjam = <user
  session>` di level SQL (`PeminjamanModel::findFor`, `paginate` dengan `$owner`),
  bukan sekadar cek "sudah login".
- **URL tampering**: role/permission selalu dibaca dari session yang disegarkan dari
  database setiap request (`Auth::boot()`); tidak pernah dari `$_GET`/`$_POST`/cookie.
- SQL: seluruh query memakai PDO prepared statement (parameter binding), tanpa
  interpolasi string, `PDO::ATTR_EMULATE_PREPARES => false`.
- Output di-escape via `htmlspecialchars()` (`e()`).
- Error teknis (exception, SQLSTATE) tidak pernah ditampilkan ke pengguna;
  dicatat ke `storage/logs/`, pengguna hanya melihat "Terjadi kesalahan. Silakan
  coba lagi." Business error (mis. "Kategori masih dipakai") ditampilkan apa adanya.
- Header keamanan dasar: `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`.
- Session: `session_regenerate_id(true)` saat login & logout, `HttpOnly`,
  `SameSite=Lax`, tidak menyimpan password di session.
- Transaksi kritis (peminjaman, pengembalian, sinkronisasi status) memakai
  `START TRANSACTION` + `FOR UPDATE` (row locking) untuk mencegah race condition
  saat alat yang sama dipinjam bersamaan.

## 8. Fitur

Login/logout, register (peminjam), CRUD user/kategori/alat, peminjaman online &
offline, approval/reject, pengembalian dengan penghitungan denda otomatis
(function SQL `hitung_denda_keterlambatan/kerusakan/kehilangan`), kehilangan
otomatis saat KBM berakhir (event scheduler + fallback aplikasi), riwayat &
status milik sendiri, laporan (peminjaman/pengembalian/denda) dengan filter +
cetak, log aktivitas dengan filter, pencarian & filter pada semua tabel data,
pagination, responsive (desktop/tablet/mobile).

## 9. Import database & konfigurasi

Lihat `database/README.txt` untuk langkah import lengkap dan detail Event
Scheduler (opsional; aplikasi punya fallback jika Event Scheduler tidak aktif -
lihat `app/core/SyncService.php`, dipanggil pada setiap request oleh pengguna
yang login serta dipaksa sebelum approve/pengembalian).

## 10. Troubleshooting

- **Halaman putih / 500** - aktifkan sementara `APP_ENV = 'development'` di
  `config/config.php` untuk melihat pesan error, atau cek `storage/logs/app-*.log`.
- **404 pada semua halaman selain beranda** - `mod_rewrite` belum aktif atau
  `.htaccess` tidak dibaca (cek `AllowOverride All` pada konfigurasi Apache).
- **Error koneksi database** - cek `DB_HOST`/`DB_USER`/`DB_PASS`/`DB_NAME` di
  `config/config.php`, pastikan `database.sql` sudah diimpor lebih dulu.
- **Denda/keterlambatan tidak sesuai ekspektasi** - denda dihitung dari
  `jadwal_kbm`, bukan jam manual; pastikan `seed.sql` (atau data jadwal Anda
  sendiri) sudah lengkap untuk kelompok jadwal yang relevan.
- **Login gagal terus walau password benar** - cek pembatas percobaan gagal
  (`LOGIN_MAX_GAGAL` per `LOGIN_JENDELA_MENIT` menit dari IP yang sama).

## 11. Pengujian & catatan jujur

Lingkungan pembuatan proyek ini **tidak menyediakan PHP CLI maupun server
MySQL/MariaDB**, sehingga pengujian yang dilakukan adalah **validasi statis**
(tidak menjalankan aplikasi sungguhan):

- Parsing kurung/kurawal setiap file PHP untuk mendeteksi ketidakseimbangan.
- Cross-check seluruh kelas yang dipakai (`new X`, `X::`, `extends X`) terhadap
  kelas yang benar-benar didefinisikan.
- Cross-check seluruh `route` di `config/routes.php` terhadap method controller
  yang benar-benar ada, dan seluruh permission terhadap `config/permissions.php`.
- Cross-check seluruh view yang dirender/`partial()` terhadap file yang ada.
- Review manual baris-per-baris seluruh `database.sql` (function, procedure,
  trigger, event) untuk urutan `DECLARE`, penanganan `SIGNAL`/`RESIGNAL`,
  dan konsistensi tipe data.
- Verifikasi manual jadwal KBM di `seed.sql` terhadap tabel asli pada
  `JAM_KBM_26-27.pdf` (termasuk pola "Kamis mengikuti Rabu" dan pengecualian JP 11
  yang hanya ada di Rabu untuk kelas XI/XII).
- Verifikasi hash password demo (`password_hash`/`password_verify`, bcrypt
  `$2y$10$...`) benar-benar valid lewat `crypt()` Python (algoritma yang sama).
- Sintaks JavaScript (`assets/js/app.js`) diverifikasi dengan `node --check`.

**Yang belum bisa diuji** (karena tidak ada PHP/MySQL untuk menjalankan
aplikasi sungguhan): eksekusi nyata seluruh 28 skenario uji di bawah,
kompatibilitas versi PHP/MySQL spesifik di server Anda, dan perilaku visual
sebenarnya di browser. Disarankan menjalankan ulang skenario berikut setelah
deploy ke XAMPP/hosting:

1. Login admin / petugas / peminjam - berhasil sesuai role, redirect ke dashboard masing-masing.
2. Login salah password - gagal, pesan error, dicatat sebagai `LOGIN_GAGAL`.
3. Register user baru - berhasil, role otomatis `peminjam`.
4. Admin CRUD user / kategori / alat - berhasil, tervalidasi, tercatat di log.
5. Peminjam melihat daftar alat - hanya alat berstatus tersedia yang bisa dipilih untuk dipinjam.
6. Peminjam mengajukan peminjaman online - JP & jam terisi otomatis, status `diajukan`.
7. Petugas approve pengajuan - status jadi `dipinjam`, alat jadi `dipinjam`.
8. Petugas reject pengajuan - status jadi `ditolak`, alat tetap `tersedia`.
9. Peminjaman offline oleh petugas - status langsung `dipinjam`.
10. Pengembalian kondisi baik, tepat waktu - tidak ada denda.
11. Pengembalian terlambat - denda Rp5.000 x JP terlambat (dihitung dari `jadwal_kbm`).
12. Pengembalian rusak ringan / rusak berat - denda Rp10.000 / Rp50.000 per unit.
13. Alat tidak dikembalikan sampai akhir KBM - otomatis `hilang`, denda Rp100.000, tercatat di log.
14. Alat hilang lalu ditemukan & dikembalikan - status alat bisa `tersedia` lagi, denda kehilangan tetap tercatat (histori tidak dihapus).
15. Laporan peminjaman/pengembalian/denda - filter tanggal/status/jenis/peminjam/alat/kategori berfungsi, tombol cetak menampilkan halaman print yang bisa di-print.
16. Log aktivitas - mencatat LOGIN/LOGOUT/CREATE/UPDATE/DELETE/APPROVE/REJECT/BORROW/RETURN/DAMAGE/LOST/FINE.
17. Peminjam membuka `?route=dashboard-admin` langsung - **403 Forbidden**.
18. Peminjam membuka `?route=peminjaman-detail&id=<milik user lain>` - **403 Forbidden** (bukan 404, karena route valid tapi kepemilikan gagal).
19. Percobaan mengubah role lewat parameter POST tambahan (`role=admin`) pada form yang tidak menyediakan field itu - diabaikan (`only()` hanya membaca kunci yang di-whitelist controller).
20. SQL injection dasar pada field pencarian (`q=' OR '1'='1`) - aman (prepared statement, `LIKE` di-escape lewat `Model::like()`).
21. CSRF: submit form tanpa token / token salah - **403**, "Sesi formulir tidak valid atau kedaluwarsa."
22. Dua transaksi mencoba meminjam alat yang sama nyaris bersamaan - hanya satu yang berhasil (row locking `FOR UPDATE` di `PeminjamanModel::lockAlat`).

## 12. Rencana pengembangan berikutnya

- Notifikasi email/WA saat pengajuan disetujui/ditolak atau mendekati batas kembali.
- Upload foto kondisi alat saat pengembalian.
- Ekspor laporan ke PDF/Excel (saat ini cetak memakai halaman print HTML).
- Riwayat perubahan (audit trail) per kolom, bukan hanya log aktivitas berbasis teks.
