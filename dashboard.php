<?php
// dashboard.php
session_start();

// Cek apakah pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Jika role adalah admin, arahkan ke panel admin
if ($role === 'admin') {
    header("Location: admin/index.php");
    exit;
}

// Jika role adalah orang_tua, arahkan ke panel orang tua
if ($role === 'orang_tua') {
    header("Location: orang_tua/index.php");
    exit;
}

// Jika role adalah guru_tahfidz, arahkan ke panel guru
if ($role === 'guru_tahfidz') {
    header("Location: guru/index.php");
    exit;
}

// Judul peran untuk tampilan lebih ramah
$role_title = '';
$role_badge_class = '';
switch ($role) {
    case 'admin':
        $role_title = 'Administrator';
        break;
    case 'guru_tahfidz':
        $role_title = 'Guru Tahfidz';
        break;
    case 'orang_tua':
        $role_title = 'Orang Tua / Wali';
        break;
    default:
        $role_title = 'Pengguna';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
</head>
<body style="align-items: flex-start; padding: 40px 0;">

<div class="container dashboard-container">
    <div class="dashboard-card">
        <!-- Dashboard Header -->
        <header class="dashboard-header">
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
                <div class="welcome-text">
                    <p>Selamat Datang,</p>
                    <h1><?php echo htmlspecialchars($nama_lengkap); ?></h1>
                    <span class="role-badge"><?php echo htmlspecialchars($role_title); ?></span>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 15px;">
                <div class="logo-dashboard-wrapper">
                    <img src="assets/images/logo.jpg" alt="Logo MI Al-Adzkiya" class="logo-dashboard">
                </div>
                <a href="logout.php" class="btn btn-secondary" style="padding: 10px 15px; font-size: 13px; border-radius: 8px;">
                    <i class="fa-solid fa-right-from-bracket" style="margin-right: 5px;"></i> Keluar
                </a>
            </div>
        </header>

        <!-- Dashboard Body -->
        <div class="dashboard-body">
            <h2 style="font-family: var(--font-heading); font-size: 20px; font-weight: 700; color: var(--primary-dark); margin-bottom: 10px;">
                Menu Utama Sistem Informasi
            </h2>
            <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 25px;">
                Akses fitur-fitur akademik dan tahfidz sesuai hak akses akun Anda di bawah ini:
            </p>

            <!-- Menus rendered based on user role -->
            <div class="menu-grid">
                <?php if ($role === 'admin'): ?>
                    <!-- Menu Admin -->
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-users-gear menu-icon"></i>
                        <span class="menu-title">Kelola Pengguna</span>
                        <span class="menu-desc">Kelola data guru, siswa, dan orang tua wali.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-book-bookmark menu-icon"></i>
                        <span class="menu-title">Pengaturan Kelas</span>
                        <span class="menu-desc">Atur jenjang kelas dan mata pelajaran.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-database menu-icon"></i>
                        <span class="menu-title">Backup Basis Data</span>
                        <span class="menu-desc">Cadangkan data sistem demi keamanan informasi.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-sliders menu-icon"></i>
                        <span class="menu-title">Konfigurasi Sistem</span>
                        <span class="menu-desc">Atur parameter dan profil sekolah.</span>
                    </a>

                <?php elseif ($role === 'guru_tahfidz'): ?>
                    <!-- Menu Guru Tahfidz -->
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-circle-check menu-icon"></i>
                        <span class="menu-title">Tashih Setoran</span>
                        <span class="menu-desc">Input hafalan baru harian siswa.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-chart-line menu-icon"></i>
                        <span class="menu-title">Rekap Hafalan</span>
                        <span class="menu-desc">Lihat statistik pencapaian hafalan siswa.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-calendar-day menu-icon"></i>
                        <span class="menu-title">Jadwal Halaqah</span>
                        <span class="menu-desc">Atur jadwal bimbingan halaqah tahfidz.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-clipboard-list menu-icon"></i>
                        <span class="menu-title">Nilai & Catatan</span>
                        <span class="menu-desc">Berikan nilai berkala dan catatan motivasi.</span>
                    </a>

                <?php elseif ($role === 'orang_tua'): ?>
                    <!-- Menu Orang Tua / Wali -->
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-child menu-icon"></i>
                        <span class="menu-title">Perkembangan Anak</span>
                        <span class="menu-desc">Pantau jumlah juz & surah yang telah dihafal anak.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-file-invoice menu-icon"></i>
                        <span class="menu-title">Raport Tahfidz</span>
                        <span class="menu-desc">Unduh raport pencapaian tahfidz anak per semester.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-envelope-open-text menu-icon"></i>
                        <span class="menu-title">Catatan Guru</span>
                        <span class="menu-desc">Baca evaluasi harian dari ustadz/ustadzah pembimbing.</span>
                    </a>
                    <a href="#" class="menu-item">
                        <i class="fa-solid fa-comments menu-icon"></i>
                        <span class="menu-title">Hubungi Pengajar</span>
                        <span class="menu-desc">Konsultasikan kendala atau kemajuan belajar anak.</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="footer-text">
        <p>&copy; <?php echo date('Y'); ?> MI Al-Adzkiya. All Rights Reserved.</p>
    </div>
</div>

</body>
</html>
