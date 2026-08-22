<?php
// admin/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna login dan memiliki peran administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nama_admin = $_SESSION['nama_lengkap'];

// Fungsi pembantu untuk menandai menu aktif
function isActive($pageName, $current_page) {
    return $pageName === $current_page ? 'active' : '';
}

// Fungsi pembantu untuk log aktivitas riwayat pengguna
function logActivity($pdo, $userId, $aktivitas) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO riwayat_pengguna (user_id, aktivitas, ip_address) VALUES (:user_id, :aktivitas, :ip)");
        $stmt->execute([
            'user_id' => $userId,
            'aktivitas' => $aktivitas,
            'ip' => $ip
        ]);
    } catch (\Exception $e) {
        // Abaikan error log agar alur utama tidak terganggu
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS (Gaya utama) -->
    <link rel="stylesheet" href="../assets/css/style.css?v=1.5">
</head>
<body class="admin-body">

<div class="admin-layout">
    <!-- Sidebar Kiri -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="admin-sidebar-header">
            <img src="../assets/images/logo.jpg" alt="Logo MI Al-Adzkiya" class="admin-sidebar-logo">
            <span class="admin-sidebar-title">MI AL-ADZKIYA</span>
        </div>
        
        <ul class="admin-sidebar-menu">
            <li class="admin-menu-item <?php echo isActive('index.php', $current_page); ?>">
                <a href="index.php">
                    <i class="fa-solid fa-gauge"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('tahun_ajaran.php', $current_page); ?>">
                <a href="tahun_ajaran.php">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Tahun Ajaran</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('siswa.php', $current_page); ?>">
                <a href="siswa.php">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>Data Siswa</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('kelas.php', $current_page); ?>">
                <a href="kelas.php">
                    <i class="fa-solid fa-school"></i>
                    <span>Data Kelas</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('guru.php', $current_page); ?>">
                <a href="guru.php">
                    <i class="fa-solid fa-chalkboard-user"></i>
                    <span>Data Guru Tahfidz</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('orang_tua.php', $current_page); ?>">
                <a href="orang_tua.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Data Orang Tua/Wali</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('user_management.php', $current_page); ?>">
                <a href="user_management.php">
                    <i class="fa-solid fa-user-gear"></i>
                    <span>Manajemen User</span>
                </a>
            </li>
            <li class="admin-menu-item <?php echo isActive('riwayat.php', $current_page); ?>">
                <a href="riwayat.php">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat Pengguna</span>
                </a>
            </li>
        </ul>
        
        <div class="admin-sidebar-footer">
            <a href="../logout.php" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div style="display: flex; align-items: center; gap: 15px;">
                <!-- Hamburger menu for mobile layout -->
                <button onclick="toggleSidebar()" style="background:none; border:none; font-size: 20px; cursor: pointer; color: var(--primary-color); display: none;" class="mobile-toggle-btn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="admin-topbar-title">
                    <?php
                    // Set title based on current page
                    switch ($current_page) {
                        case 'index.php': echo 'Dashboard'; break;
                        case 'tahun_ajaran.php': echo 'Manajemen Tahun Ajaran'; break;
                        case 'kelas.php': echo 'Manajemen Data Kelas'; break;
                        case 'guru.php': echo 'Manajemen Data Guru Tahfidz'; break;
                        case 'orang_tua.php': echo 'Manajemen Data Orang Tua/Wali'; break;
                        case 'siswa.php': echo 'Manajemen Data Siswa'; break;
                        case 'user_management.php': echo 'Manajemen Akun Pengguna'; break;
                        case 'riwayat.php': echo 'Log Riwayat Pengguna'; break;
                        default: echo 'Admin Panel';
                    }
                    ?>
                </div>
            </div>
            
            <div class="admin-user-info">
                <div style="text-align: right; line-height: 1.2;">
                    <div class="admin-user-name"><?php echo htmlspecialchars($nama_admin); ?></div>
                    <span class="admin-user-role">Administrator</span>
                </div>
                <div class="user-avatar" style="width: 40px; height: 40px; font-size: 16px;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
        </header>

        <!-- Area konten sesungguhnya -->
        <main class="admin-content">
            
            <!-- Tambahkan JS sederhana untuk toggle sidebar di mobile -->
            <script>
            function toggleSidebar() {
                document.getElementById('sidebar').classList.toggle('show');
            }
            
            // Tampilkan tombol hamburger jika layar kecil
            if (window.innerWidth <= 768) {
                document.querySelector('.mobile-toggle-btn').style.display = 'block';
            }
            </script>
