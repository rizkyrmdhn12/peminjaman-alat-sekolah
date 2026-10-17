-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2026 at 07:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_peminjaman_alat_sekolah`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_proses_pengembalian` (IN `p_id_peminjaman` BIGINT UNSIGNED, IN `p_id_petugas` INT UNSIGNED, IN `p_waktu` DATETIME, IN `p_catatan` TEXT, OUT `p_id_pengembalian` BIGINT UNSIGNED)   BEGIN
  DECLARE v_status VARCHAR(20) DEFAULT NULL;
  DECLARE v_id BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_jp INT UNSIGNED DEFAULT 0;
  DECLARE v_detail INT DEFAULT 0;
  DECLARE v_input INT DEFAULT 0;
  DECLARE v_total DECIMAL(12,2) DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;

  START TRANSACTION;

  SELECT status_peminjaman INTO v_status
    FROM peminjaman WHERE id_peminjaman = p_id_peminjaman FOR UPDATE;

  IF v_status IS NULL OR v_status NOT IN ('dipinjam','terlambat','hilang') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi tidak dapat diproses pengembaliannya.';
  END IF;

  SELECT COUNT(*) INTO v_detail
    FROM detail_peminjaman WHERE id_peminjaman = p_id_peminjaman;
  SELECT COUNT(*) INTO v_input
    FROM detail_peminjaman dp
    JOIN tmp_kondisi_kembali t ON t.id_detail_peminjaman = dp.id_detail_peminjaman
   WHERE dp.id_peminjaman = p_id_peminjaman;
  IF v_detail = 0 OR v_detail <> v_input THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kondisi seluruh alat pada transaksi harus diisi.';
  END IF;

  IF EXISTS (SELECT 1 FROM tmp_kondisi_kembali
              WHERE kondisi NOT IN ('baik','rusak_ringan','rusak_berat','hilang')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Kondisi alat tidak valid.';
  END IF;

  IF v_status = 'hilang' AND EXISTS (SELECT 1 FROM tmp_kondisi_kembali WHERE kondisi = 'hilang') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaksi berstatus hilang hanya diproses jika alat ditemukan.';
  END IF;

  -- Transaksi yang sudah hilang otomatis sudah dikenai denda kehilangan;
  -- tidak ada denda keterlambatan tambahan.
  IF v_status = 'hilang' THEN
    SET v_jp = 0;
  ELSE
    SET v_jp = fn_hitung_jp_terlambat(p_id_peminjaman, p_waktu);
  END IF;

  INSERT INTO pengembalian (id_peminjaman, id_petugas, tanggal_pengembalian, total_denda, catatan)
  VALUES (p_id_peminjaman, p_id_petugas, p_waktu, 0, p_catatan);
  SET v_id = LAST_INSERT_ID();

  -- Trigger trg_detail_pengembalian_ai mengubah status/kondisi alat
  INSERT INTO detail_pengembalian
    (id_pengembalian, id_detail_peminjaman, kondisi, jumlah_jp_terlambat,
     denda_keterlambatan, denda_kerusakan, denda_kehilangan, catatan)
  SELECT v_id, dp.id_detail_peminjaman, t.kondisi,
         IF(t.kondisi = 'hilang', 0, v_jp),
         IF(t.kondisi = 'hilang', 0, hitung_denda_keterlambatan(v_jp)),
         hitung_denda_kerusakan(t.kondisi),
         hitung_denda_kehilangan(t.kondisi),
         t.catatan
    FROM detail_peminjaman dp
    JOIN tmp_kondisi_kembali t ON t.id_detail_peminjaman = dp.id_detail_peminjaman
   WHERE dp.id_peminjaman = p_id_peminjaman;

  INSERT INTO denda (id_pengembalian, id_detail_peminjaman, jenis_denda, jumlah_denda, keterangan, status_denda, tanggal_denda)
  SELECT x.id_pengembalian, x.id_detail_peminjaman, 'keterlambatan', x.denda_keterlambatan,
         CONCAT(x.jumlah_jp_terlambat, ' JP terlambat'), 'belum_diselesaikan', p_waktu
    FROM detail_pengembalian x
   WHERE x.id_pengembalian = v_id AND x.denda_keterlambatan > 0;

  INSERT INTO denda (id_pengembalian, id_detail_peminjaman, jenis_denda, jumlah_denda, keterangan, status_denda, tanggal_denda)
  SELECT x.id_pengembalian, x.id_detail_peminjaman, 'kerusakan', x.denda_kerusakan,
         CONCAT('Alat dikembalikan dalam kondisi ', REPLACE(x.kondisi, '_', ' ')), 'belum_diselesaikan', p_waktu
    FROM detail_pengembalian x
   WHERE x.id_pengembalian = v_id AND x.denda_kerusakan > 0;

  INSERT INTO denda (id_pengembalian, id_detail_peminjaman, jenis_denda, jumlah_denda, keterangan, status_denda, tanggal_denda)
  SELECT x.id_pengembalian, x.id_detail_peminjaman, 'kehilangan', x.denda_kehilangan,
         'Alat dilaporkan hilang saat pengembalian', 'belum_diselesaikan', p_waktu
    FROM detail_pengembalian x
   WHERE x.id_pengembalian = v_id AND x.denda_kehilangan > 0;

  -- Riwayat denda kehilangan otomatis dipertahankan & dikaitkan ke pengembalian ini
  UPDATE denda d
    JOIN detail_peminjaman dp ON dp.id_detail_peminjaman = d.id_detail_peminjaman
     SET d.id_pengembalian = v_id
   WHERE dp.id_peminjaman = p_id_peminjaman AND d.id_pengembalian IS NULL;

  SELECT COALESCE(SUM(d.jumlah_denda), 0) INTO v_total
    FROM denda d
    JOIN detail_peminjaman dp ON dp.id_detail_peminjaman = d.id_detail_peminjaman
   WHERE dp.id_peminjaman = p_id_peminjaman;

  UPDATE pengembalian SET total_denda = v_total WHERE id_pengembalian = v_id;

  UPDATE peminjaman
     SET status_peminjaman = 'dikembalikan',
         waktu_dikembalikan = p_waktu,
         id_petugas = COALESCE(id_petugas, p_id_petugas)
   WHERE id_peminjaman = p_id_peminjaman;

  COMMIT;
  SET p_id_pengembalian = v_id;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_sinkron_status_peminjaman` (IN `p_sekarang` DATETIME)   BEGIN
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    DROP TEMPORARY TABLE IF EXISTS tmp_hilang;
    RESIGNAL;
  END;

  DROP TEMPORARY TABLE IF EXISTS tmp_hilang;
  CREATE TEMPORARY TABLE tmp_hilang (
    id_peminjaman BIGINT UNSIGNED NOT NULL PRIMARY KEY
  ) ENGINE=InnoDB;

  START TRANSACTION;

  UPDATE peminjaman
     SET status_peminjaman = 'dibatalkan',
         catatan = CONCAT_WS(' | ', NULLIF(catatan, ''), 'Dibatalkan otomatis: pengajuan tidak diproses hingga batas waktu.')
   WHERE status_peminjaman = 'diajukan' AND batas_kembali < p_sekarang;

  UPDATE peminjaman
     SET status_peminjaman = 'terlambat'
   WHERE status_peminjaman = 'dipinjam' AND batas_kembali < p_sekarang;

  INSERT INTO tmp_hilang (id_peminjaman)
  SELECT p.id_peminjaman
    FROM peminjaman p
    JOIN jadwal_kbm jk ON jk.id_jadwal = p.id_jadwal_kembali
   WHERE p.status_peminjaman IN ('dipinjam','terlambat')
     AND fn_akhir_kbm(jk.id_kelompok_jadwal, jk.hari, DATE(p.batas_kembali)) <= p_sekarang;

  INSERT INTO denda (id_pengembalian, id_detail_peminjaman, jenis_denda, jumlah_denda, keterangan, status_denda, tanggal_denda)
  SELECT NULL, dp.id_detail_peminjaman, 'kehilangan', hitung_denda_kehilangan('hilang'),
         'Tidak dikembalikan hingga akhir KBM (otomatis)', 'belum_diselesaikan', p_sekarang
    FROM detail_peminjaman dp
    JOIN tmp_hilang t ON t.id_peminjaman = dp.id_peminjaman;

  UPDATE alat a
    JOIN detail_peminjaman dp ON dp.id_alat = a.id_alat
    JOIN tmp_hilang t ON t.id_peminjaman = dp.id_peminjaman
     SET a.status_alat = 'hilang';

  INSERT INTO log_aktivitas (id_user, aktivitas, modul, id_referensi, ip_address, created_at)
  SELECT NULL, 'LOST: alat tidak dikembalikan hingga akhir KBM, denda kehilangan dicatat (otomatis)',
         'peminjaman', t.id_peminjaman, NULL, p_sekarang
    FROM tmp_hilang t;

  UPDATE peminjaman p
    JOIN tmp_hilang t ON t.id_peminjaman = p.id_peminjaman
     SET p.status_peminjaman = 'hilang';

  COMMIT;
  DROP TEMPORARY TABLE IF EXISTS tmp_hilang;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_akhir_kbm` (`p_id_kelompok` INT UNSIGNED, `p_hari` VARCHAR(10), `p_tanggal` DATE) RETURNS DATETIME READS SQL DATA BEGIN
  DECLARE v_akhir TIME DEFAULT NULL;
  SELECT MAX(jam_selesai) INTO v_akhir
    FROM jadwal_kbm
   WHERE id_kelompok_jadwal = p_id_kelompok
     AND hari = p_hari
     AND status = 'aktif';
  IF v_akhir IS NULL THEN
    RETURN NULL;
  END IF;
  RETURN TIMESTAMP(p_tanggal, v_akhir);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_hitung_jp_terlambat` (`p_id_peminjaman` BIGINT UNSIGNED, `p_waktu` DATETIME) RETURNS INT(10) UNSIGNED READS SQL DATA BEGIN
  DECLARE v_kelompok INT UNSIGNED DEFAULT NULL;
  DECLARE v_hari VARCHAR(10) DEFAULT NULL;
  DECLARE v_batas DATETIME DEFAULT NULL;
  DECLARE v_jumlah INT UNSIGNED DEFAULT 0;

  SELECT jk.id_kelompok_jadwal, jk.hari, p.batas_kembali
    INTO v_kelompok, v_hari, v_batas
    FROM peminjaman p
    JOIN jadwal_kbm jk ON jk.id_jadwal = p.id_jadwal_kembali
   WHERE p.id_peminjaman = p_id_peminjaman;

  IF v_batas IS NULL OR p_waktu <= v_batas THEN
    RETURN 0;
  END IF;

  SELECT COUNT(*) INTO v_jumlah
    FROM jadwal_kbm x
   WHERE x.id_kelompok_jadwal = v_kelompok
     AND x.hari = v_hari
     AND x.status = 'aktif'
     AND TIMESTAMP(DATE(v_batas), x.jam_mulai) >= v_batas
     AND TIMESTAMP(DATE(v_batas), x.jam_selesai) <= p_waktu;

  RETURN v_jumlah;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_denda_kehilangan` (`p_kondisi` VARCHAR(20)) RETURNS DECIMAL(12,2) DETERMINISTIC NO SQL BEGIN
  RETURN CASE p_kondisi WHEN 'hilang' THEN 100000.00 ELSE 0.00 END;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_denda_kerusakan` (`p_kondisi` VARCHAR(20)) RETURNS DECIMAL(12,2) DETERMINISTIC NO SQL BEGIN
  RETURN CASE p_kondisi
           WHEN 'rusak_ringan' THEN 10000.00
           WHEN 'rusak_berat' THEN 50000.00
           ELSE 0.00
         END;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_denda_keterlambatan` (`p_jumlah_jp` INT UNSIGNED) RETURNS DECIMAL(12,2) DETERMINISTIC NO SQL BEGIN
  RETURN p_jumlah_jp * 5000.00;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id_alat` int(10) UNSIGNED NOT NULL,
  `id_kategori` int(10) UNSIGNED NOT NULL,
  `kode_inventaris` varchar(30) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `merek` varchar(50) DEFAULT NULL,
  `tipe_model` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `status_alat` enum('tersedia','dipinjam','rusak','hilang') NOT NULL DEFAULT 'tersedia',
  `lokasi_penyimpanan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id_alat`, `id_kategori`, `kode_inventaris`, `nama_alat`, `merek`, `tipe_model`, `deskripsi`, `kondisi`, `status_alat`, `lokasi_penyimpanan`, `created_at`, `updated_at`) VALUES
(1, 1, 'CAM-001', 'Camera', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-10-01 03:25:38'),
(2, 1, 'CAM-002', 'Camera', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(3, 1, 'TRP-001', 'Tripod', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-10-01 03:25:38'),
(4, 1, 'HDC-001', 'Handycam', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(5, 1, 'SPK-001', 'Speaker', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(6, 1, 'MIC-001', 'Microphone', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(7, 1, 'MIC-002', 'Microphone', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(8, 1, 'HDS-001', 'Headset', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(9, 1, 'REC-001', 'Recorder', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Multimedia', '2026-09-29 00:31:01', '2026-10-01 03:25:38'),
(10, 2, 'PRJ-001', 'Projector', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-10-01 03:25:38'),
(11, 2, 'PRJ-002', 'Projector', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(12, 2, 'PRS-001', 'Projector Screen', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(13, 2, 'PPT-001', 'Presenter Pointer', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(14, 2, 'HDM-001', 'HDMI Cable', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-10-01 03:25:38'),
(15, 2, 'VGA-001', 'VGA Cable', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(16, 2, 'ADC-001', 'Adapter/Converter', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(17, 2, 'PSP-001', 'Presentation Speaker', NULL, NULL, NULL, 'baik', 'tersedia', 'Ruang Presentasi', '2026-09-29 00:31:01', '2026-09-29 00:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `denda`
--

CREATE TABLE `denda` (
  `id_denda` bigint(20) UNSIGNED NOT NULL,
  `id_pengembalian` bigint(20) UNSIGNED DEFAULT NULL,
  `id_detail_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `jenis_denda` enum('keterlambatan','kerusakan','kehilangan') NOT NULL,
  `jumlah_denda` decimal(12,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `status_denda` enum('belum_diselesaikan','diselesaikan') NOT NULL DEFAULT 'belum_diselesaikan',
  `tanggal_denda` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detail_peminjaman`
--

CREATE TABLE `detail_peminjaman` (
  `id_detail_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `id_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `id_alat` int(10) UNSIGNED NOT NULL,
  `kondisi_saat_dipinjam` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_peminjaman`
--

INSERT INTO `detail_peminjaman` (`id_detail_peminjaman`, `id_peminjaman`, `id_alat`, `kondisi_saat_dipinjam`, `catatan`, `created_at`) VALUES
(1, 1, 1, 'baik', NULL, '2026-10-01 03:21:39'),
(2, 1, 3, 'baik', NULL, '2026-10-01 03:21:39'),
(3, 1, 9, 'baik', NULL, '2026-10-01 03:21:39'),
(4, 1, 10, 'baik', NULL, '2026-10-01 03:21:39'),
(5, 1, 14, 'baik', NULL, '2026-10-01 03:21:39');

--
-- Triggers `detail_peminjaman`
--
DELIMITER $$
CREATE TRIGGER `trg_detail_peminjaman_ai` AFTER INSERT ON `detail_peminjaman` FOR EACH ROW BEGIN
  DECLARE v_status VARCHAR(20) DEFAULT NULL;
  SELECT status_peminjaman INTO v_status
    FROM peminjaman WHERE id_peminjaman = NEW.id_peminjaman;
  IF v_status IN ('dipinjam','terlambat') THEN
    UPDATE alat SET status_alat = 'dipinjam'
     WHERE id_alat = NEW.id_alat AND status_alat = 'tersedia';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `detail_pengembalian`
--

CREATE TABLE `detail_pengembalian` (
  `id_detail_pengembalian` bigint(20) UNSIGNED NOT NULL,
  `id_pengembalian` bigint(20) UNSIGNED NOT NULL,
  `id_detail_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `kondisi` enum('baik','rusak_ringan','rusak_berat','hilang') NOT NULL,
  `jumlah_jp_terlambat` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `denda_keterlambatan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `denda_kerusakan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `denda_kehilangan` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `detail_pengembalian`
--

INSERT INTO `detail_pengembalian` (`id_detail_pengembalian`, `id_pengembalian`, `id_detail_peminjaman`, `kondisi`, `jumlah_jp_terlambat`, `denda_keterlambatan`, `denda_kerusakan`, `denda_kehilangan`, `catatan`, `created_at`) VALUES
(1, 1, 1, 'baik', 0, 0.00, 0.00, 0.00, NULL, '2026-10-01 03:25:38'),
(2, 1, 2, 'baik', 0, 0.00, 0.00, 0.00, NULL, '2026-10-01 03:25:38'),
(3, 1, 3, 'baik', 0, 0.00, 0.00, 0.00, NULL, '2026-10-01 03:25:38'),
(4, 1, 4, 'baik', 0, 0.00, 0.00, 0.00, NULL, '2026-10-01 03:25:38'),
(5, 1, 5, 'baik', 0, 0.00, 0.00, 0.00, NULL, '2026-10-01 03:25:38');

--
-- Triggers `detail_pengembalian`
--
DELIMITER $$
CREATE TRIGGER `trg_detail_pengembalian_ai` AFTER INSERT ON `detail_pengembalian` FOR EACH ROW BEGIN
  DECLARE v_id_alat INT UNSIGNED DEFAULT NULL;
  SELECT id_alat INTO v_id_alat
    FROM detail_peminjaman WHERE id_detail_peminjaman = NEW.id_detail_peminjaman;
  IF v_id_alat IS NOT NULL THEN
    UPDATE alat
       SET status_alat = CASE NEW.kondisi
                           WHEN 'baik' THEN 'tersedia'
                           WHEN 'hilang' THEN 'hilang'
                           ELSE 'rusak'
                         END,
           kondisi = CASE NEW.kondisi
                       WHEN 'hilang' THEN alat.kondisi
                       ELSE NEW.kondisi
                     END
     WHERE id_alat = v_id_alat;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_kbm`
--

CREATE TABLE `jadwal_kbm` (
  `id_jadwal` int(10) UNSIGNED NOT NULL,
  `id_kelompok_jadwal` int(10) UNSIGNED NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat') NOT NULL,
  `nomor_jp` tinyint(3) UNSIGNED NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `jadwal_kbm`
--

INSERT INTO `jadwal_kbm` (`id_jadwal`, `id_kelompok_jadwal`, `hari`, `nomor_jp`, `jam_mulai`, `jam_selesai`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Senin', 1, '08:05:00', '08:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(2, 1, 'Senin', 2, '08:40:00', '09:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(3, 1, 'Senin', 3, '09:15:00', '09:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(4, 1, 'Senin', 4, '10:05:00', '10:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(5, 1, 'Senin', 5, '10:40:00', '11:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(6, 1, 'Senin', 6, '11:15:00', '11:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(7, 1, 'Senin', 7, '12:20:00', '12:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(8, 1, 'Senin', 8, '12:55:00', '13:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(9, 1, 'Senin', 9, '13:30:00', '14:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(10, 1, 'Senin', 10, '14:05:00', '14:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(11, 1, 'Selasa', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(12, 1, 'Selasa', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(13, 1, 'Selasa', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(14, 1, 'Selasa', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(15, 1, 'Selasa', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(16, 1, 'Selasa', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(17, 1, 'Selasa', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(18, 1, 'Selasa', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(19, 1, 'Selasa', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(20, 1, 'Selasa', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(21, 1, 'Selasa', 11, '13:55:00', '14:35:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(22, 1, 'Rabu', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(23, 1, 'Rabu', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(24, 1, 'Rabu', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(25, 1, 'Rabu', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(26, 1, 'Rabu', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(27, 1, 'Rabu', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(28, 1, 'Rabu', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(29, 1, 'Rabu', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(30, 1, 'Rabu', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(31, 1, 'Rabu', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(32, 1, 'Rabu', 11, '13:55:00', '14:35:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(33, 1, 'Kamis', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(34, 1, 'Kamis', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(35, 1, 'Kamis', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(36, 1, 'Kamis', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(37, 1, 'Kamis', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(38, 1, 'Kamis', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(39, 1, 'Kamis', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(40, 1, 'Kamis', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(41, 1, 'Kamis', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(42, 1, 'Kamis', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(43, 1, 'Kamis', 11, '13:55:00', '14:35:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(44, 1, 'Jumat', 1, '06:30:00', '07:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(45, 1, 'Jumat', 2, '07:05:00', '07:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(46, 1, 'Jumat', 3, '07:40:00', '08:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(47, 1, 'Jumat', 4, '08:15:00', '08:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(48, 1, 'Jumat', 5, '09:05:00', '09:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(49, 1, 'Jumat', 6, '09:40:00', '10:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(50, 1, 'Jumat', 7, '10:15:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(51, 2, 'Senin', 1, '08:05:00', '08:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(52, 2, 'Senin', 2, '08:40:00', '09:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(53, 2, 'Senin', 3, '09:15:00', '09:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(54, 2, 'Senin', 4, '10:05:00', '10:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(55, 2, 'Senin', 5, '10:40:00', '11:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(56, 2, 'Senin', 6, '11:15:00', '11:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(57, 2, 'Senin', 7, '12:20:00', '12:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(58, 2, 'Senin', 8, '12:55:00', '13:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(59, 2, 'Senin', 9, '13:30:00', '14:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(60, 2, 'Senin', 10, '14:05:00', '14:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(61, 2, 'Selasa', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(62, 2, 'Selasa', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(63, 2, 'Selasa', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(64, 2, 'Selasa', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(65, 2, 'Selasa', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(66, 2, 'Selasa', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(67, 2, 'Selasa', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(68, 2, 'Selasa', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(69, 2, 'Selasa', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(70, 2, 'Selasa', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(71, 2, 'Rabu', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(72, 2, 'Rabu', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(73, 2, 'Rabu', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(74, 2, 'Rabu', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(75, 2, 'Rabu', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(76, 2, 'Rabu', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(77, 2, 'Rabu', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(78, 2, 'Rabu', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(79, 2, 'Rabu', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(80, 2, 'Rabu', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(81, 2, 'Rabu', 11, '13:55:00', '14:35:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(82, 2, 'Kamis', 1, '06:30:00', '07:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(83, 2, 'Kamis', 2, '07:10:00', '07:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(84, 2, 'Kamis', 3, '07:50:00', '08:30:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(85, 2, 'Kamis', 4, '08:30:00', '09:10:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(86, 2, 'Kamis', 5, '09:25:00', '10:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(87, 2, 'Kamis', 6, '10:05:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(88, 2, 'Kamis', 7, '10:45:00', '11:25:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(89, 2, 'Kamis', 8, '11:25:00', '12:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(90, 2, 'Kamis', 9, '12:35:00', '13:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(91, 2, 'Kamis', 10, '13:15:00', '13:55:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(92, 2, 'Jumat', 1, '06:30:00', '07:05:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(93, 2, 'Jumat', 2, '07:05:00', '07:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(94, 2, 'Jumat', 3, '07:40:00', '08:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(95, 2, 'Jumat', 4, '08:15:00', '08:50:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(96, 2, 'Jumat', 5, '09:05:00', '09:40:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(97, 2, 'Jumat', 6, '09:40:00', '10:15:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(98, 2, 'Jumat', 7, '10:15:00', '10:45:00', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_alat`
--

CREATE TABLE `kategori_alat` (
  `id_kategori` int(10) UNSIGNED NOT NULL,
  `nama_kategori` varchar(50) NOT NULL,
  `deskripsi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_alat`
--

INSERT INTO `kategori_alat` (`id_kategori`, `nama_kategori`, `deskripsi`, `created_at`, `updated_at`) VALUES
(1, 'Multimedia', 'Perangkat perekaman, dokumentasi, dan audio', '2026-09-29 00:31:01', '2026-09-29 00:31:01'),
(2, 'Presentasi', 'Perangkat pendukung presentasi dan proyeksi', '2026-09-29 00:31:01', '2026-09-29 00:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id_kelas` int(10) UNSIGNED NOT NULL,
  `id_kelompok_jadwal` int(10) UNSIGNED NOT NULL,
  `tingkat` tinyint(3) UNSIGNED NOT NULL,
  `jurusan` varchar(50) NOT NULL,
  `nama_kelas` varchar(30) NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id_kelas`, `id_kelompok_jadwal`, `tingkat`, `jurusan`, `nama_kelas`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 10, 'RPL', 'X RPL 1', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(2, 1, 10, 'RPL', 'X RPL 2', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(3, 2, 11, 'RPL', 'XI RPL 1', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(4, 2, 11, 'RPL', 'XI RPL 2', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(5, 2, 12, 'RPL', 'XII RPL 1', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(6, 2, 12, 'RPL', 'XII RPL 2', 'aktif', '2026-09-29 00:30:54', '2026-09-29 00:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `kelompok_jadwal`
--

CREATE TABLE `kelompok_jadwal` (
  `id_kelompok_jadwal` int(10) UNSIGNED NOT NULL,
  `nama_kelompok` varchar(20) NOT NULL,
  `keterangan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kelompok_jadwal`
--

INSERT INTO `kelompok_jadwal` (`id_kelompok_jadwal`, `nama_kelompok`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'X', 'Jadwal kelas tingkat X', '2026-09-29 00:30:54', '2026-09-29 00:30:54'),
(2, 'XI_XII', 'Jadwal kelas tingkat XI dan XII', '2026-09-29 00:30:54', '2026-09-29 00:30:54');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id_log` bigint(20) UNSIGNED NOT NULL,
  `id_user` int(10) UNSIGNED DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `modul` varchar(50) NOT NULL,
  `id_referensi` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id_log`, `id_user`, `aktivitas`, `modul`, `id_referensi`, `ip_address`, `created_at`) VALUES
(1, NULL, 'LOGIN_GAGAL: username: admin', 'auth', NULL, '::1', '2026-09-29 00:33:28'),
(2, NULL, 'LOGIN_GAGAL: username: admin', 'auth', NULL, '::1', '2026-09-29 00:33:39'),
(3, 1, 'LOGIN: berhasil login', 'auth', 1, '::1', '2026-09-29 03:46:45'),
(4, 1, 'LOGOUT: logout', 'auth', 1, '::1', '2026-09-29 03:49:08'),
(5, 2, 'LOGIN: berhasil login', 'auth', 2, '::1', '2026-09-29 03:49:31'),
(6, 2, 'LOGOUT: logout', 'auth', 2, '::1', '2026-09-29 03:49:53'),
(7, 3, 'LOGIN: berhasil login', 'auth', 3, '::1', '2026-09-29 03:50:10'),
(8, 3, 'LOGOUT: logout', 'auth', 3, '::1', '2026-09-29 15:56:11'),
(9, 1, 'LOGIN: berhasil login', 'auth', 1, '::1', '2026-09-29 15:56:48'),
(10, 1, 'CREATE: menambah pengguna ikyy (peminjam)', 'users', 5, '::1', '2026-09-29 16:18:46'),
(11, 1, 'UPDATE: mengubah pengguna ujang', 'users', 5, '::1', '2026-09-29 16:19:17'),
(12, 1, 'DELETE: menghapus pengguna ujang', 'users', 5, '::1', '2026-09-29 16:19:25'),
(13, 1, 'CREATE: menambah kategori sepak bola', 'kategori', 3, '::1', '2026-09-29 16:19:58'),
(14, 1, 'UPDATE: mengubah kategori sepak bola', 'kategori', 3, '::1', '2026-09-29 16:20:20'),
(15, 1, 'DELETE: menghapus kategori sepak bola', 'kategori', 3, '::1', '2026-09-29 16:20:25'),
(16, 1, 'CREATE: menambah alat CAM-003', 'alat', 18, '::1', '2026-09-29 16:23:23'),
(17, 1, 'UPDATE: mengubah alat CAM-003', 'alat', 18, '::1', '2026-09-29 16:24:04'),
(18, 1, 'DELETE: menghapus alat CAM-003', 'alat', 18, '::1', '2026-09-29 16:24:25'),
(19, 1, 'LOGOUT: logout', 'auth', 1, '::1', '2026-09-29 16:24:47'),
(20, NULL, 'LOGIN_GAGAL: username: petugas', 'auth', NULL, '::1', '2026-09-29 16:24:58'),
(21, 2, 'LOGIN: berhasil login', 'auth', 2, '::1', '2026-09-29 16:25:03'),
(22, 2, 'LOGOUT: logout', 'auth', 2, '::1', '2026-09-29 16:25:08'),
(23, 3, 'LOGIN: berhasil login', 'auth', 3, '::1', '2026-09-29 16:25:24'),
(24, 3, 'LOGIN: berhasil login', 'auth', 3, '::1', '2026-10-01 03:20:04'),
(25, 3, 'BORROW: pengajuan PJM-20261001-0001 (5 alat)', 'peminjaman', 1, '::1', '2026-10-01 03:21:39'),
(26, 3, 'LOGOUT: logout', 'auth', 3, '::1', '2026-10-01 03:23:22'),
(27, 2, 'LOGIN: berhasil login', 'auth', 2, '::1', '2026-10-01 03:23:41'),
(28, 2, 'APPROVE: menyetujui peminjaman', 'peminjaman', 1, '::1', '2026-10-01 03:24:35'),
(29, 2, 'RETURN: pengembalian PJM-20261001-0001', 'pengembalian', 1, '::1', '2026-10-01 03:25:40'),
(30, 2, 'LOGOUT: logout', 'auth', 2, '::1', '2026-10-13 14:55:36'),
(31, 3, 'LOGIN: berhasil login', 'auth', 3, '::1', '2026-10-13 14:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `kode_peminjaman` varchar(30) NOT NULL,
  `id_peminjam` int(10) UNSIGNED NOT NULL,
  `id_petugas` int(10) UNSIGNED DEFAULT NULL,
  `jenis_peminjaman` enum('online','offline') NOT NULL,
  `tanggal_peminjaman` date NOT NULL,
  `id_jadwal_mulai` int(10) UNSIGNED NOT NULL,
  `id_jadwal_kembali` int(10) UNSIGNED NOT NULL,
  `waktu_mulai` datetime NOT NULL,
  `batas_kembali` datetime NOT NULL,
  `waktu_dikembalikan` datetime DEFAULT NULL,
  `status_peminjaman` enum('diajukan','disetujui','ditolak','dipinjam','terlambat','hilang','dikembalikan','dibatalkan') NOT NULL DEFAULT 'diajukan',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id_peminjaman`, `kode_peminjaman`, `id_peminjam`, `id_petugas`, `jenis_peminjaman`, `tanggal_peminjaman`, `id_jadwal_mulai`, `id_jadwal_kembali`, `waktu_mulai`, `batas_kembali`, `waktu_dikembalikan`, `status_peminjaman`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 'PJM-20261001-0001', 3, 2, 'online', '2026-10-01', 87, 88, '2026-10-01 10:05:00', '2026-10-01 11:25:00', '2026-10-01 10:25:37', 'dikembalikan', 'presentasi project akhir untuk keperluan ukk', '2026-10-01 03:21:39', '2026-10-01 03:25:40');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id_pengembalian` bigint(20) UNSIGNED NOT NULL,
  `id_peminjaman` bigint(20) UNSIGNED NOT NULL,
  `id_petugas` int(10) UNSIGNED DEFAULT NULL,
  `tanggal_pengembalian` datetime NOT NULL,
  `total_denda` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id_pengembalian`, `id_peminjaman`, `id_petugas`, `tanggal_pengembalian`, `total_denda`, `catatan`, `created_at`, `updated_at`) VALUES
(1, 1, 2, '2026-10-01 10:25:37', 0.00, 'makasih banyak bu', '2026-10-01 03:25:38', '2026-10-01 03:25:38');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(10) UNSIGNED NOT NULL,
  `id_kelas` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jenis_pengguna` enum('siswa','guru','staff') DEFAULT NULL,
  `nomor_identitas` varchar(30) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','petugas','peminjam') NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `id_kelas`, `username`, `password`, `nama_lengkap`, `jenis_pengguna`, `nomor_identitas`, `no_hp`, `email`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'admin', '$2y$10$hCZK2rXoC5BfxKFWgBc7iuzX7.X7fJGetrUhJqcayDVkVRl8zyPxO', 'Administrator', NULL, NULL, NULL, NULL, 'admin', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(2, NULL, 'petugas', '$2y$10$oUYUaKcTre0./qEbd.700uggMXILXgEUVODsEb3S0nIyQ94qTId8W', 'Petugas Sarana', NULL, NULL, NULL, NULL, 'petugas', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(3, 3, 'peminjam', '$2y$10$AJsnTOJGrtLJYAhPK4DSpup55LLacwgvw8BZrYc82cvPe7cWjXRRq', 'Siswa Demo', 'siswa', '2026001', NULL, NULL, 'peminjam', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58'),
(4, NULL, 'guru', '$2y$10$KSKSoJVtqfeZa.X1fAwAauHPp2hPlj8bVJhU4UOanR4.pVvnDpq2u', 'Guru Demo', 'guru', '198501012010011001', NULL, NULL, 'peminjam', 'aktif', '2026-09-29 00:30:58', '2026-09-29 00:30:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id_alat`),
  ADD UNIQUE KEY `uq_alat_kode` (`kode_inventaris`),
  ADD KEY `idx_alat_kategori` (`id_kategori`),
  ADD KEY `idx_alat_status` (`status_alat`);

--
-- Indexes for table `denda`
--
ALTER TABLE `denda`
  ADD PRIMARY KEY (`id_denda`),
  ADD KEY `idx_denda_pengembalian` (`id_pengembalian`),
  ADD KEY `idx_denda_detail` (`id_detail_peminjaman`),
  ADD KEY `idx_denda_status` (`status_denda`),
  ADD KEY `idx_denda_tanggal` (`tanggal_denda`);

--
-- Indexes for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD PRIMARY KEY (`id_detail_peminjaman`),
  ADD UNIQUE KEY `uq_detail_peminjaman` (`id_peminjaman`,`id_alat`),
  ADD KEY `idx_detail_peminjaman_alat` (`id_alat`);

--
-- Indexes for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD PRIMARY KEY (`id_detail_pengembalian`),
  ADD UNIQUE KEY `uq_detail_pengembalian` (`id_pengembalian`,`id_detail_peminjaman`),
  ADD KEY `idx_detail_pengembalian_dp` (`id_detail_peminjaman`);

--
-- Indexes for table `jadwal_kbm`
--
ALTER TABLE `jadwal_kbm`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD UNIQUE KEY `uq_jadwal` (`id_kelompok_jadwal`,`hari`,`nomor_jp`);

--
-- Indexes for table `kategori_alat`
--
ALTER TABLE `kategori_alat`
  ADD PRIMARY KEY (`id_kategori`),
  ADD UNIQUE KEY `uq_kategori_nama` (`nama_kategori`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id_kelas`),
  ADD UNIQUE KEY `uq_kelas_nama` (`nama_kelas`),
  ADD KEY `idx_kelas_kelompok` (`id_kelompok_jadwal`);

--
-- Indexes for table `kelompok_jadwal`
--
ALTER TABLE `kelompok_jadwal`
  ADD PRIMARY KEY (`id_kelompok_jadwal`),
  ADD UNIQUE KEY `uq_kelompok_nama` (`nama_kelompok`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `idx_log_user` (`id_user`),
  ADD KEY `idx_log_modul` (`modul`),
  ADD KEY `idx_log_created` (`created_at`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id_peminjaman`),
  ADD UNIQUE KEY `uq_peminjaman_kode` (`kode_peminjaman`),
  ADD KEY `idx_peminjaman_peminjam` (`id_peminjam`),
  ADD KEY `idx_peminjaman_petugas` (`id_petugas`),
  ADD KEY `idx_peminjaman_status` (`status_peminjaman`),
  ADD KEY `idx_peminjaman_tanggal` (`tanggal_peminjaman`),
  ADD KEY `idx_peminjaman_jadwal_mulai` (`id_jadwal_mulai`),
  ADD KEY `idx_peminjaman_jadwal_kembali` (`id_jadwal_kembali`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id_pengembalian`),
  ADD UNIQUE KEY `uq_pengembalian_peminjaman` (`id_peminjaman`),
  ADD KEY `idx_pengembalian_petugas` (`id_petugas`),
  ADD KEY `idx_pengembalian_tanggal` (`tanggal_pengembalian`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_identitas` (`nomor_identitas`),
  ADD KEY `idx_users_role` (`role`),
  ADD KEY `idx_users_kelas` (`id_kelas`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id_alat` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `denda`
--
ALTER TABLE `denda`
  MODIFY `id_denda` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  MODIFY `id_detail_peminjaman` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  MODIFY `id_detail_pengembalian` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwal_kbm`
--
ALTER TABLE `jadwal_kbm`
  MODIFY `id_jadwal` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_alat`
--
ALTER TABLE `kategori_alat`
  MODIFY `id_kategori` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id_kelas` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kelompok_jadwal`
--
ALTER TABLE `kelompok_jadwal`
  MODIFY `id_kelompok_jadwal` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id_log` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id_peminjaman` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id_pengembalian` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `fk_alat_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_alat` (`id_kategori`) ON UPDATE CASCADE;

--
-- Constraints for table `denda`
--
ALTER TABLE `denda`
  ADD CONSTRAINT `fk_denda_detail` FOREIGN KEY (`id_detail_peminjaman`) REFERENCES `detail_peminjaman` (`id_detail_peminjaman`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_denda_pengembalian` FOREIGN KEY (`id_pengembalian`) REFERENCES `pengembalian` (`id_pengembalian`) ON UPDATE CASCADE;

--
-- Constraints for table `detail_peminjaman`
--
ALTER TABLE `detail_peminjaman`
  ADD CONSTRAINT `fk_detail_peminjaman_alat` FOREIGN KEY (`id_alat`) REFERENCES `alat` (`id_alat`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_peminjaman_header` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `detail_pengembalian`
--
ALTER TABLE `detail_pengembalian`
  ADD CONSTRAINT `fk_detail_pengembalian_dp` FOREIGN KEY (`id_detail_peminjaman`) REFERENCES `detail_peminjaman` (`id_detail_peminjaman`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_pengembalian_header` FOREIGN KEY (`id_pengembalian`) REFERENCES `pengembalian` (`id_pengembalian`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `jadwal_kbm`
--
ALTER TABLE `jadwal_kbm`
  ADD CONSTRAINT `fk_jadwal_kelompok` FOREIGN KEY (`id_kelompok_jadwal`) REFERENCES `kelompok_jadwal` (`id_kelompok_jadwal`) ON UPDATE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `fk_kelas_kelompok` FOREIGN KEY (`id_kelompok_jadwal`) REFERENCES `kelompok_jadwal` (`id_kelompok_jadwal`) ON UPDATE CASCADE;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `fk_peminjaman_jadwal_kembali` FOREIGN KEY (`id_jadwal_kembali`) REFERENCES `jadwal_kbm` (`id_jadwal`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_jadwal_mulai` FOREIGN KEY (`id_jadwal_mulai`) REFERENCES `jadwal_kbm` (`id_jadwal`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_peminjam` FOREIGN KEY (`id_peminjam`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_peminjaman_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `fk_pengembalian_peminjaman` FOREIGN KEY (`id_peminjaman`) REFERENCES `peminjaman` (`id_peminjaman`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pengembalian_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_kelas` FOREIGN KEY (`id_kelas`) REFERENCES `kelas` (`id_kelas`) ON UPDATE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `ev_sinkron_status_peminjaman` ON SCHEDULE EVERY 1 MINUTE STARTS '2026-09-29 07:26:18' ON COMPLETION NOT PRESERVE ENABLE DO CALL sp_sinkron_status_peminjaman(UTC_TIMESTAMP() + INTERVAL 7 HOUR)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
