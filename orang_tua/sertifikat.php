<?php
// orang_tua/sertifikat.php
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
    // Tampilan cetak sertifikat untuk Orang Tua
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'orang_tua') {
        die("Akses ditolak.");
    }
    
    // Verifikasi bahwa sertifikat ini memang milik anak dari orang tua yang login
    try {
        // Ambil profil orang tua
        $stmt_ortu = $pdo->prepare("SELECT id FROM orang_tua WHERE user_id = :user_id");
        $stmt_ortu->execute(['user_id' => $_SESSION['user_id']]);
        $ortu_id = $stmt_ortu->fetchColumn();
        
        $stmt_cert = $pdo->prepare("
            SELECT c.*, s.nama_lengkap AS nama_siswa, s.nisn, gt.nama_lengkap AS nama_guru, gt.nip AS nip_guru 
            FROM sertifikat c
            JOIN siswa s ON c.siswa_id = s.id
            JOIN guru_tahfidz gt ON c.guru_id = gt.id
            WHERE c.id = :id AND s.orang_tua_id = :ortu_id
        ");
        $stmt_cert->execute([
            'id' => $print_cert_id,
            'ortu_id' => $ortu_id
        ]);
        $cert = $stmt_cert->fetch();
        
        if (!$cert) {
            die("Sertifikat tidak ditemukan atau bukan milik anak Anda.");
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
            .border-outer {
                width: 100%;
                height: 100%;
                border: 15px solid #0d5c34;
                box-sizing: border-box;
                position: relative;
                padding: 15px;
            }
            .border-inner {
                width: 100%;
                height: 100%;
                border: 4px double #ffd700;
                box-sizing: border-box;
                position: relative;
                padding: 10px 45px;
            }
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
                color: #ccab00;
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

// Render normal interface
require_once 'header.php';

$error = '';
$sertifikat_list = [];

if ($anak_aktif) {
    try {
        $stmt_list = $pdo->prepare("
            SELECT c.*, gt.nama_lengkap AS nama_guru 
            FROM sertifikat c
            JOIN guru_tahfidz gt ON c.guru_id = gt.id
            WHERE c.siswa_id = :siswa_id
            ORDER BY c.tanggal_lulus DESC, c.id DESC
        ");
        $stmt_list->execute(['siswa_id' => $anak_aktif['id']]);
        $sertifikat_list = $stmt_list->fetchAll();
    } catch (\PDOException $e) {
        $error = 'Gagal memuat daftar sertifikat kelulusan.';
    }
}
?>

<?php if (empty($daftar_anak)): ?>
    <div class="card" style="margin-top: 20px; box-shadow: none; border: 1.5px solid rgba(239, 68, 68, 0.2); background-color: rgba(239, 68, 68, 0.03); width: 100%; max-width: 100%;">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 30px; color: var(--error-color);"></i>
            <div>
                <h3 style="color: #991b1b; font-family: var(--font-heading); margin-bottom: 8px;">Data Anak Belum Terhubung</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Akun Anda belum terhubung dengan data siswa mana pun.
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Judul -->
    <div style="margin-bottom: 25px;">
        <h2 style="font-size: 20px; font-family: var(--font-heading); color: var(--primary-dark); margin-bottom: 6px;">
            Sertifikat Kelulusan Tahfidz Anak
        </h2>
        <p style="font-size: 14px; color: var(--text-muted);">
            Daftar penghargaan sertifikat kelulusan kelancaran hafalan Juz yang telah diraih oleh ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%;">
        <div class="table-responsive">
            <?php if (empty($sertifikat_list)): ?>
                <div style="padding: 50px 24px; text-align: center; color: var(--text-muted);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px; color: #94a3b8; font-size: 24px;">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <p style="font-weight: 500; font-size: 15px; color: var(--text-main);">Belum memiliki sertifikat kelulusan</p>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                        Sertifikat akan tampil di sini setelah diterbitkan oleh guru pembimbing tahfidz siswa.
                    </p>
                </div>
            <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th>No. Sertifikat</th>
                            <th style="text-align: center;">Juz Kelulusan</th>
                            <th style="text-align: center;">Predikat Kelancaran</th>
                            <th>Tanggal Lulus</th>
                            <th>Guru Penguji</th>
                            <th style="text-align: center; width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($sertifikat_list as $row): 
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                                <td style="font-family: monospace; font-weight: bold;"><?php echo htmlspecialchars($row['no_sertifikat']); ?></td>
                                <td style="text-align: center;">
                                    <span class="badge-status" style="background-color: #faf5ff; color: #8b5cf6; border: 1px solid #e9d5ff; font-weight: 700;">
                                        <?php echo htmlspecialchars($row['juz_dihafal']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: var(--primary-color);">
                                    <?php echo htmlspecialchars($row['predikat']); ?>
                                </td>
                                <td><?php echo date('d-m-Y', strtotime($row['tanggal_lulus'])); ?></td>
                                <td><?php echo htmlspecialchars($row['nama_guru']); ?></td>
                                <td style="text-align: center;">
                                    <a href="sertifikat.php?print_cert_id=<?php echo $row['id']; ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 5px; width: auto; color: #ca8a04; background-color: #fefce8; border-color: rgba(202, 138, 4, 0.15);">
                                        <i class="fa-solid fa-print"></i> Cetak / Unduh
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
