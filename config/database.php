<?php
// config/database.php

$host = 'localhost';
$db   = 'db_sitaadz';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Di lingkungan produksi, sembunyikan detail error sensitif. Di sini kita tampilkan untuk memudahkan debugging lokal.
     die("Koneksi database gagal: " . $e->getMessage());
}
?>
