-- Nonaktifkan pengecekan foreign key sementara
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS sertifikat;
DROP TABLE IF EXISTS setoran_tahfidz;
DROP TABLE IF EXISTS target_hafalan;
DROP TABLE IF EXISTS konsultasi;
DROP TABLE IF EXISTS riwayat_pengguna;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS kelas;
DROP TABLE IF EXISTS orang_tua;
DROP TABLE IF EXISTS guru_tahfidz;
DROP TABLE IF EXISTS tahun_ajaran;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Buat tabel users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'guru_tahfidz', 'orang_tua') NOT NULL,
    security_question VARCHAR(255) NOT NULL,
    security_answer VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Buat tabel tahun_ajaran
CREATE TABLE tahun_ajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun VARCHAR(20) NOT NULL,
    semester ENUM('Ganjil','Genap') NOT NULL,
    status ENUM('aktif','tidak_aktif') DEFAULT 'tidak_aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Buat tabel guru_tahfidz
CREATE TABLE guru_tahfidz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nip VARCHAR(30) UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    no_hp VARCHAR(20),
    foto_profil VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Buat tabel orang_tua
CREATE TABLE orang_tua (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    hubungan ENUM('Ayah','Ibu','Wali') NULL,
    ttl VARCHAR(100) NULL,
    no_hp VARCHAR(20),
    alamat TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Buat tabel kelas
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(30) NOT NULL UNIQUE,
    wali_kelas_id INT,
    FOREIGN KEY (wali_kelas_id) REFERENCES guru_tahfidz(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Buat tabel siswa
CREATE TABLE siswa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nisn VARCHAR(20) NOT NULL UNIQUE,
    nama_lengkap VARCHAR(100) NOT NULL,
    ttl VARCHAR(100) NULL,
    jenis_kelamin ENUM('L','P') NULL,
    kelas_id INT,
    orang_tua_id INT,
    status_aktif ENUM('aktif','alumni','keluar') DEFAULT 'aktif',
    foto_profil VARCHAR(255) NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL,
    FOREIGN KEY (orang_tua_id) REFERENCES orang_tua(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Buat tabel riwayat_pengguna
CREATE TABLE riwayat_pengguna (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    aktivitas VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Buat tabel konsultasi
CREATE TABLE konsultasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pengirim_id INT NOT NULL,
    penerima_id INT NOT NULL,
    pesan TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pengirim_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (penerima_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Buat tabel target_hafalan
CREATE TABLE target_hafalan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    tahun_ajaran_id INT NOT NULL,
    target_juz VARCHAR(50) NULL,
    target_surah VARCHAR(100) NULL,
    target_ayat VARCHAR(255) NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (tahun_ajaran_id) REFERENCES tahun_ajaran(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Buat tabel setoran_tahfidz
CREATE TABLE setoran_tahfidz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    guru_id INT NOT NULL,
    tanggal DATE NOT NULL,
    surah VARCHAR(100) NOT NULL,
    ayat_mulai INT NOT NULL,
    ayat_selesai INT NOT NULL,
    jenis ENUM('ziadah','murajaah') NOT NULL,
    nilai CHAR(2) NOT NULL,
    nilai_angka INT NULL,
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES guru_tahfidz(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Buat tabel sertifikat
CREATE TABLE sertifikat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id INT NOT NULL,
    guru_id INT NOT NULL,
    juz_dihafal VARCHAR(50) NOT NULL,
    tanggal_lulus DATE NOT NULL,
    no_sertifikat VARCHAR(100) NOT NULL,
    predikat VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
    FOREIGN KEY (guru_id) REFERENCES guru_tahfidz(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MOCK DATA INSERTIONS
INSERT INTO users (id, username, password, nama_lengkap, email, role, security_question, security_answer) VALUES
(1,'admin','$2y$10$Ekt83PhFOpssEhmnNEyazutHN0i0//THwa7X8bvu9hOr.lQGzdpku','Administrator Sitaadz','admin@sitaadz.sch.id','admin','Nama sekolah dasar Anda?','mi al-adzkiya'),
(2,'guru','$2y$10$Ekt83PhFOpssEhmnNEyazutHN0i0//THwa7X8bvu9hOr.lQGzdpku','Ustadz Ahmad Tahfidz','ahmad@sitaadz.sch.id','guru_tahfidz','Nama sekolah dasar Anda?','mi al-adzkiya'),
(3,'orang_tua','$2y$10$Ekt83PhFOpssEhmnNEyazutHN0i0//THwa7X8bvu9hOr.lQGzdpku','Bapak/Ibu Wali Murid','walimurid@mail.com','orang_tua','Nama sekolah dasar Anda?','mi al-adzkiya');

INSERT INTO guru_tahfidz (id,user_id,nip,nama_lengkap,no_hp,foto_profil) VALUES
(1,2,'198905202015031002','Ustadz Ahmad Tahfidz','081234567890',NULL);

INSERT INTO orang_tua (id,user_id,nama_lengkap,hubungan,ttl,no_hp,alamat) VALUES
(1,3,'Bapak/Ibu Wali Murid','Wali','Jakarta, 01-01-1980','085799988877','Jl. Pendidikan No.45');

INSERT INTO kelas (id,nama_kelas,wali_kelas_id) VALUES
(1,'7-A Tahfidz',1),
(2,'7-B Tahfidz',NULL),
(3,'8-A Tahfidz',NULL);

INSERT INTO siswa (id,nisn,nama_lengkap,ttl,jenis_kelamin,kelas_id,orang_tua_id,status_aktif,foto_profil) VALUES
(1,'1234567890','Muhammad Al-Fatih','Jakarta, 12-12-2012','L',1,1,'aktif',NULL),
(2,'0987654321','Fatimah Az-Zahra','Jakarta, 10-10-2013','P',1,1,'aktif',NULL);

INSERT INTO tahun_ajaran (id,tahun,semester,status) VALUES
(1,'2025/2026','Ganjil','tidak_aktif'),
(2,'2025/2026','Genap','aktif');

INSERT INTO target_hafalan (siswa_id, tahun_ajaran_id, target_juz, target_surah, target_ayat, keterangan) VALUES
(1, 2, '30', 'An-Naba', '1-40', 'Fokus kelancaran makhraj');

INSERT INTO setoran_tahfidz (siswa_id, guru_id, tanggal, surah, ayat_mulai, ayat_selesai, jenis, nilai, nilai_angka, catatan) VALUES
(1, 1, '2026-07-20', 'An-Naba', 1, 10, 'ziadah', 'A', 95, 'Lancar sekali'),
(1, 1, '2026-07-21', 'An-Naba', 11, 20, 'ziadah', 'A-', 88, 'Sedikit ragu di akhir ayat');

INSERT INTO sertifikat (siswa_id, guru_id, juz_dihafal, tanggal_lulus, no_sertifikat, predikat) VALUES
(1, 1, 'Juz 30', '2026-07-21', 'SRT/2026/07/001', 'Jayyid Jiddan');

INSERT INTO riwayat_pengguna (user_id,aktivitas,ip_address) VALUES
(1,'Inisialisasi basis data awal sistem','127.0.0.1'),
(1,'Menambahkan akun guru dan orang tua contoh','127.0.0.1');