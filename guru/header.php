<?php
// guru/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna login dan memiliki peran guru_tahfidz
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru_tahfidz') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/database.php';

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nama_guru = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];

// Ambil detail profile guru_tahfidz
try {
    $stmt_guru = $pdo->prepare("SELECT * FROM guru_tahfidz WHERE user_id = :user_id");
    $stmt_guru->execute(['user_id' => $user_id]);
    $guru_profile = $stmt_guru->fetch();

    if (!$guru_profile) {
        // Jika profile belum terbuat, buat otomatis demi integritas data
        $stmt_create = $pdo->prepare("INSERT INTO guru_tahfidz (user_id, nama_lengkap) VALUES (:user_id, :nama)");
        $stmt_create->execute([
            'user_id' => $user_id,
            'nama' => $_SESSION['nama_lengkap']
        ]);

        $stmt_guru->execute(['user_id' => $user_id]);
        $guru_profile = $stmt_guru->fetch();
    }

    $guru_id = $guru_profile['id'];
} catch (\PDOException $e) {
    die("Kesalahan sistem memuat profil: " . $e->getMessage());
}

// Hitung jumlah pesan konsultasi belum dibaca untuk guru ini
$unread_konsultasi = 0;
try {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM konsultasi WHERE penerima_id = :my_user_id AND is_read = 0");
    $stmt_unread->execute(['my_user_id' => $user_id]);
    $unread_konsultasi = (int) $stmt_unread->fetchColumn();
} catch (\PDOException $e) {
    // Abaikan
}

// Fungsi pembantu untuk menandai menu aktif
function isActive($pageName, $current_page)
{
    return $pageName === $current_page ? 'active' : '';
}

// Fungsi pembantu untuk log aktivitas riwayat pengguna
function logActivity($pdo, $userId, $aktivitas)
{
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO riwayat_pengguna (user_id, aktivitas, ip_address) VALUES (:user_id, :aktivitas, :ip)");
        $stmt->execute([
            'user_id' => $userId,
            'aktivitas' => $aktivitas,
            'ip' => $ip
        ]);
    } catch (\Exception $e) {
        // Abaikan
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guru Tahfidz | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
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
                        <span>Dashboard Guru</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('target.php', $current_page); ?>">
                    <a href="target.php">
                        <i class="fa-solid fa-bullseye"></i>
                        <span>Target Hafalan</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('setoran.php', $current_page); ?>">
                    <a href="setoran.php">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>Setoran Hafalan</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('progres.php', $current_page); ?>">
                    <a href="progres.php">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Progres Hafalan</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('konsultasi.php', $current_page); ?>">
                    <a href="konsultasi.php">
                        <i class="fa-solid fa-comments"></i>
                        <span>Konsultasi Orang Tua</span>
                        <?php if ($unread_konsultasi > 0): ?>
                            <span class="chat-badge" style="background-color: var(--error-color); color: #ffffff; padding: 2px 7px; border-radius: 20px; font-size: 10px; font-weight: bold; margin-left: auto;"><?php echo $unread_konsultasi; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('nilai.php', $current_page); ?>">
                    <a href="nilai.php">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <span>Laporan Nilai</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('sertifikat.php', $current_page); ?>">
                    <a href="sertifikat.php">
                        <i class="fa-solid fa-award"></i>
                        <span>Sertifikat</span>
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
                    <button onclick="toggleSidebar()"
                        style="background:none; border:none; font-size: 20px; cursor: pointer; color: var(--primary-color); display: none;"
                        class="mobile-toggle-btn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="admin-topbar-title">
                        <?php
                        switch ($current_page) {
                            case 'index.php':
                                echo 'Dashboard Guru Tahfidz';
                                break;
                            case 'target.php':
                                echo 'Kelola Target Hafalan';
                                break;
                            case 'setoran.php':
                                echo 'Tashih / Setoran Hafalan';
                                break;
                            case 'progres.php':
                                echo 'Progres Hafalan Siswa';
                                break;
                            case 'konsultasi.php':
                                echo 'Ruang Konsultasi Wali Murid';
                                break;
                            case 'nilai.php':
                                echo 'Rekap Laporan Nilai';
                                break;
                            case 'sertifikat.php':
                                echo 'Penerbitan Sertifikat';
                                break;
                            default:
                                echo 'Guru Portal';
                        }
                        ?>
                    </div>
                </div>

                <div class="admin-user-info">
                    <div style="text-align: right; line-height: 1.2;">
                        <div class="admin-user-name"><?php echo htmlspecialchars($nama_guru); ?></div>
                        <span class="admin-user-role">Guru Tahfidz</span>
                    </div>
                    <div class="user-avatar" style="width: 40px; height: 40px; font-size: 16px;">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                </div>
            </header>

            <!-- Area konten sesungguhnya -->
            <main class="admin-content">

                <script>
                    function toggleSidebar() {
                        document.getElementById('sidebar').classList.toggle('show');
                    }
                    if (window.innerWidth <= 768) {
                        document.querySelector('.mobile-toggle-btn').style.display = 'block';
                    }
                </script>