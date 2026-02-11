-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 07, 2026 at 10:34 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_smk`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_absensi`
--

CREATE TABLE `tb_absensi` (
  `absensi_id` bigint NOT NULL,
  `jenis` enum('guru','siswa') COLLATE utf8mb4_general_ci NOT NULL,
  `guru_id` int DEFAULT NULL,
  `siswa_id` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jadwal_jam_masuk` time DEFAULT NULL,
  `jadwal_batas_telat` time DEFAULT NULL,
  `jadwal_jam_pulang` time DEFAULT NULL,
  `status_masuk` enum('Hadir','Telat','Izin','Sakit','Tidak Hadir') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_pulang` enum('Pulang','Belum Pulang') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `jam_pulang` datetime DEFAULT NULL,
  `lat_masuk` decimal(10,7) DEFAULT NULL,
  `lng_masuk` decimal(10,7) DEFAULT NULL,
  `akurasi_masuk` float DEFAULT NULL,
  `jarak_masuk_meter` float DEFAULT NULL,
  `foto_masuk` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lat_pulang` decimal(10,7) DEFAULT NULL,
  `lng_pulang` decimal(10,7) DEFAULT NULL,
  `akurasi_pulang` float DEFAULT NULL,
  `jarak_pulang_meter` float DEFAULT NULL,
  `foto_pulang` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `device_info` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_absensi`
--

INSERT INTO `tb_absensi` (`absensi_id`, `jenis`, `guru_id`, `siswa_id`, `tanggal`, `jadwal_jam_masuk`, `jadwal_batas_telat`, `jadwal_jam_pulang`, `status_masuk`, `status_pulang`, `jam_masuk`, `jam_pulang`, `lat_masuk`, `lng_masuk`, `akurasi_masuk`, `jarak_masuk_meter`, `foto_masuk`, `lat_pulang`, `lng_pulang`, `akurasi_pulang`, `jarak_pulang_meter`, `foto_pulang`, `device_info`, `catatan`, `created_at`) VALUES
(4, 'siswa', NULL, 1, '2026-01-06', '07:00:00', '07:15:00', '15:00:00', 'Telat', 'Pulang', '2026-01-06 21:35:19', '2026-01-06 21:35:47', '-2.9868332', '114.7327062', 22.938, 39777.5, 'uploads/absensi/absen_097edc82515723eb.jpg', '-2.9868332', '114.7327062', 22.938, 39777.5, 'uploads/absensi/pulang_02950faef540b9c5.jpg', 'ua=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36;sig=ok', NULL, '2026-01-06 21:35:19'),
(5, 'siswa', NULL, 1, '2026-01-07', NULL, NULL, NULL, 'Izin', NULL, '2026-01-07 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Izin disetujui', '2026-01-06 22:03:29'),
(6, 'siswa', NULL, 1, '2026-01-08', NULL, NULL, NULL, 'Izin', NULL, '2026-01-08 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Izin disetujui', '2026-01-06 22:03:29'),
(7, 'guru', 1, NULL, '2026-01-14', NULL, NULL, NULL, 'Izin', NULL, '2026-01-14 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Izin disetujui', '2026-01-06 22:11:50'),
(8, 'guru', 1, NULL, '2026-01-07', '06:45:00', '07:05:00', '15:30:00', 'Telat', NULL, '2026-01-07 15:49:29', NULL, '-3.4439168', '114.8026880', 79391, 22708.7, 'uploads/absensi/absen_2a70c20581220d29.jpg', NULL, NULL, NULL, NULL, NULL, 'ua=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36;sig=42ca9e68c256088631eb9e83c55fa53e984853d0c1321ae8ade3c03d81d056f5', NULL, '2026-01-07 15:49:29');

-- --------------------------------------------------------

--
-- Table structure for table `tb_guru`
--

CREATE TABLE `tb_guru` (
  `guru_id` int NOT NULL,
  `user_id` int NOT NULL,
  `nip` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_guru`
--

INSERT INTO `tb_guru` (`guru_id`, `user_id`, `nip`, `jabatan`, `foto`, `jenis_kelamin`, `alamat`, `is_active`, `created_at`) VALUES
(1, 2, '123', 'Kepala Sekolah', 'uploads/guru/guru_3fa85829a7ead1d4.png', 'P', 'Semanda', 1, '2026-01-06 19:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `tb_hari_libur`
--

CREATE TABLE `tb_hari_libur` (
  `libur_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `jenis` enum('Nasional','Cuti Bersama','Sekolah','Khusus') COLLATE utf8mb4_general_ci DEFAULT 'Nasional',
  `keterangan` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_hari_libur`
--

INSERT INTO `tb_hari_libur` (`libur_id`, `tanggal`, `nama_libur`, `jenis`, `keterangan`, `is_active`, `created_at`, `updated_at`) VALUES
(26, '2026-01-01', 'Tahun Baru 2026 Masehi', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(27, '2026-01-16', 'Isra Mikraj Nabi Muhammad SAW', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(28, '2026-02-17', 'Tahun Baru Imlek 2577 Kongzili', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(29, '2026-03-19', 'Hari Suci Nyepi (Tahun Baru Saka 1948)', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(30, '2026-03-21', 'Hari Raya Idul Fitri 1447 H (Hari Pertama)', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(31, '2026-03-22', 'Hari Raya Idul Fitri 1447 H (Hari Kedua)', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(32, '2026-04-03', 'Wafat Yesus Kristus', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(33, '2026-04-05', 'Kebangkitan Yesus Kristus (Paskah)', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(34, '2026-05-01', 'Hari Buruh Internasional', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(35, '2026-05-14', 'Kenaikan Yesus Kristus', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(36, '2026-05-27', 'Hari Raya Idul Adha 1447 H', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(37, '2026-05-31', 'Hari Raya Waisak 2570 BE', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(38, '2026-06-01', 'Hari Lahir Pancasila', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(39, '2026-06-16', '1 Muharam Tahun Baru Islam 1448 H', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(40, '2026-08-17', 'Proklamasi Kemerdekaan', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(41, '2026-08-25', 'Maulid Nabi Muhammad SAW', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(42, '2026-12-25', 'Kelahiran Yesus Kristus', 'Nasional', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(43, '2026-02-16', 'Cuti Bersama Tahun Baru Imlek 2577 Kongzili', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(44, '2026-03-18', 'Cuti Bersama Hari Suci Nyepi (Tahun Baru Saka 1948)', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(45, '2026-03-20', 'Cuti Bersama Idul Fitri 1447 H', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(46, '2026-03-23', 'Cuti Bersama Idul Fitri 1447 H', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(47, '2026-03-24', 'Cuti Bersama Idul Fitri 1447 H', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(48, '2026-05-15', 'Cuti Bersama Kenaikan Yesus Kristus', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(49, '2026-05-28', 'Cuti Bersama Idul Adha 1447 H', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49'),
(50, '2026-12-24', 'Cuti Bersama Kelahiran Yesus Kristus', 'Cuti Bersama', NULL, 1, '2026-01-06 22:40:49', '2026-01-06 22:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `tb_izin`
--

CREATE TABLE `tb_izin` (
  `izin_id` int NOT NULL,
  `jenis_pengguna` enum('guru','siswa') COLLATE utf8mb4_general_ci NOT NULL,
  `guru_id` int DEFAULT NULL,
  `siswa_id` int DEFAULT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jenis_izin` enum('Izin','Sakit','Dinas','Dispensasi') COLLATE utf8mb4_general_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_general_ci,
  `bukti` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Diajukan','Disetujui','Ditolak') COLLATE utf8mb4_general_ci DEFAULT 'Diajukan',
  `diajukan_pada` datetime DEFAULT CURRENT_TIMESTAMP,
  `disetujui_pada` datetime DEFAULT NULL,
  `disetujui_oleh` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_izin`
--

INSERT INTO `tb_izin` (`izin_id`, `jenis_pengguna`, `guru_id`, `siswa_id`, `tanggal_mulai`, `tanggal_selesai`, `jenis_izin`, `keterangan`, `bukti`, `status`, `diajukan_pada`, `disetujui_pada`, `disetujui_oleh`) VALUES
(1, 'siswa', NULL, 1, '2026-01-07', '2026-01-08', 'Izin', 'Sakit', 'uploads/izin/izin_9232c267798c6654.png', 'Disetujui', '2026-01-06 22:03:20', NULL, NULL),
(2, 'guru', 1, NULL, '2026-01-14', '2026-01-14', 'Dinas', 'Perjalanan Dinas Pelatihan', NULL, 'Disetujui', '2026-01-06 22:11:32', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_jadwal_absensi`
--

CREATE TABLE `tb_jadwal_absensi` (
  `jadwal_id` int NOT NULL,
  `berlaku_untuk` enum('guru','siswa') COLLATE utf8mb4_general_ci NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') COLLATE utf8mb4_general_ci NOT NULL,
  `jam_masuk` time NOT NULL,
  `batas_telat` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `is_libur` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_jadwal_absensi`
--

INSERT INTO `tb_jadwal_absensi` (`jadwal_id`, `berlaku_untuk`, `hari`, `jam_masuk`, `batas_telat`, `jam_pulang`, `is_libur`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'siswa', 'Senin', '07:00:00', '07:15:00', '15:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(2, 'siswa', 'Selasa', '07:00:00', '07:15:00', '15:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(3, 'siswa', 'Rabu', '07:00:00', '07:15:00', '15:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(4, 'siswa', 'Kamis', '07:00:00', '07:15:00', '15:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(5, 'siswa', 'Jumat', '07:00:00', '07:15:00', '11:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(6, 'siswa', 'Sabtu', '07:30:00', '07:45:00', '12:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(7, 'siswa', 'Minggu', '00:00:00', '00:00:00', '00:00:00', 1, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(8, 'guru', 'Senin', '06:45:00', '07:05:00', '15:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(9, 'guru', 'Selasa', '06:45:00', '07:05:00', '15:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(10, 'guru', 'Rabu', '06:45:00', '07:05:00', '15:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(11, 'guru', 'Kamis', '06:45:00', '07:05:00', '15:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(12, 'guru', 'Jumat', '06:45:00', '07:05:00', '12:00:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(13, 'guru', 'Sabtu', '07:00:00', '07:15:00', '12:30:00', 0, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17'),
(14, 'guru', 'Minggu', '00:00:00', '00:00:00', '00:00:00', 1, 1, '2026-01-06 17:39:17', '2026-01-06 17:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kelas`
--

CREATE TABLE `tb_kelas` (
  `kelas_id` int NOT NULL,
  `nama_kelas` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tingkat` enum('X','XI','XII') COLLATE utf8mb4_general_ci NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kelas`
--

INSERT INTO `tb_kelas` (`kelas_id`, `nama_kelas`, `tingkat`, `is_active`, `created_at`) VALUES
(1, 'X Farmasi 1', 'X', 1, '2026-01-06 17:39:17'),
(2, 'X Farmasi 2', 'X', 1, '2026-01-06 17:39:17'),
(3, 'XI Farmasi 1', 'XI', 1, '2026-01-06 17:39:17'),
(4, 'XII Farmasi 1', 'XII', 1, '2026-01-06 17:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `tb_override_hari`
--

CREATE TABLE `tb_override_hari` (
  `override_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Masuk','Libur') COLLATE utf8mb4_general_ci NOT NULL,
  `alasan` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengaturan_absensi`
--

CREATE TABLE `tb_pengaturan_absensi` (
  `pengaturan_id` int NOT NULL,
  `nama_lokasi` varchar(150) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Sekolah',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `radius_meter` int NOT NULL DEFAULT '100',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pengaturan_absensi`
--

INSERT INTO `tb_pengaturan_absensi` (`pengaturan_id`, `nama_lokasi`, `latitude`, `longitude`, `radius_meter`, `updated_at`) VALUES
(1, 'SMK Farmasi Mandiri Banjarmasin', '-3.33033242', '114.63266759', 50000, '2026-01-07 15:48:22');

-- --------------------------------------------------------

--
-- Table structure for table `tb_siswa`
--

CREATE TABLE `tb_siswa` (
  `siswa_id` int NOT NULL,
  `user_id` int NOT NULL,
  `nis` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `foto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_id` int NOT NULL,
  `jenis_kelamin` enum('L','P') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `alamat` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_siswa`
--

INSERT INTO `tb_siswa` (`siswa_id`, `user_id`, `nis`, `foto`, `kelas_id`, `jenis_kelamin`, `alamat`, `is_active`, `created_at`) VALUES
(1, 3, '12', 'uploads/siswa/siswa_afd09540e3c471de.jpg', 1, 'P', 'Malkon Temon', 1, '2026-01-06 19:36:58');

-- --------------------------------------------------------

--
-- Table structure for table `tb_users`
--

CREATE TABLE `tb_users` (
  `user_id` int NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','guru','siswa') COLLATE utf8mb4_general_ci NOT NULL,
  `no_hp` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_users`
--

INSERT INTO `tb_users` (`user_id`, `nama`, `username`, `password_hash`, `role`, `no_hp`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$51/EGJZNzE/xBgyq22IagudjOqtd8iNTmyvOLiV2BOHuu9RhxEmkC', 'admin', NULL, 1, '2026-01-06 18:41:07', '2026-01-06 18:41:07'),
(2, 'Susanti Pusparini, ST', 'santi', '$2y$10$2Tv0YMGCBwZcAP.mwnC6Su2ZvDijAg8Ne/ruNhy6gXGWJ0iNMmwWa', 'guru', '08', 1, '2026-01-06 19:30:10', '2026-01-06 19:30:10'),
(3, 'Raisa', 'raisa', '$2y$10$h4iGwvLAj4y23ufJ8Agn0.bOEOqrNyo/7r3slADd8VPBQ.YQJ9R1K', 'siswa', '08', 1, '2026-01-06 19:35:57', '2026-01-06 19:35:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  ADD PRIMARY KEY (`absensi_id`),
  ADD UNIQUE KEY `uk_absen_guru` (`jenis`,`guru_id`,`tanggal`),
  ADD UNIQUE KEY `uk_absen_siswa` (`jenis`,`siswa_id`,`tanggal`),
  ADD KEY `idx_tanggal` (`tanggal`),
  ADD KEY `idx_guru_tanggal` (`guru_id`,`tanggal`),
  ADD KEY `idx_siswa_tanggal` (`siswa_id`,`tanggal`);

--
-- Indexes for table `tb_guru`
--
ALTER TABLE `tb_guru`
  ADD PRIMARY KEY (`guru_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `tb_hari_libur`
--
ALTER TABLE `tb_hari_libur`
  ADD PRIMARY KEY (`libur_id`),
  ADD UNIQUE KEY `uk_tanggal` (`tanggal`);

--
-- Indexes for table `tb_izin`
--
ALTER TABLE `tb_izin`
  ADD PRIMARY KEY (`izin_id`),
  ADD KEY `fk_izin_guru` (`guru_id`),
  ADD KEY `fk_izin_siswa` (`siswa_id`),
  ADD KEY `fk_izin_approver` (`disetujui_oleh`);

--
-- Indexes for table `tb_jadwal_absensi`
--
ALTER TABLE `tb_jadwal_absensi`
  ADD PRIMARY KEY (`jadwal_id`),
  ADD UNIQUE KEY `uk_jadwal` (`berlaku_untuk`,`hari`);

--
-- Indexes for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  ADD PRIMARY KEY (`kelas_id`);

--
-- Indexes for table `tb_override_hari`
--
ALTER TABLE `tb_override_hari`
  ADD PRIMARY KEY (`override_id`),
  ADD UNIQUE KEY `uk_override_tanggal` (`tanggal`);

--
-- Indexes for table `tb_pengaturan_absensi`
--
ALTER TABLE `tb_pengaturan_absensi`
  ADD PRIMARY KEY (`pengaturan_id`);

--
-- Indexes for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD PRIMARY KEY (`siswa_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `fk_siswa_kelas` (`kelas_id`);

--
-- Indexes for table `tb_users`
--
ALTER TABLE `tb_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  MODIFY `absensi_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tb_guru`
--
ALTER TABLE `tb_guru`
  MODIFY `guru_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_hari_libur`
--
ALTER TABLE `tb_hari_libur`
  MODIFY `libur_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `tb_izin`
--
ALTER TABLE `tb_izin`
  MODIFY `izin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tb_jadwal_absensi`
--
ALTER TABLE `tb_jadwal_absensi`
  MODIFY `jadwal_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tb_kelas`
--
ALTER TABLE `tb_kelas`
  MODIFY `kelas_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_override_hari`
--
ALTER TABLE `tb_override_hari`
  MODIFY `override_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_pengaturan_absensi`
--
ALTER TABLE `tb_pengaturan_absensi`
  MODIFY `pengaturan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  MODIFY `siswa_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_users`
--
ALTER TABLE `tb_users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  ADD CONSTRAINT `fk_absensi_guru` FOREIGN KEY (`guru_id`) REFERENCES `tb_guru` (`guru_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_absensi_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `tb_siswa` (`siswa_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_guru`
--
ALTER TABLE `tb_guru`
  ADD CONSTRAINT `fk_guru_user` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_izin`
--
ALTER TABLE `tb_izin`
  ADD CONSTRAINT `fk_izin_approver` FOREIGN KEY (`disetujui_oleh`) REFERENCES `tb_users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_guru` FOREIGN KEY (`guru_id`) REFERENCES `tb_guru` (`guru_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_izin_siswa` FOREIGN KEY (`siswa_id`) REFERENCES `tb_siswa` (`siswa_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD CONSTRAINT `fk_siswa_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `tb_kelas` (`kelas_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_siswa_user` FOREIGN KEY (`user_id`) REFERENCES `tb_users` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
