<?php
// index.php
session_start();

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
} else {
    // Jika belum login, arahkan ke halaman login
    header("Location: login.php");
    exit;
}
?>
