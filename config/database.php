<?php
// config/database.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ob_start();

if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
    $host = 'localhost';
    $db   = 'db_sitaadz';
    $user = 'root';
    $pass = ''; // Default XAMPP password is empty
} else {
    $host = 'sql210.infinityfree.com';
    $db   = 'if0_42392561_db_sitaadz';
    $user = 'if0_42392561';
    $pass = 'DwIdTDHguUSVI4H';
}
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

if (!function_exists('formatGrade')) {
    function formatGrade($nilai, $nilai_angka) {
        $nilai = trim($nilai);
        if ($nilai_angka === null) {
            return $nilai;
        }
        $score = intval($nilai_angka);
        if (strcasecmp($nilai, 'Sangat Lancar') === 0) {
            if ($score >= 95) return 'Sangat Lancar (A+)';
            if ($score >= 90) return 'Sangat Lancar (A)';
            return 'Sangat Lancar (A-)';
        } elseif (strcasecmp($nilai, 'Lancar Terbata-Bata') === 0) {
            if ($score >= 80) return 'Lancar Terbata-Bata (B+)';
            if ($score >= 75) return 'Lancar Terbata-Bata (B)';
            return 'Lancar Terbata-Bata (B-)';
        } elseif (strcasecmp($nilai, 'Lancar dengan Bantuan') === 0) {
            if ($score >= 70) return 'Lancar dengan Bantuan (C+)';
            if ($score >= 65) return 'Lancar dengan Bantuan (C)';
            return 'Lancar dengan Bantuan (C-)';
        } elseif (strcasecmp($nilai, 'Tidak Lancar / Ulangi') === 0) {
            return 'Tidak Lancar / Ulangi (D)';
        }
        return $nilai;
    }
}
?>
