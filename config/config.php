<?php
/**
 * Konfigurasi aplikasi Sangkuriang Equipment.
 * Sesuaikan DB_* dengan server MySQL/MariaDB Anda.
 */
define('APP_NAME', 'Sangkuriang Equipment');
define('APP_SCHOOL', 'SMK Sangkuriang 1 Cimahi');
define('APP_ENV', 'production');          // 'development' => tampilkan detail error (jangan dipakai saat hosting)
define('APP_TIMEZONE', 'Asia/Jakarta');

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'db_peminjaman_alat_sekolah');
define('DB_USER', 'root');
define('DB_PASS', '');

// Denda = PENALTI (bukan biaya sewa). Nilai yang sama dipakai oleh function SQL di database/database.sql.
define('DENDA_TERLAMBAT_PER_JP', 5000);
define('DENDA_RUSAK_RINGAN', 10000);
define('DENDA_RUSAK_BERAT', 50000);
define('DENDA_HILANG', 100000);

define('PER_PAGE', 10);                   // baris per halaman pada tabel
define('MAX_ALAT_PER_TRANSAKSI', 10);
define('SYNC_INTERVAL', 60);              // detik; fallback pengecekan terlambat/hilang jika Event Scheduler mati
define('LOGIN_MAX_GAGAL', 5);             // percobaan login gagal per IP
define('LOGIN_JENDELA_MENIT', 10);
