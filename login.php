<?php
// login.php
session_start();

// Jika sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Silakan masukkan nama pengguna dan kata sandi.';
    } else {
        try {
            // Ambil data user dari database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            // Verifikasi password dan user
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role'] = $user['role'];

                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'Nama pengguna atau kata sandi salah.';
            }
        } catch (\PDOException $e) {
            $error = 'Terjadi kesalahan sistem. Silakan coba beberapa saat lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MI Al-Adzkiya</title>
    <!-- Font Awesome CDN for icons -->
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
                <h1 class="title">MI AL-ADZKIYA</h1>
                <p class="subtitle">Sistem Informasi Tahfidz Adzkiya </p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['reset_success']) && $_GET['reset_success'] == 1): ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <div>Kata sandi berhasil diperbarui. Silakan login.</div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username" class="form-label">Nama Pengguna</label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="form-control"
                            placeholder="Masukkan nama pengguna" required
                            value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                        <i class="fa-solid fa-user input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Masukkan kata sandi" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                </div>

                <div class="form-actions">
                    <label class="checkbox-container">
                        <input type="checkbox" id="show-password">
                        <span>Tampilkan sandi</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-link">Lupa Kata Sandi?</a>
                </div>

                <button type="submit" class="btn btn-primary">
                    <span>Masuk</span>
                    <i class="fa-solid fa-arrow-right-to-bracket"></i>
                </button>
            </form>
        </div>

        <div class="footer-text">
            <p>&copy; <?php echo date('Y'); ?> MI Al-Adzkiya. All Rights Reserved.</p>
        </div>
    </div>

    <script>
        document.getElementById('show-password').addEventListener('change', function() {
            const passwordField = document.getElementById('password');
            if (this.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        });
    </script>

</body>

</html>