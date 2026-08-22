-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2026 at 02:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_sitaadz`
--

-- --------------------------------------------------------

--
-- Table structure for table `guru_tahfidz`
--

CREATE TABLE `guru_tahfidz` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `guru_tahfidz`
--

INSERT INTO `guru_tahfidz` (`id`, `user_id`, `nip`, `nama_lengkap`, `no_hp`, `foto_profil`) VALUES
(1, 2, '198905202015031002', 'Ustadz Ahmad Tahfidz', '081234567890', NULL),
(2, 4, '111222333', 'Putri Rosa Rosana', '083111334442', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(30) NOT NULL,
  `wali_kelas_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `wali_kelas_id`) VALUES
(1, '7-A Tahfidz', 1),
(2, '7-B Tahfidz', NULL),
(3, '8-A Tahfidz', NULL),
(4, 'Kelas 1', 2);

-- --------------------------------------------------------

--
-- Table structure for table `konsultasi`
--

CREATE TABLE `konsultasi` (
  `id` int(11) NOT NULL,
  `pengirim_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `pesan` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `konsultasi`
--

INSERT INTO `konsultasi` (`id`, `pengirim_id`, `penerima_id`, `pesan`, `is_read`, `created_at`) VALUES
(1, 4, 5, 'Halo orang tua salima, salima sudah cukup baik dalam menghafal surat attakasur', 1, '2026-07-28 01:32:27'),
(2, 5, 4, 'terimakasih Ibu atas bimbingannya', 1, '2026-07-28 01:34:44'),
(3, 4, 5, 'sama sama', 1, '2026-08-01 12:48:01'),
(4, 5, 4, 'baik', 1, '2026-08-01 13:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `orang_tua`
--

CREATE TABLE `orang_tua` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `hubungan` enum('Ayah','Ibu','Wali') DEFAULT NULL,
  `ttl` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orang_tua`
--

INSERT INTO `orang_tua` (`id`, `user_id`, `nama_lengkap`, `hubungan`, `ttl`, `no_hp`, `alamat`) VALUES
(1, 3, 'Bapak/Ibu Wali Murid', 'Wali', 'Jakarta, 01-01-1980', '085799988877', 'Jl. Pendidikan No.45'),
(2, 5, 'Ica Ramdani', 'Ayah', 'Garut, 2009-10-28', '083111334442', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_pengguna`
--

CREATE TABLE `riwayat_pengguna` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `riwayat_pengguna`
--

INSERT INTO `riwayat_pengguna` (`id`, `user_id`, `aktivitas`, `ip_address`, `created_at`) VALUES
(1, 1, 'Inisialisasi basis data awal sistem', '127.0.0.1', '2026-07-22 01:34:06'),
(2, 1, 'Menambahkan akun guru dan orang tua contoh', '127.0.0.1', '2026-07-22 01:34:06'),
(3, 1, 'Menambahkan siswa baru: Salima Hafsah (NISN: 111222333)', '::1', '2026-07-28 01:24:49'),
(4, 1, 'Menambahkan guru tahfidz baru: Putri Rosa Rosana (User ID: 4)', '::1', '2026-07-28 01:25:53'),
(5, 1, 'Menambahkan kelas baru: Kelas 1', '::1', '2026-07-28 01:26:16'),
(6, 1, 'Memasukkan siswa Salima Hafsah ke kelas Kelas 1', '::1', '2026-07-28 01:26:36'),
(7, 1, 'Menambahkan orang tua/wali baru: Ica Ramdani (User ID: 5)', '::1', '2026-07-28 01:28:29'),
(8, 4, 'Mengatur target hafalan massal untuk Kelas Kelas 1: Juz Juz 30, Surah At-Takasur, Ayat 1-3', '::1', '2026-07-28 01:30:22'),
(9, 4, 'Menginput setoran ziadah At-Takasur (Ayat 1 - 3) untuk Salima Hafsah dengan nilai B+ (78)', '::1', '2026-07-28 01:31:45'),
(10, 4, 'Menerbitkan sertifikat kelulusan 30 Juz untuk Salima Hafsah dengan nomor MI-ADZ/2026/07/164', '::1', '2026-07-28 01:33:05'),
(11, 4, 'Mengatur target hafalan massal untuk Kelas Kelas 1: Juz Juz 30, Surah At-Takasur, Ayat 1-3', '::1', '2026-08-01 12:29:08'),
(12, 4, 'Mengatur target hafalan massal untuk Kelas Kelas 1: Juz Juz 30, Surah At-Takasur, Ayat 1-3', '::1', '2026-08-01 12:29:32'),
(13, 4, 'Mengatur target hafalan massal untuk Kelas Kelas 1: Juz Juz 30, Surah At-Takasur, Ayat 1-3', '::1', '2026-08-01 12:32:16'),
(14, 4, 'Menginput setoran ziadah An-Nas (Ayat 1 - 6) untuk Salima Hafsah dengan nilai A+ (90)', '::1', '2026-08-01 12:47:04'),
(15, 4, 'Menginput setoran murajaah An-Nas (Ayat 1 - 6) untuk Salima Hafsah dengan nilai A (85)', '::1', '2026-08-01 12:51:28'),
(16, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/357', '::1', '2026-08-01 12:51:58'),
(17, 4, 'Menerbitkan sertifikat kelulusan 30 Juz untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/777', '::1', '2026-08-01 13:02:06'),
(18, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/245', '::1', '2026-08-01 13:05:59'),
(19, 4, 'Membatalkan/menghapus sertifikat 30 Juz nomor MI-ADZ/2026/07/164 siswa Salima Hafsah', '::1', '2026-08-01 13:06:21'),
(20, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/245 siswa Salima Hafsah', '::1', '2026-08-01 13:06:23'),
(21, 4, 'Menerbitkan sertifikat kelulusan 30 Juz untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/663', '::1', '2026-08-01 13:19:27'),
(22, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/027', '::1', '2026-08-01 13:29:53'),
(23, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/682', '::1', '2026-08-01 13:30:18'),
(24, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/565', '::1', '2026-08-01 13:49:31'),
(25, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/268', '::1', '2026-08-01 13:50:14'),
(26, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/763', '::1', '2026-08-01 13:53:08'),
(27, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/357 siswa Salima Hafsah', '::1', '2026-08-01 13:53:21'),
(28, 4, 'Membatalkan/menghapus sertifikat 30 Juz nomor MI-ADZ/2026/08/663 siswa Salima Hafsah', '::1', '2026-08-01 13:53:23'),
(29, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/763 siswa Salima Hafsah', '::1', '2026-08-01 13:53:28'),
(30, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/268 siswa Salima Hafsah', '::1', '2026-08-01 13:53:31'),
(31, 4, 'Membatalkan/menghapus sertifikat 30 Juz nomor MI-ADZ/2026/08/777 siswa Salima Hafsah', '::1', '2026-08-01 13:53:34'),
(32, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/565 siswa Salima Hafsah', '::1', '2026-08-01 13:53:36'),
(33, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/682 siswa Salima Hafsah', '::1', '2026-08-01 13:53:39'),
(34, 4, 'Membatalkan/menghapus sertifikat Juz 30 nomor MI-ADZ/2026/08/027 siswa Salima Hafsah', '::1', '2026-08-01 13:53:41'),
(35, 4, 'Menerbitkan sertifikat kelulusan Juz 30 untuk Salima Hafsah dengan nomor MI-ADZ/2026/08/889', '::1', '2026-08-01 13:54:06'),
(36, 1, 'Menghapus tahun ajaran ID 1 (2025/2026 Ganjil)', '::1', '2026-08-16 13:29:01');

-- --------------------------------------------------------

--
-- Table structure for table `sertifikat`
--

CREATE TABLE `sertifikat` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `juz_dihafal` varchar(50) NOT NULL,
  `tanggal_lulus` date NOT NULL,
  `no_sertifikat` varchar(100) NOT NULL,
  `predikat` varchar(50) NOT NULL,
  `catatan` text DEFAULT NULL,
  `nama_kepsek` varchar(150) DEFAULT NULL,
  `nip_kepsek` varchar(50) DEFAULT NULL,
  `nama_guru_ttd` varchar(150) DEFAULT NULL,
  `nip_guru_ttd` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sertifikat`
--

INSERT INTO `sertifikat` (`id`, `siswa_id`, `guru_id`, `juz_dihafal`, `tanggal_lulus`, `no_sertifikat`, `predikat`, `catatan`, `nama_kepsek`, `nip_kepsek`, `nama_guru_ttd`, `nip_guru_ttd`, `created_at`) VALUES
(1, 1, 1, 'Juz 30', '2026-07-21', 'SRT/2026/07/001', 'Lancar Terbata-Bata', NULL, NULL, NULL, NULL, NULL, '2026-07-22 01:34:06'),
(12, 3, 2, 'Juz 30', '2026-08-01', 'MI-ADZ/2026/08/889', 'Sangat Lancar', NULL, 'Teti Hamidah S.Pd', '197805122005041001', 'Putri Rosa Rosana', '111222333', '2026-08-01 13:54:06');

-- --------------------------------------------------------

--
-- Table structure for table `setoran_tahfidz`
--

CREATE TABLE `setoran_tahfidz` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `surah` varchar(100) NOT NULL,
  `ayat_mulai` int(11) NOT NULL,
  `ayat_selesai` int(11) NOT NULL,
  `jenis` enum('ziadah','murajaah') NOT NULL,
  `nilai` varchar(50) NOT NULL,
  `nilai_angka` int(11) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `setoran_tahfidz`
--

INSERT INTO `setoran_tahfidz` (`id`, `siswa_id`, `guru_id`, `tanggal`, `surah`, `ayat_mulai`, `ayat_selesai`, `jenis`, `nilai`, `nilai_angka`, `catatan`, `created_at`) VALUES
(1, 1, 1, '2026-07-20', 'An-Naba', 1, 10, 'ziadah', 'Sangat Lancar', 95, 'Lancar sekali', '2026-07-22 01:34:06'),
(2, 1, 1, '2026-07-21', 'An-Naba', 11, 20, 'ziadah', 'Lancar Terbata-Bata', 88, 'Sedikit ragu di akhir ayat', '2026-07-22 01:34:06'),
(3, 3, 2, '2026-07-28', 'At-Takasur', 1, 3, 'ziadah', 'Lancar Terbata-Bata', 78, 'lebih semangat lagi belajar nya', '2026-07-28 01:31:45'),
(4, 3, 2, '2026-08-01', 'An-Nas', 1, 6, 'ziadah', 'Sangat Lancar', 90, '', '2026-08-01 12:47:04'),
(5, 3, 2, '2026-08-01', 'An-Nas', 1, 6, 'murajaah', 'Sangat Lancar', 85, '', '2026-08-01 12:51:28');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `id` int(11) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `ttl` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `orang_tua_id` int(11) DEFAULT NULL,
  `status_aktif` enum('aktif','alumni','keluar') DEFAULT 'aktif',
  `foto_profil` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nisn`, `nama_lengkap`, `ttl`, `jenis_kelamin`, `kelas_id`, `orang_tua_id`, `status_aktif`, `foto_profil`) VALUES
(1, '1234567890', 'Muhammad Al-Fatih', 'Jakarta, 12-12-2012', 'L', 1, 1, 'aktif', NULL),
(2, '0987654321', 'Fatimah Az-Zahra', 'Jakarta, 10-10-2013', 'P', 1, 1, 'aktif', NULL),
(3, '111222333', 'Salima Hafsah', 'Garut, 2019-09-10', 'P', 4, 2, 'aktif', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tahun_ajaran`
--

CREATE TABLE `tahun_ajaran` (
  `id` int(11) NOT NULL,
  `tahun` varchar(20) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `status` enum('aktif','tidak_aktif') DEFAULT 'tidak_aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tahun_ajaran`
--

INSERT INTO `tahun_ajaran` (`id`, `tahun`, `semester`, `status`, `created_at`) VALUES
(2, '2025/2026', 'Genap', 'tidak_aktif', '2026-07-22 01:34:06'),
(3, '2026/2027', 'Ganjil', 'aktif', '2026-08-07 00:30:50');

-- --------------------------------------------------------

--
-- Table structure for table `target_hafalan`
--

CREATE TABLE `target_hafalan` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `tahun_ajaran_id` int(11) NOT NULL,
  `target_juz` varchar(50) DEFAULT NULL,
  `target_surah` varchar(100) DEFAULT NULL,
  `target_ayat` varchar(255) DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `target_hafalan`
--

INSERT INTO `target_hafalan` (`id`, `siswa_id`, `tahun_ajaran_id`, `target_juz`, `target_surah`, `target_ayat`, `keterangan`, `created_at`) VALUES
(1, 1, 2, '30', 'An-Naba', '1-40', 'Fokus kelancaran makhraj', '2026-07-22 01:34:06'),
(2, 3, 2, 'Juz 30', 'At-Takasur', '1-3', 'lancar', '2026-07-28 01:30:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','guru_tahfidz','orang_tua') NOT NULL,
  `security_question` varchar(255) NOT NULL,
  `security_answer` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `email`, `role`, `security_question`, `security_answer`, `created_at`) VALUES
(1, 'admin', '$2y$10$9ks0xJ17O99DZPpjKiAIc.MplaUHkrB4W42Q9L3erW/kQE/k.a9R6', 'Administrator Sitaadz', 'admin@sitaadz.sch.id', 'admin', 'Nama sekolah dasar Anda?', 'mi al-adzkiya', '2026-07-22 01:34:06'),
(2, 'guru', '$2y$10$oI8Yp.jnwX9Sv40LIntZD.pp5ro/ocAFiy.l3.K0ldbd20ZXq2jEi', 'Ustadz Ahmad Tahfidz', 'ahmad@sitaadz.sch.id', 'guru_tahfidz', 'Nama sekolah dasar Anda?', 'mi al-adzkiya', '2026-07-22 01:34:06'),
(3, 'orang_tua', '$2y$10$e.QlGd0mohMQcQmVAxtMtu9zph7WYEa5acTJNCEzxjiZ2CZrqeB72', 'Bapak/Ibu Wali Murid', 'walimurid@mail.com', 'orang_tua', 'Nama sekolah dasar Anda?', 'mi al-adzkiya', '2026-07-22 01:34:06'),
(4, 'putrirosa', '$2y$10$j4vw9yQvHqHupvWdeCronuKqylzVKd6votrs1ZQ8T8GrWiLko//.a', 'Putri Rosa Rosana', 'putrirosa@gmail.com', 'guru_tahfidz', 'Nama sekolah dasar Anda?', 'mi al-adzkiya', '2026-07-28 01:25:53'),
(5, 'icaramdani', '$2y$10$j4vw9yQvHqHupvWdeCronuKqylzVKd6votrs1ZQ8T8GrWiLko//.a', 'Ica Ramdani', '', 'orang_tua', 'Nama sekolah dasar Anda?', 'mi al-adzkiya', '2026-07-28 01:28:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `guru_tahfidz`
--
ALTER TABLE `guru_tahfidz`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `nip` (`nip`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_kelas` (`nama_kelas`),
  ADD KEY `wali_kelas_id` (`wali_kelas_id`);

--
-- Indexes for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengirim_id` (`pengirim_id`),
  ADD KEY `penerima_id` (`penerima_id`);

--
-- Indexes for table `orang_tua`
--
ALTER TABLE `orang_tua`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `riwayat_pengguna`
--
ALTER TABLE `riwayat_pengguna`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `guru_id` (`guru_id`);

--
-- Indexes for table `setoran_tahfidz`
--
ALTER TABLE `setoran_tahfidz`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `guru_id` (`guru_id`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD KEY `kelas_id` (`kelas_id`),
  ADD KEY `orang_tua_id` (`orang_tua_id`);

--
-- Indexes for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `target_hafalan`
--
ALTER TABLE `target_hafalan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `tahun_ajaran_id` (`tahun_ajaran_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `guru_tahfidz`
--
ALTER TABLE `guru_tahfidz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `konsultasi`
--
ALTER TABLE `konsultasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orang_tua`
--
ALTER TABLE `orang_tua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `riwayat_pengguna`
--
ALTER TABLE `riwayat_pengguna`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `sertifikat`
--
ALTER TABLE `sertifikat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `setoran_tahfidz`
--
ALTER TABLE `setoran_tahfidz`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tahun_ajaran`
--
ALTER TABLE `tahun_ajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `target_hafalan`
--
ALTER TABLE `target_hafalan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `guru_tahfidz`
--
ALTER TABLE `guru_tahfidz`
  ADD CONSTRAINT `guru_tahfidz_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`wali_kelas_id`) REFERENCES `guru_tahfidz` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `konsultasi`
--
ALTER TABLE `konsultasi`
  ADD CONSTRAINT `konsultasi_ibfk_1` FOREIGN KEY (`pengirim_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `konsultasi_ibfk_2` FOREIGN KEY (`penerima_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orang_tua`
--
ALTER TABLE `orang_tua`
  ADD CONSTRAINT `orang_tua_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `riwayat_pengguna`
--
ALTER TABLE `riwayat_pengguna`
  ADD CONSTRAINT `riwayat_pengguna_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD CONSTRAINT `sertifikat_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sertifikat_ibfk_2` FOREIGN KEY (`guru_id`) REFERENCES `guru_tahfidz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `setoran_tahfidz`
--
ALTER TABLE `setoran_tahfidz`
  ADD CONSTRAINT `setoran_tahfidz_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `setoran_tahfidz_ibfk_2` FOREIGN KEY (`guru_id`) REFERENCES `guru_tahfidz` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`orang_tua_id`) REFERENCES `orang_tua` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `target_hafalan`
--
ALTER TABLE `target_hafalan`
  ADD CONSTRAINT `target_hafalan_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `target_hafalan_ibfk_2` FOREIGN KEY (`tahun_ajaran_id`) REFERENCES `tahun_ajaran` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
