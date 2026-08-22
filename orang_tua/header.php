<?php
// orang_tua/header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna login dan memiliki peran orang_tua
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'orang_tua') {
    header("Location: ../login.php");
    exit;
}

require_once '../config/database.php';

$current_page = basename($_SERVER['SCRIPT_NAME']);
$nama_ortu = $_SESSION['nama_lengkap'];
$user_id = $_SESSION['user_id'];

// 1. Ambil detail profile orang_tua
try {
    $stmt_ortu = $pdo->prepare("SELECT id, nama_lengkap FROM orang_tua WHERE user_id = :user_id");
    $stmt_ortu->execute(['user_id' => $user_id]);
    $ortu_profile = $stmt_ortu->fetch();
    
    if (!$ortu_profile) {
        // Jika profile belum terbuat, buat otomatis demi integritas data
        $stmt_create = $pdo->prepare("INSERT INTO orang_tua (user_id, nama_lengkap) VALUES (:user_id, :nama)");
        $stmt_create->execute([
            'user_id' => $user_id,
            'nama' => $_SESSION['nama_lengkap']
        ]);
        $ortu_id = $pdo->lastInsertId();
    } else {
        $ortu_id = $ortu_profile['id'];
    }
} catch (\PDOException $e) {
    die("Kesalahan sistem memuat profil: " . $e->getMessage());
}

// 2. Ambil daftar anak (siswa yang terikat dengan orang_tua_id ini)
$daftar_anak = [];
try {
    $stmt_anak = $pdo->prepare("SELECT id, nisn, nama_lengkap, kelas_id, foto_profil FROM siswa WHERE orang_tua_id = :ortu_id");
    $stmt_anak->execute(['ortu_id' => $ortu_id]);
    $daftar_anak = $stmt_anak->fetchAll();
} catch (\PDOException $e) {
    // Abaikan
}

// 3. Logika ganti anak aktif
if (isset($_POST['change_child'])) {
    $child_id = intval($_POST['change_child']);
    // Pastikan anak tersebut memang anak dari orang tua ini
    foreach ($daftar_anak as $anak) {
        if ($anak['id'] === $child_id) {
            $_SESSION['selected_child_id'] = $child_id;
            break;
        }
    }
    header("Location: " . $current_page);
    exit;
}

// 4. Inisialisasi anak aktif default
if (!isset($_SESSION['selected_child_id']) && !empty($daftar_anak)) {
    $_SESSION['selected_child_id'] = $daftar_anak[0]['id'];
}

// Ambil info anak terpilih saat ini
$anak_aktif = null;
if (isset($_SESSION['selected_child_id'])) {
    foreach ($daftar_anak as $anak) {
        if ($anak['id'] === $_SESSION['selected_child_id']) {
            $anak_aktif = $anak;
            break;
        }
    }
}

// Hitung jumlah pesan konsultasi belum dibaca untuk orang tua ini
$unread_konsultasi = 0;
try {
    $stmt_unread = $pdo->prepare("SELECT COUNT(*) FROM konsultasi WHERE penerima_id = :my_user_id AND is_read = 0");
    $stmt_unread->execute(['my_user_id' => $user_id]);
    $unread_konsultasi = (int) $stmt_unread->fetchColumn();
} catch (\PDOException $e) {
    // Abaikan
}

// Fungsi pembantu untuk menandai menu aktif
function isActive($pageName, $current_page) {
    return $pageName === $current_page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wali Murid | MI Al-Adzkiya</title>
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
                    <i class="fa-solid fa-house-user"></i>
                    <span>Dashboard Wali</span>
                </a>
            </li>
            
            <?php if ($anak_aktif): ?>
                <li class="admin-menu-item <?php echo isActive('progres.php', $current_page); ?>">
                    <a href="progres.php">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Setoran Anak Saya</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('target.php', $current_page); ?>">
                    <a href="target.php">
                        <i class="fa-solid fa-bullseye"></i>
                        <span>Target Setoran</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('nilai.php', $current_page); ?>">
                    <a href="nilai.php">
                        <i class="fa-solid fa-star"></i>
                        <span>Nilai Setoran</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('catatan.php', $current_page); ?>">
                    <a href="catatan.php">
                        <i class="fa-solid fa-clipboard-question"></i>
                        <span>Catatan Hafalan</span>
                    </a>
                </li>
                <li class="admin-menu-item <?php echo isActive('sertifikat.php', $current_page); ?>">
                    <a href="sertifikat.php">
                        <i class="fa-solid fa-award"></i>
                        <span>Sertifikat</span>
                    </a>
                </li>
            <?php endif; ?>
            
            <li class="admin-menu-item <?php echo isActive('konsultasi.php', $current_page); ?>">
                <a href="konsultasi.php">
                    <i class="fa-solid fa-comments"></i>
                    <span>Konsultasi</span>
                    <?php if ($unread_konsultasi > 0): ?>
                        <span class="chat-badge" style="background-color: var(--error-color); color: #ffffff; padding: 2px 7px; border-radius: 20px; font-size: 10px; font-weight: bold; margin-left: auto;"><?php echo $unread_konsultasi; ?></span>
                    <?php endif; ?>
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
                <button onclick="toggleSidebar()" style="background:none; border:none; font-size: 20px; cursor: pointer; color: var(--primary-color); display: none;" class="mobile-toggle-btn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="admin-topbar-title">
                    <?php
                    switch ($current_page) {
                        case 'index.php': echo 'Dashboard Orang Tua / Wali'; break;
                        case 'progres.php': echo 'Setoran Anak Saya'; break;
                        case 'target.php': echo 'Target Setoran Anak'; break;
                        case 'nilai.php': echo 'Nilai Setoran Anak'; break;
                        case 'catatan.php': echo 'Catatan & Evaluasi Hafalan'; break;
                        case 'konsultasi.php': echo 'Ruang Konsultasi Wali Murid'; break;
                        case 'sertifikat.php': echo 'Sertifikat Kelulusan Anak'; break;
                        default: echo 'Wali Murid Portal';
                    }
                    ?>
                </div>
            </div>
            
            <div style="display: flex; align-items: center; gap: 20px;">
                <!-- Selektor Anak (Tampil jika anak lebih dari 1) -->
                <?php if (count($daftar_anak) > 1): ?>
                    <form action="" method="POST" id="childSelectorForm" style="margin: 0;">
                        <select name="change_child" onchange="document.getElementById('childSelectorForm').submit();" class="form-control" style="padding: 6px 12px; font-size: 13px; width: 200px; height: auto;">
                            <?php foreach ($daftar_anak as $anak): ?>
                                <option value="<?php echo $anak['id']; ?>" <?php echo $anak['id'] === $_SESSION['selected_child_id'] ? 'selected' : ''; ?>>
                                    Anak: <?php echo htmlspecialchars($anak['nama_lengkap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
                
                <div class="admin-user-info">
                    <div style="text-align: right; line-height: 1.2;">
                        <div class="admin-user-name"><?php echo htmlspecialchars($nama_ortu); ?></div>
                        <span class="admin-user-role">Orang Tua / Wali</span>
                    </div>
                    <div class="user-avatar" style="width: 40px; height: 40px; font-size: 16px;">
                        <i class="fa-solid fa-users"></i>
                    </div>
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
