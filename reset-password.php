<?php
// reset-password.php
session_start();
require_once 'config/database.php';

// Cek hak akses untuk reset password
if (!isset($_SESSION['allow_reset']) || $_SESSION['allow_reset'] !== true || !isset($_SESSION['reset_username'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
$username = $_SESSION['reset_username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirm_password === '') {
        $error = 'Silakan isi kedua kolom kata sandi.';
    } elseif (strlen($password) < 6) {
        $error = 'Kata sandi harus minimal 6 karakter.';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        try {
            // Hash password baru
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password di database
            $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE username = :username");
            $stmt->execute([
                'password' => $hashed_password,
                'username' => $username
            ]);
            
            // Hapus session sementara setelah sukses
            unset($_SESSION['allow_reset']);
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_username']);
            unset($_SESSION['reset_question']);
            
            // Redirect ke login dengan indikator sukses
            header("Location: login.php?reset_success=1");
            exit;
        } catch (\PDOException $e) {
            $error = 'Gagal memperbarui kata sandi. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Kata Sandi | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.5">
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <div class="logo-container">
                <img src="assets/images/logo.jpg" alt="Logo MI Al-Adzkiya" class="logo">
            </div>
            <h1 class="title">Atur Ulang Sandi</h1>
            <p class="subtitle">Buat Kata Sandi Baru untuk Akun Anda</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <div style="background-color: #f0fdf4; border: 1px solid var(--card-border); border-radius: 12px; padding: 15px; margin-bottom: 20px; font-size: 13px;">
            <strong>Mereset Sandi Akun:</strong> <?php echo htmlspecialchars($username); ?>
        </div>

        <form action="reset-password.php" method="POST">
            <div class="form-group">
                <label for="password" class="form-label">Kata Sandi Baru</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" placeholder="Kata sandi baru (min. 6 karakter)" required autofocus>
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
                <div class="input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Ulangi kata sandi baru" required>
                    <i class="fa-solid fa-circle-check input-icon"></i>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 25px;">
                <a href="forgot-password.php?retry=1" class="btn btn-secondary" style="flex: 1;">Kembali</a>
                <button type="submit" class="btn btn-primary" style="flex: 1.5;">
                    <span>Simpan</span>
                    <i class="fa-solid fa-floppy-disk"></i>
                </button>
            </div>
        </form>
    </div>
    
    <div class="footer-text">
        <p>&copy; <?php echo date('Y'); ?> MI Al-Adzkiya. All Rights Reserved.</p>
    </div>
</div>

</body>
</html>
