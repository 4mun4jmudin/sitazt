<?php
// guru/sertifikat.php
require_once '../config/database.php';

// Migrasi DB: Tambahkan catatan jika belum ada
try {
    $pdo->exec("ALTER TABLE sertifikat ADD COLUMN catatan TEXT NULL AFTER predikat");
} catch (\PDOException $e) {
    // Abaikan jika sudah ada
}
try {
    $pdo->exec("ALTER TABLE sertifikat ADD COLUMN nama_kepsek VARCHAR(150) NULL AFTER catatan");
    $pdo->exec("ALTER TABLE sertifikat ADD COLUMN nip_kepsek VARCHAR(50) NULL AFTER nama_kepsek");
    $pdo->exec("ALTER TABLE sertifikat ADD COLUMN nama_guru_ttd VARCHAR(150) NULL AFTER nip_kepsek");
    $pdo->exec("ALTER TABLE sertifikat ADD COLUMN nip_guru_ttd VARCHAR(50) NULL AFTER nama_guru_ttd");
} catch (\PDOException $e) {
    // Abaikan jika sudah ada
}
try {
    $stmt_check_old_cert = $pdo->query("SELECT COUNT(*) FROM sertifikat WHERE predikat IN ('Mumtaz', 'Jayyid Jiddan', 'Jayyid', 'Maqbul', 'Dhaif')");
    if ($stmt_check_old_cert && $stmt_check_old_cert->fetchColumn() > 0) {
        $pdo->exec("UPDATE sertifikat SET predikat = 'Sangat Lancar' WHERE predikat = 'Mumtaz'");
        $pdo->exec("UPDATE sertifikat SET predikat = 'Lancar Terbata-Bata' WHERE predikat IN ('Jayyid Jiddan', 'Jayyid')");
        $pdo->exec("UPDATE sertifikat SET predikat = 'Lancar dengan Bantuan' WHERE predikat = 'Maqbul'");
        $pdo->exec("UPDATE sertifikat SET predikat = 'Tidak Lancar / Ulangi' WHERE predikat = 'Dhaif'");
    }
} catch (\PDOException $e) {
    // Abaikan jika gagal
}

$print_cert_id = intval($_GET['print_cert_id'] ?? 0);
if ($print_cert_id > 0) {
    // Jalankan tampilan cetak sertifikat
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru_tahfidz') {
        die("Akses ditolak.");
    }
    
    try {
        $stmt_cert = $pdo->prepare("
            SELECT c.*, s.nama_lengkap AS nama_siswa, s.nisn, gt.nama_lengkap AS nama_guru, gt.nip AS nip_guru 
            FROM sertifikat c
            JOIN siswa s ON c.siswa_id = s.id
            JOIN guru_tahfidz gt ON c.guru_id = gt.id
            WHERE c.id = :id
        ");
        $stmt_cert->execute(['id' => $print_cert_id]);
        $cert = $stmt_cert->fetch();
        
        if (!$cert) {
            die("Sertifikat tidak ditemukan.");
        }
    } catch (\PDOException $e) {
        die("Error database: " . $e->getMessage());
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Sertifikat Tahfidz - <?php echo htmlspecialchars($cert['nama_siswa']); ?></title>
        <!-- Font Google Outfit & Great Vibes for classic calligraphy script -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Outfit:wght@400;600;700;800&family=Cinzel:wght@600;700;800&display=swap" rel="stylesheet">
        
        <style>
            body {
                margin: 0;
                padding: 0;
                background-color: #f8fafc;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                font-family: 'Outfit', sans-serif;
            }
            .certificate-wrapper {
                width: 900px;
                height: 680px;
                background-color: #ffffff;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                border-radius: 8px;
                position: relative;
                padding: 25px;
                box-sizing: border-box;
                overflow: hidden;
            }
            /* Premium Border Style */
            .border-outer {
                width: 100%;
                height: 100%;
                border: 15px solid #0d5c34; /* Green */
                box-sizing: border-box;
                position: relative;
                padding: 15px;
            }
            .border-inner {
                width: 100%;
                height: 100%;
                border: 4px double #ffd700; /* Gold */
                box-sizing: border-box;
                position: relative;
                padding: 10px 45px;
            }
            /* Corner ornaments using pure CSS */
            .corner-ornament {
                position: absolute;
                width: 40px;
                height: 40px;
                border: 5px solid #ffd700;
                z-index: 10;
            }
            .top-left { top: -10px; left: -10px; border-right: none; border-bottom: none; }
            .top-right { top: -10px; right: -10px; border-left: none; border-bottom: none; }
            .bottom-left { bottom: -10px; left: -10px; border-right: none; border-top: none; }
            .bottom-right { bottom: -10px; right: -10px; border-left: none; border-top: none; }
            
            .kop-surat-cert {
                display: flex;
                align-items: center;
                justify-content: center;
                border-bottom: 2px solid #0d5c34;
                padding-bottom: 8px;
                margin-bottom: 15px;
                gap: 15px;
            }
            .kop-logo-cert img {
                height: 55px;
            }
            .kop-text-cert {
                text-align: center;
            }
            .kop-text-cert h2 {
                margin: 0;
                font-size: 12px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
            }
            .kop-text-cert h1 {
                margin: 1px 0;
                font-size: 14px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
            }
            .kop-text-cert h3 {
                margin: 1px 0;
                font-size: 10px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
            }
            .kop-text-cert p {
                margin: 1px 0;
                font-size: 9px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
            }
            .kop-text-cert p.email {
                margin-top: 2px;
                font-size: 9px;
            }
            .kop-text-cert p.email a {
                color: #0000ff;
                text-decoration: underline;
            }
            .cert-title {
                font-family: 'Cinzel', serif;
                font-size: 28px;
                font-weight: 800;
                color: #ccab00; /* Goldish */
                text-align: center;
                margin: 8px 0 2px 0;
                letter-spacing: 4px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            }
            .cert-subtitle {
                text-align: center;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 3px;
                color: #64748b;
                margin-bottom: 10px;
                font-weight: 600;
            }
            .cert-body {
                text-align: center;
                font-size: 15px;
                line-height: 1.8;
                color: #334155;
            }
            .student-name {
                font-family: 'Great Vibes', cursive;
                font-size: 38px;
                color: #073b20;
                margin: 4px 0;
                display: block;
                font-weight: 400;
            }
            .predikat-badge {
                display: inline-block;
                border: 2px solid #ccab00;
                color: #0d5c34;
                padding: 4px 20px;
                border-radius: 30px;
                font-weight: 700;
                margin-top: 8px;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 1px;
                background-color: #fbfbf0;
            }
            .signature-section {
                display: flex;
                justify-content: space-between;
                margin-top: 20px;
                padding: 0 40px;
            }
            .sig-box {
                text-align: center;
                font-size: 12px;
                color: #475569;
                width: 200px;
            }
            .sig-space {
                height: 55px;
            }
            .no-print-bar {
                background-color: #f1f5f9;
                padding: 15px;
                text-align: center;
                border-bottom: 1px solid #e2e8f0;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                z-index: 100;
            }
            @media print {
                body {
                    background-color: #ffffff;
                }
                .no-print-bar {
                    display: none;
                }
                .certificate-wrapper {
                    box-shadow: none;
                    margin: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="no-print-bar">
            <button onclick="window.print();" style="padding: 10px 20px; font-weight: bold; background-color: #0d5c34; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: sans-serif;">
                CETAK SERTIFIKAT TAHFIDZ
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; font-weight: bold; background-color: #64748b; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: sans-serif; margin-left: 10px;">
                TUTUP
            </button>
        </div>
        
        <div class="certificate-wrapper" style="margin-top: 60px;">
            <div class="border-outer">
                <div class="border-inner">
                    <div class="corner-ornament top-left"></div>
                    <div class="corner-ornament top-right"></div>
                    <div class="corner-ornament bottom-left"></div>
                    <div class="corner-ornament bottom-right"></div>
                    
                    <div class="kop-surat-cert">
                        <div class="kop-logo-cert">
                            <img src="../assets/images/logo.jpg" alt="Logo">
                        </div>
                        <div class="kop-text-cert">
                            <h2>YAYASAN AL-BAROROH BLUBUR LIMBANGAN</h2>
                            <h1>MADRASAH IBTIDAIYAH (MI) AL-ADZKIYA</h1>
                            <h3>STATUS <u>TERAKREDITASI : B</u></h3>
                            <p>Kp. Cicadas RT.02 RW.08 Desa Pasirwaru Kec. Bl. Limbangan Garut 44186</p>
                            <p class="email">email : <a href="mailto:mi.aladzkiya@yahoo.com">mi.aladzkiya@yahoo.com</a></p>
                        </div>
                    </div>
                    
                    <div class="cert-title">SERTIFIKAT TAHFIDZ</div>
                    <div class="cert-subtitle">No: <?php echo htmlspecialchars($cert['no_sertifikat']); ?></div>
                    
                    <div class="cert-body">
                        Dengan memuji syukur kehadirat Allah SWT, dinyatakan bahwa:
                        <span class="student-name"><?php echo htmlspecialchars($cert['nama_siswa']); ?></span>
                        NISN: <strong><?php echo htmlspecialchars($cert['nisn']); ?></strong> telah berhasil menyelesaikan ujian kelulusan hafalan:
                        <br>
                        <strong style="font-size: 18px; color: #0d5c34; display: block; margin-top: 8px;">
                            <?php echo htmlspecialchars($cert['juz_dihafal']); ?> Al-Qur'anul Karim
                        </strong>
                        dengan predikat kelancaran kelulusan:
                        <br>
                        <?php
                        $predikat_label = htmlspecialchars($cert['predikat']);
                        if ($predikat_label === 'Sangat Lancar') {
                            $predikat_display = 'Sangat Lancar (A+, A, A-)';
                        } elseif ($predikat_label === 'Lancar Terbata-Bata') {
                            $predikat_display = 'Lancar Terbata-Bata (B+, B, B-)';
                        } elseif ($predikat_label === 'Lancar dengan Bantuan') {
                            $predikat_display = 'Lancar dengan Bantuan (C+, C, C-)';
                        } elseif ($predikat_label === 'Tidak Lancar / Ulangi') {
                            $predikat_display = 'Tidak Lancar / Ulangi (D)';
                        } else {
                            $predikat_display = $predikat_label;
                        }
                        ?>
                        <span class="predikat-badge"><?php echo $predikat_display; ?></span>
                    </div>
                    
                    <div class="signature-section">
                        <div class="sig-box">
                            <p>Mengetahui,</p>
                            <p style="font-weight: 600; margin-top:-5px;">Kepala Madrasah</p>
                            <div class="sig-space"></div>
                            <p><strong><?php echo htmlspecialchars(!empty($cert['nama_kepsek']) ? $cert['nama_kepsek'] : "H. Mohammad Syafi'i, M.Pd"); ?></strong><br>NIP. <?php echo htmlspecialchars(!empty($cert['nip_kepsek']) ? $cert['nip_kepsek'] : "197805122005041001"); ?></p>
                        </div>
                        <div class="sig-box">
                            <p>Jakarta, <?php echo date('d M Y', strtotime($cert['tanggal_lulus'])); ?></p>
                            <p style="font-weight: 600; margin-top:-5px;">Guru Pembimbing Tahfidz</p>
                            <div class="sig-space"></div>
                            <p><strong><u><?php echo htmlspecialchars(!empty($cert['nama_guru_ttd']) ? $cert['nama_guru_ttd'] : $cert['nama_guru']); ?></u></strong><br>NIP. <?php echo htmlspecialchars(!empty($cert['nip_guru_ttd']) ? $cert['nip_guru_ttd'] : ($cert['nip_guru'] ?? '-')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Render interface normal
require_once 'header.php';

$error = '';
$success = '';

// Generate No Sertifikat Rekomendasi
$recom_cert_no = "MI-ADZ/" . date('Y') . "/" . date('m') . "/" . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

// 1. Ambil daftar siswa bimbingan yang sudah memenuhi target
$kelas_ids = [];
$siswa_list = [];
try {
    $stmt_kelas = $pdo->prepare("SELECT id FROM kelas WHERE wali_kelas_id = :guru_id");
    $stmt_kelas->execute(['guru_id' => $guru_id]);
    $kelas_ids = array_column($stmt_kelas->fetchAll(), 'id');
    
    $stmt_ta = $pdo->query("SELECT id FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
    $ta_aktif = $stmt_ta->fetch();
    
    if (!empty($kelas_ids) && $ta_aktif) {
        $in_clause = implode(',', array_fill(0, count($kelas_ids), '?'));
        // Ambil semua siswa bimbingan
        $stmt_all_siswa = $pdo->prepare("
            SELECT id, nama_lengkap 
            FROM siswa 
            WHERE kelas_id IN ($in_clause) AND status_aktif = 'aktif'
            ORDER BY nama_lengkap ASC
        ");
        $stmt_all_siswa->execute($kelas_ids);
        $temp_siswa_list = $stmt_all_siswa->fetchAll();
        
        foreach ($temp_siswa_list as $s) {
            // Ambil target siswa
            $stmt_target = $pdo->prepare("
                SELECT target_juz, target_surah 
                FROM target_hafalan 
                WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id
                LIMIT 1
            ");
            $stmt_target->execute([
                'siswa_id' => $s['id'],
                'ta_id' => $ta_aktif['id']
            ]);
            $target = $stmt_target->fetch();
            
            if ($target) {
                $target_juz = trim($target['target_juz']);
                $target_surah = trim($target['target_surah']);
                $achieved = false;
                
                if ($target_surah !== '') {
                    // Cek apakah ada setoran ziadah untuk surah tersebut
                    $stmt_check = $pdo->prepare("
                        SELECT COUNT(*) FROM setoran_tahfidz 
                        WHERE siswa_id = :siswa_id AND surah = :surah AND jenis = 'ziadah'
                    ");
                    $stmt_check->execute([
                        'siswa_id' => $s['id'],
                        'surah' => $target_surah
                    ]);
                    if ($stmt_check->fetchColumn() > 0) {
                        $achieved = true;
                    }
                } elseif ($target_juz !== '') {
                    // Cek setoran juz (jika Juz 30 cari An-Nas; jika Juz 29 cari Al-Mursalat; dll)
                    $last_surah = '';
                    if (strpos($target_juz, '30') !== false) {
                        $last_surah = 'An-Nas';
                    } elseif (strpos($target_juz, '29') !== false) {
                        $last_surah = 'Al-Mursalat';
                    }
                    
                    if ($last_surah !== '') {
                        $stmt_check = $pdo->prepare("
                            SELECT COUNT(*) FROM setoran_tahfidz 
                            WHERE siswa_id = :siswa_id AND surah = :surah AND jenis = 'ziadah'
                        ");
                        $stmt_check->execute([
                            'siswa_id' => $s['id'],
                            'surah' => $last_surah
                        ]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $achieved = true;
                        }
                    } else {
                        // Untuk target juz lain, anggap tercapai jika sudah ada setoran di sistem
                        $stmt_check = $pdo->prepare("
                            SELECT COUNT(*) FROM setoran_tahfidz 
                            WHERE siswa_id = :siswa_id
                        ");
                        $stmt_check->execute(['siswa_id' => $s['id']]);
                        if ($stmt_check->fetchColumn() > 0) {
                            $achieved = true;
                        }
                    }
                }
                
                if ($achieved) {
                    // Tambahkan info target untuk memudahkan prefill sertifikat jika diperlukan
                    $s['target_juz'] = $target_juz;
                    $s['target_surah'] = $target_surah;
                    $siswa_list[] = $s;
                }
            }
        }
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat daftar siswa yang memenuhi target.';
}

// 2. Simpan sertifikat baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sertifikat'])) {
    $siswa_id = intval($_POST['siswa_id'] ?? 0);
    $juz_dihafal = trim($_POST['juz_dihafal'] ?? '');
    $tanggal_lulus = $_POST['tanggal_lulus'] ?? date('Y-m-d');
    $no_sertifikat = trim($_POST['no_sertifikat'] ?? '');
    $predikat = trim($_POST['predikat'] ?? 'Sangat Lancar');
    $nama_kepsek = trim($_POST['nama_kepsek'] ?? '');
    $nip_kepsek = trim($_POST['nip_kepsek'] ?? '');
    $nama_guru_ttd = trim($_POST['nama_guru_ttd'] ?? '');
    $nip_guru_ttd = trim($_POST['nip_guru_ttd'] ?? '');
    
    if ($siswa_id <= 0) {
        $error = 'Silakan pilih siswa.';
    } elseif ($juz_dihafal === '') {
        $error = 'Silakan pilih Juz yang dihafal.';
    } elseif ($no_sertifikat === '') {
        $error = 'Nomor sertifikat tidak boleh kosong.';
    } else {
        try {
            // Cek nomor sertifikat duplikat
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM sertifikat WHERE no_sertifikat = :no");
            $stmt_check->execute(['no' => $no_sertifikat]);
            if ($stmt_check->fetchColumn() > 0) {
                $error = 'Nomor sertifikat tersebut sudah terdaftar di sistem. Silakan buat nomor unik lain.';
            } else {
                $stmt_in = $pdo->prepare("
                    INSERT INTO sertifikat (siswa_id, guru_id, juz_dihafal, tanggal_lulus, no_sertifikat, predikat, nama_kepsek, nip_kepsek, nama_guru_ttd, nip_guru_ttd)
                    VALUES (:siswa_id, :guru_id, :juz, :tgl, :no, :pred, :nama_kepsek, :nip_kepsek, :nama_guru_ttd, :nip_guru_ttd)
                ");
                $stmt_in->execute([
                    'siswa_id' => $siswa_id,
                    'guru_id' => $guru_id,
                    'juz' => $juz_dihafal,
                    'tgl' => $tanggal_lulus,
                    'no' => $no_sertifikat,
                    'pred' => $predikat,
                    'nama_kepsek' => $nama_kepsek,
                    'nip_kepsek' => $nip_kepsek,
                    'nama_guru_ttd' => $nama_guru_ttd,
                    'nip_guru_ttd' => $nip_guru_ttd
                ]);
                
                $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :id");
                $stmt_name->execute(['id' => $siswa_id]);
                $nama_siswa = $stmt_name->fetchColumn();
                
                logActivity($pdo, $user_id, "Menerbitkan sertifikat kelulusan $juz_dihafal untuk $nama_siswa dengan nomor $no_sertifikat");
                
                $success = "Sertifikat kelulusan juz berhasil diterbitkan untuk ananda $nama_siswa.";
                header("Location: sertifikat.php?success=" . urlencode($success));
                exit;
            }
        } catch (\PDOException $e) {
            $error = 'Gagal menerbitkan sertifikat: ' . $e->getMessage();
        }
    }
}

// 3. Hapus sertifikat
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        // Cek kepemilikan sertifikat (guru yang menerbitkannya)
        $stmt_check = $pdo->prepare("
            SELECT c.*, s.nama_lengkap AS nama_siswa 
            FROM sertifikat c
            JOIN siswa s ON c.siswa_id = s.id
            WHERE c.id = :id AND c.guru_id = :guru_id
        ");
        $stmt_check->execute(['id' => $delete_id, 'guru_id' => $guru_id]);
        $cert = $stmt_check->fetch();
        
        if ($cert) {
            $stmt_del = $pdo->prepare("DELETE FROM sertifikat WHERE id = :id");
            $stmt_del->execute(['id' => $delete_id]);
            
            logActivity($pdo, $user_id, "Membatalkan/menghapus sertifikat {$cert['juz_dihafal']} nomor {$cert['no_sertifikat']} siswa {$cert['nama_siswa']}");
            $success = "Sertifikat berhasil dihapus dari sistem.";
            header("Location: sertifikat.php?success=" . urlencode($success));
            exit;
        } else {
            $error = 'Data sertifikat tidak ditemukan atau Anda tidak berwenang menghapusnya.';
        }
    } catch (\PDOException $e) {
        $error = 'Gagal menghapus sertifikat: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// 4. Ambil daftar riwayat sertifikat yang diterbitkan guru ini
$sertifikat_list = [];
try {
    $stmt_list = $pdo->prepare("
        SELECT c.*, s.nama_lengkap AS nama_siswa, k.nama_kelas 
        FROM sertifikat c
        JOIN siswa s ON c.siswa_id = s.id
        JOIN kelas k ON s.kelas_id = k.id
        WHERE c.guru_id = :guru_id
        ORDER BY c.tanggal_lulus DESC, c.id DESC
    ");
    $stmt_list->execute(['guru_id' => $guru_id]);
    $sertifikat_list = $stmt_list->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat riwayat sertifikat.';
}
?>

<div style="margin-bottom: 25px;">
    <p style="font-size: 14px; color: var(--text-muted);">
        Terbitkan sertifikat penghargaan kelulusan Juz (misalnya Juz 30) untuk siswa yang telah menyelesaikan target hafalan juz tertentu.
    </p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1.2fr 2fr; gap: 25px; align-items: start; flex-wrap: wrap;">
    <!-- Form Terbitkan Sertifikat -->
    <div>
        <div class="admin-card-table" style="padding: 24px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <h2 style="margin-bottom: 20px; font-family: var(--font-heading); font-size: 16px; color: var(--primary-dark);">
                <i class="fa-solid fa-award" style="margin-right: 5px; color: var(--primary-color);"></i> Penerbitan Sertifikat Baru
            </h2>
            
            <form action="sertifikat.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="siswa_id">Siswa Penerima</label>
                    <div style="margin-bottom: 8px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass input-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                        <input type="text" id="search_siswa_cert" onkeyup="filterSiswaCert()" placeholder="Ketik nama siswa..." class="form-control" style="font-size: 13px; padding: 8px 12px 8px 35px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;">
                    </div>
                    <select name="siswa_id" id="siswa_select_cert" class="form-control form-control-select" style="padding-left: 16px;" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($siswa_list as $siswa): ?>
                            <option value="<?php echo $siswa['id']; ?>">
                                <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="juz_dihafal">Kelulusan Hafalan</label>
                    <select name="juz_dihafal" class="form-control form-control-select" style="padding-left: 16px;" required>
                        <option value="">-- Pilih Juz --</option>
                        <option value="Juz 30">Juz 30 (Juz Amma)</option>
                        <option value="Juz 29">Juz 29</option>
                        <option value="Juz 28">Juz 28</option>
                        <?php for ($i = 1; $i <= 27; $i++): ?>
                            <option value="Juz <?php echo $i; ?>">Juz <?php echo $i; ?></option>
                        <?php endfor; ?>
                        <option value="30 Juz">Lulus 30 Juz Al-Qur'an</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="no_sertifikat">Nomor Sertifikat</label>
                    <input type="text" name="no_sertifikat" class="form-control" style="padding-left: 16px;" value="<?php echo $recom_cert_no; ?>" required>
                    <span style="font-size: 10px; color: var(--text-muted); display: block; margin-top: 4px;">*Auto-generated. Anda dapat mengubahnya secara manual.</span>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 10px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" for="predikat">Predikat Kelulusan</label>
                        <select name="predikat" class="form-control form-control-select" style="padding-left: 16px;" required>
                            <option value="Sangat Lancar">Sangat Lancar</option>
                            <option value="Lancar Terbata-Bata">Lancar Terbata-Bata</option>
                            <option value="Lancar dengan Bantuan">Lancar dengan Bantuan</option>
                            <option value="Tidak Lancar / Ulangi">Tidak Lancar / Ulangi</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="tanggal_lulus">Tanggal Ujian / Lulus</label>
                        <input type="date" id="tanggal_lulus" name="tanggal_lulus" class="form-control" style="padding-left: 16px;" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div style="border-top: 1px dashed rgba(13, 92, 52, 0.15); padding-top: 15px; margin-top: 15px;">
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" for="nama_kepsek">Nama Kepala Madrasah (TTD)</label>
                        <input type="text" name="nama_kepsek" class="form-control" style="padding-left: 16px;" value="H. Mohammad Syafi&#039;i, M.Pd" autocomplete="off" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" for="nip_kepsek">NIP Kepala Madrasah</label>
                        <input type="text" name="nip_kepsek" class="form-control" style="padding-left: 16px;" value="197805122005041001" autocomplete="off" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" for="nama_guru_ttd">Nama Guru Tahfidz (TTD)</label>
                        <input type="text" name="nama_guru_ttd" class="form-control" style="padding-left: 16px;" value="<?php echo htmlspecialchars($guru_profile['nama_lengkap']); ?>" autocomplete="off" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 15px;">
                        <label class="form-label" for="nip_guru_ttd">NIP Guru Tahfidz</label>
                        <input type="text" name="nip_guru_ttd" class="form-control" style="padding-left: 16px;" value="<?php echo htmlspecialchars($guru_profile['nip'] ?? ''); ?>" autocomplete="off">
                    </div>
                </div>
                
                <div style="display: flex; justify-content: center; margin-top: 15px;">
                    <button type="submit" name="submit_sertifikat" class="btn btn-primary" style="width: auto; padding: 8px 16px; font-size: 13px;">
                        <i class="fa-solid fa-award" style="margin-right: 5px;"></i> Terbitkan Sertifikat
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Riwayat Sertifikat Diterbitkan -->
    <div>
        <div class="admin-card-table">
            <div class="admin-card-header">
                <h2>Sertifikat yang Telah Diterbitkan</h2>
            </div>
            
            <div class="table-responsive">
                <?php if (empty($sertifikat_list)): ?>
                    <div style="padding: 50px; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-award" style="font-size: 30px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
                        Belum ada sertifikat kelulusan yang diterbitkan.
                    </div>
                <?php else: ?>
                    <table class="table-admin" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>No. Sertifikat</th>
                                <th>Nama Siswa</th>
                                <th style="text-align: center;">Kelulusan</th>
                                <th style="text-align: center;">Predikat</th>
                                <th>Tanggal</th>
                                <th style="text-align: center; width: 110px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sertifikat_list as $row): ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 12px; font-weight: bold;"><?php echo htmlspecialchars($row['no_sertifikat']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nama_siswa']); ?></strong>
                                        <div style="font-size: 10px; color: var(--text-muted);"><?php echo htmlspecialchars($row['nama_kelas']); ?></div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="badge-status" style="background-color: #faf5ff; color: #8b5cf6; border: 1px solid #e9d5ff; font-weight: 700;">
                                            <?php echo htmlspecialchars($row['juz_dihafal']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center; font-weight: 600; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($row['predikat']); ?>
                                    </td>
                                    <td><?php echo date('d-m-Y', strtotime($row['tanggal_lulus'])); ?></td>
                                    <td style="text-align: center;">
                                        <div style="display: flex; gap: 6px; justify-content: center;">
                                            <a href="sertifikat.php?print_cert_id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" title="Cetak Sertifikat" style="padding: 5px 6px; width: auto; color: #ca8a04; background-color: #fefce8; border-color: rgba(202, 138, 4, 0.15);">
                                                <i class="fa-solid fa-print"></i>
                                            </a>
                                            <a href="sertifikat.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Apakah Anda yakin ingin membatalkan/menghapus sertifikat ini?');" title="Hapus" style="padding: 5px 6px; width: auto; color: var(--error-color); background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.15);">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</main>
</div>
</div>
<script>
function initDropdownDatePicker(input, minYear, maxYear) {
    if (!input) return;
    
    // Create dropdowns container
    const container = document.createElement('div');
    container.className = 'date-dropdown-group';
    container.style.display = 'grid';
    container.style.gridTemplateColumns = '1fr 1.3fr 1fr';
    container.style.gap = '8px';
    container.style.marginTop = '4px';
    
    // Create selects
    const selectDay = document.createElement('select');
    selectDay.className = 'form-control form-control-select';
    selectDay.style.padding = '8px';
    selectDay.innerHTML = '<option value="">Hari</option>';
    for (let d = 1; d <= 31; d++) {
        const val = String(d).padStart(2, '0');
        selectDay.innerHTML += `<option value="${val}">${d}</option>`;
    }
    
    const selectMonth = document.createElement('select');
    selectMonth.className = 'form-control form-control-select';
    selectMonth.style.padding = '8px';
    selectMonth.innerHTML = '<option value="">Bulan</option>';
    const months = [
        {val: '01', name: 'Januari'}, {val: '02', name: 'Februari'}, {val: '03', name: 'Maret'},
        {val: '04', name: 'April'}, {val: '05', name: 'Mei'}, {val: '06', name: 'Juni'},
        {val: '07', name: 'Juli'}, {val: '08', name: 'Agustus'}, {val: '09', name: 'September'},
        {val: '10', name: 'Oktober'}, {val: '11', name: 'November'}, {val: '12', name: 'Desember'}
    ];
    months.forEach(m => {
        selectMonth.innerHTML += `<option value="${m.val}">${m.name}</option>`;
    });
    
    const selectYear = document.createElement('select');
    selectYear.className = 'form-control form-control-select';
    selectYear.style.padding = '8px';
    selectYear.innerHTML = '<option value="">Tahun</option>';
    for (let y = maxYear; y >= minYear; y--) {
        selectYear.innerHTML += `<option value="${y}">${y}</option>`;
    }
    
    container.appendChild(selectDay);
    container.appendChild(selectMonth);
    container.appendChild(selectYear);
    
    // Insert container after input
    input.parentNode.insertBefore(container, input.nextSibling);
    // Hide the original date input
    input.type = 'hidden';
    
    // Update original input value from dropdowns
    function updateInputValue() {
        const d = selectDay.value;
        const m = selectMonth.value;
        const y = selectYear.value;
        if (d && m && y) {
            input.value = `${y}-${m}-${d}`;
        } else {
            input.value = '';
        }
        // Trigger change event on input
        const event = new Event('change', { bubbles: true });
        input.dispatchEvent(event);
    }
    
    selectDay.addEventListener('change', updateInputValue);
    selectMonth.addEventListener('change', updateInputValue);
    selectYear.addEventListener('change', updateInputValue);
    
    // Parse value YYYY-MM-DD and set dropdowns
    function setDropdownsFromValue(val) {
        if (val && val.match(/^\d{4}-\d{2}-\d{2}$/)) {
            const parts = val.split('-');
            selectYear.value = parts[0];
            selectMonth.value = parts[1];
            selectDay.value = parts[2];
        } else {
            selectYear.value = '';
            selectMonth.value = '';
            selectDay.value = '';
        }
    }
    
    // Initial set
    setDropdownsFromValue(input.value);
    
    // Intercept programmatical value assignment
    const descriptor = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    Object.defineProperty(input, 'value', {
        get: function() {
            return descriptor.get.call(this);
        },
        set: function(val) {
            descriptor.set.call(this, val);
            setDropdownsFromValue(val);
        }
    });
}

// Instantiate for tanggal_lulus input
const currentYear = new Date().getFullYear();
initDropdownDatePicker(document.getElementById('tanggal_lulus'), currentYear - 5, currentYear + 5);
// Script Pencarian Siswa Sertifikat dan Dropdown robust
let allCertSiswaOptions = [];
document.addEventListener("DOMContentLoaded", function() {
    var select = document.getElementById('siswa_select_cert');
    if (select) {
        var options = select.options;
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            if (opt.value !== "") {
                allCertSiswaOptions.push({
                    value: opt.value,
                    text: opt.text
                });
            }
        }
    }
    filterSiswaCert();
});

function filterSiswaCert() {
    var searchVal = document.getElementById('search_siswa_cert').value.toLowerCase();
    var select = document.getElementById('siswa_select_cert');
    if (!select) return;
    var prevVal = select.value;
    select.innerHTML = '<option value="">-- Pilih Siswa --</option>';
    for (var i = 0; i < allCertSiswaOptions.length; i++) {
        var optData = allCertSiswaOptions[i];
        if (optData.text.toLowerCase().includes(searchVal)) {
            var opt = document.createElement('option');
            opt.value = optData.value;
            opt.text = optData.text;
            if (optData.value === prevVal) {
                opt.selected = true;
            }
            select.appendChild(opt);
        }
    }
}
</script>
</body>
</html>
