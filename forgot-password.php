<?php
// forgot-password.php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';
$step = 1; // 1 = Input Username, 2 = Jawaban Pertanyaan Keamanan
$username = '';
$question = '';

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Bersihkan session reset jika baru masuk halaman pertama kali secara normal (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['retry'])) {
    unset($_SESSION['reset_user_id']);
    unset($_SESSION['reset_username']);
    unset($_SESSION['reset_question']);
}

// Cek jika session reset masih aktif (berada di langkah 2)
if (isset($_SESSION['reset_username']) && isset($_SESSION['reset_question'])) {
    $step = 2;
    $username = $_SESSION['reset_username'];
    $question = $_SESSION['reset_question'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_username'])) {
        // LANGKAH 1: Verifikasi Username
        $username = trim($_POST['username'] ?? '');
        
        if ($username === '') {
            $error = 'Silakan masukkan nama pengguna Anda.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, security_question FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Set session sementara untuk proses reset
                    $_SESSION['reset_user_id'] = $user['id'];
                    $_SESSION['reset_username'] = $user['username'];
                    $_SESSION['reset_question'] = $user['security_question'];
                    
                    $step = 2;
                    $question = $user['security_question'];
                } else {
                    $error = 'Nama pengguna tidak terdaftar dalam sistem.';
                }
            } catch (\PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    } elseif (isset($_POST['submit_answer'])) {
        // LANGKAH 2: Verifikasi Jawaban
        $answer = trim($_POST['answer'] ?? '');
        $username = $_SESSION['reset_username'] ?? '';
        
        if ($answer === '') {
            $error = 'Silakan masukkan jawaban pertanyaan keamanan Anda.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Normalisasi jawaban: lowercase dan hilangkan spasi berlebih
                    $normalized_input = strtolower(preg_replace('/\s+/', ' ', $answer));
                    $normalized_db = strtolower(preg_replace('/\s+/', ' ', $user['security_answer']));
                    
                    if ($normalized_input === $normalized_db) {
                        // Kredensial cocok, izinkan reset password
                        $_SESSION['allow_reset'] = true;
                        header("Location: reset-password.php");
                        exit;
                    } else {
                        $error = 'Jawaban pertanyaan keamanan Anda salah.';
                    }
                } else {
                    $error = 'Sesi telah kedaluwarsa. Silakan mulai kembali.';
                    $step = 1;
                    unset($_SESSION['reset_user_id']);
                    unset($_SESSION['reset_username']);
                    unset($_SESSION['reset_question']);
                }
            } catch (\PDOException $e) {
                $error = 'Terjadi kesalahan sistem. Silakan coba lagi.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Kata Sandi | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=1.3">
</head>
<body>

<div class="container">
    <div class="card">
        <div class="header">
            <div class="logo-container">
                <img src="assets/images/logo.jpg" alt="Logo MI Al-Adzkiya" class="logo">
            </div>
            <h1 class="title">Lupa Kata Sandi</h1>
            <p class="subtitle">Pemulihan Akun MI Al-Adzkiya</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Form Langkah 1: Masukkan Username -->
            <form action="forgot-password.php" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan nama pengguna Anda" required value="<?php echo htmlspecialchars($username); ?>">
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                    <small style="display: block; margin-top: 8px; font-size: 11px; color: var(--text-muted);">
                        Masukkan nama pengguna Anda untuk mencari pertanyaan keamanan terkait akun Anda.
                    </small>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <a href="login.php" class="btn btn-secondary" style="flex: 1;">Batal</a>
                    <button type="submit" name="submit_username" class="btn btn-primary" style="flex: 1.5;">
                        <span>Lanjut</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </div>
            </form>

        <?php else: ?>
            <!-- Form Langkah 2: Jawab Pertanyaan Keamanan -->
            <form action="forgot-password.php" method="POST">
                <div style="background-color: #f0fdf4; border: 1px solid var(--card-border); border-radius: 12px; padding: 15px; margin-bottom: 20px; font-size: 13px;">
                    <strong>Akun:</strong> <?php echo htmlspecialchars($username); ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Pertanyaan Keamanan</label>
                    <div style="font-size: 15px; font-weight: 600; color: var(--primary-dark); margin-bottom: 12px; line-height: 1.4;">
                        <i class="fa-solid fa-circle-question" style="color: var(--primary-color); margin-right: 6px;"></i>
                        <?php echo htmlspecialchars($question); ?>
                    </div>
                    
                    <label for="answer" class="form-label">Jawaban Anda</label>
                    <div class="input-wrapper">
                        <input type="text" id="answer" name="answer" class="form-control" placeholder="Masukkan jawaban Anda" required autocomplete="off" autofocus>
                        <i class="fa-solid fa-key input-icon"></i>
                    </div>
                    <small style="display: block; margin-top: 8px; font-size: 11px; color: var(--text-muted);">
                        *Jawaban tidak sensitif huruf besar/kecil (case-insensitive).
                    </small>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <a href="forgot-password.php?retry=1" class="btn btn-secondary" style="flex: 1;">Kembali</a>
                    <button type="submit" name="submit_answer" class="btn btn-primary" style="flex: 1.5;">
                        <span>Verifikasi</span>
                        <i class="fa-solid fa-circle-check"></i>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="footer-text">
        <p>&copy; <?php echo date('Y'); ?> MI Al-Adzkiya. All Rights Reserved.</p>
    </div>
</div>

</body>
</html>
