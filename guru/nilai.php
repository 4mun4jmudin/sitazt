<?php
// guru/nilai.php
require_once '../config/database.php';

// Ambil tahun ajaran aktif
$ta_aktif = $pdo->query("SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1")->fetch();

// Cek apakah mode print diaktifkan
$print_siswa_id = intval($_GET['print_siswa_id'] ?? 0);
$periode_filter = $_GET['periode_filter'] ?? 'semua';
$filter_sql = "";

if ($periode_filter === 'semua') {
    if ($ta_aktif) {
        $years = explode('/', $ta_aktif['tahun']);
        if (count($years) === 2) {
            if ($ta_aktif['semester'] === 'Ganjil') {
                $start_date = trim($years[0]) . "-07-01";
                $end_date = trim($years[0]) . "-12-31";
            } else {
                $start_date = trim($years[1]) . "-01-01";
                $end_date = trim($years[1]) . "-06-30";
            }
            $filter_sql = " AND tanggal BETWEEN '$start_date' AND '$end_date'";
        }
    }
} elseif ($periode_filter === 'minggu') {
    $filter_sql = " AND YEARWEEK(tanggal, 1) = YEARWEEK(CURRENT_DATE(), 1)";
} elseif ($periode_filter === 'bulan') {
    $filter_sql = " AND YEAR(tanggal) = YEAR(CURRENT_DATE()) AND MONTH(tanggal) = MONTH(CURRENT_DATE())";
} elseif ($periode_filter === 'tengah_semester') {
    $filter_sql = " AND tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)";
} elseif ($periode_filter === 'tiga_semester') {
    $filter_sql = " AND tanggal >= DATE_SUB(CURRENT_DATE(), INTERVAL 540 DAY)";
}

if ($print_siswa_id > 0) {
    // Jalankan cetak rapor siswa individu
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru_tahfidz') {
        die("Akses ditolak.");
    }
    
    // Ambil info guru
    try {
        $stmt_guru = $pdo->prepare("SELECT * FROM guru_tahfidz WHERE user_id = :user_id");
        $stmt_guru->execute(['user_id' => $_SESSION['user_id']]);
        $guru = $stmt_guru->fetch();
        
        // Ambil info siswa
        $stmt_siswa = $pdo->prepare("
            SELECT s.*, k.nama_kelas 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.id = :id
        ");
        $stmt_siswa->execute(['id' => $print_siswa_id]);
        $siswa = $stmt_siswa->fetch();
        
        if (!$siswa) {
            die("Siswa tidak ditemukan.");
        }
        
        // Ambil setoran hafalan dengan filter
        $query_setoran = "SELECT * FROM setoran_tahfidz WHERE siswa_id = :siswa_id" . $filter_sql . " ORDER BY tanggal ASC, id ASC";
        $stmt_setoran = $pdo->prepare($query_setoran);
        $stmt_setoran->execute(['siswa_id' => $print_siswa_id]);
        $setoran_list = $stmt_setoran->fetchAll();
        
        // Ta aktif sudah dimuat di bagian atas halaman
        
    } catch (\PDOException $e) {
        die("Error database: " . $e->getMessage());
    }
    
    // Hitung rata-rata nilai
    $total_score = 0;
    $score_count = 0;
    $avg_grade_letter = 'Belum Dinilai';
    $avg_score = 0;
    
    foreach ($setoran_list as $s) {
        $nilai = trim($s['nilai']);
        if ($s['nilai_angka'] !== null) {
            $total_score += intval($s['nilai_angka']);
            $score_count++;
        } else {
            // Fallback
            if (strcasecmp($nilai, 'Sangat Lancar') === 0) $total_score += 90;
            elseif (strcasecmp($nilai, 'Lancar Terbata-Bata') === 0) $total_score += 75;
            elseif (strcasecmp($nilai, 'Lancar dengan Bantuan') === 0) $total_score += 65;
            else $total_score += 50;
            $score_count++;
        }
    }
    
    if ($score_count > 0) {
        $avg_score = $total_score / $score_count;
        if ($avg_score >= 85) $avg_predikat = 'Sangat Lancar';
        elseif ($avg_score >= 70) $avg_predikat = 'Lancar Terbata-Bata';
        elseif ($avg_score >= 60) $avg_predikat = 'Lancar dengan Bantuan';
        else $avg_predikat = 'Tidak Lancar / Ulangi';
        
        $avg_grade_letter = formatGrade($avg_predikat, $avg_score) . " (" . number_format($avg_score, 1) . ")";
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Rapor Tahfidz - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?></title>
        <style>
            body {
                font-family: 'Times New Roman', Times, serif;
                margin: 40px;
                background-color: #ffffff;
                color: #000000;
                font-size: 14px;
            }
            .kop-surat {
                display: flex;
                align-items: center;
                justify-content: center;
                border-bottom: 3px double #000000;
                padding-bottom: 15px;
                margin-bottom: 25px;
                gap: 20px;
            }
            .kop-logo img {
                height: 85px;
            }
            .kop-text {
                text-align: center;
                flex-grow: 1;
            }
            .kop-text h2 {
                margin: 0;
                font-size: 18px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
                text-transform: uppercase;
            }
            .kop-text h1 {
                margin: 3px 0;
                font-size: 22px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
                text-transform: uppercase;
            }
            .kop-text h3 {
                margin: 2px 0;
                font-size: 14px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
                text-transform: uppercase;
            }
            .kop-text p {
                margin: 2px 0;
                font-size: 13px;
                font-weight: bold;
                color: #000000;
                font-family: 'Times New Roman', Times, serif;
            }
            .kop-text p.email {
                margin-top: 5px;
                font-size: 13px;
            }
            .kop-text p.email a {
                color: #0000ff;
                text-decoration: underline;
            }
            .title {
                text-align: center;
                font-size: 18px;
                font-weight: bold;
                text-transform: uppercase;
                margin-bottom: 25px;
                text-decoration: underline;
            }
            .biodata-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }
            .biodata-table td {
                padding: 4px 8px;
                vertical-align: top;
            }
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
                margin-bottom: 25px;
            }
            .data-table th, .data-table td {
                border: 1px solid #000000;
                padding: 8px 12px;
                text-align: left;
            }
            .data-table th {
                background-color: #f2f2f2;
                text-align: center;
                font-weight: bold;
            }
            .summary-box {
                border: 1px solid #000000;
                padding: 15px;
                margin-bottom: 30px;
                background-color: #fafafa;
            }
            .summary-box p {
                margin: 5px 0;
            }
            .signature-area {
                margin-top: 50px;
                width: 100%;
                display: flex;
                justify-content: space-between;
            }
            .signature-box {
                text-align: center;
                width: 200px;
            }
            .signature-space {
                height: 75px;
            }
            @media print {
                body { margin: 20px; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="no-print" style="background-color: #f1f5f9; padding: 15px; text-align: center; border-bottom: 1px solid #e2e8f0; margin: -40px -40px 30px -40px;">
            <button onclick="window.print();" style="padding: 10px 20px; font-weight: bold; background-color: #0d5c34; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: sans-serif;">
                <i class="fa-solid fa-print"></i> CETAK DOKUMEN RAPOR
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; font-weight: bold; background-color: #64748b; color: white; border: none; border-radius: 5px; cursor: pointer; font-family: sans-serif; margin-left: 10px;">
                TUTUP
            </button>
        </div>
        
        <div class="kop-surat">
            <div class="kop-logo">
                <img src="../assets/images/logo.jpg" alt="Logo">
            </div>
            <div class="kop-text">
                <h2>YAYASAN AL-BAROROH BLUBUR LIMBANGAN</h2>
                <h1>MADRASAH IBTIDAIYAH (MI) AL-ADZKIYA</h1>
                <h3>STATUS <u>TERAKREDITASI : B</u></h3>
                <p>Kp. Cicadas RT.02 RW.08 Desa Pasirwaru Kec. Bl. Limbangan Garut 44186</p>
                <p class="email">email : <a href="mailto:mi.aladzkiya@yahoo.com">mi.aladzkiya@yahoo.com</a></p>
            </div>
        </div>
        
        <div class="title">RAPOR PERKEMBANGAN TAHFIDZ AL-QUR'AN</div>
        
        <table class="biodata-table">
            <tr>
                <td style="width: 15%;">Nama Siswa</td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%;"><strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong></td>
                <td style="width: 15%;">Kelas</td>
                <td style="width: 2%;">:</td>
                <td style="width: 33%;"><?php echo htmlspecialchars($siswa['nama_kelas']); ?></td>
            </tr>
            <tr>
                <td>NISN</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($siswa['nisn']); ?></td>
                <td>Semester</td>
                <td>:</td>
                <td><?php echo htmlspecialchars($ta_aktif ? $ta_aktif['semester'] . " / " . $ta_aktif['tahun'] : '-'); ?></td>
            </tr>
        </table>
        
        <h3>A. Riwayat Setoran Hafalan</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">No</th>
                    <th style="width: 15%;">Tanggal</th>
                    <th style="width: 15%; text-align: center;">Kategori</th>
                    <th style="width: 30%;">Surah</th>
                    <th style="width: 15%; text-align: center;">Ayat</th>
                    <th style="width: 10%; text-align: center;">Skor Angka</th>
                    <th style="width: 15%; text-align: center;">Predikat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($setoran_list)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; font-style: italic;">Belum ada riwayat setoran hafalan.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($setoran_list as $row): 
                    ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $no++; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                            <td style="text-align: center;"><?php echo $row['jenis'] === 'ziadah' ? 'Ziadah' : 'Murajaah'; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['surah']); ?></strong></td>
                            <td style="text-align: center;"><?php echo $row['ayat_mulai']; ?> - <?php echo $row['ayat_selesai']; ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($row['nilai_angka'] ?? '-'); ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars(formatGrade($row['nilai'], $row['nilai_angka'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h3>B. Ringkasan & Hasil Evaluasi</h3>
        <div class="summary-box">
            <p><strong>Total Setoran Hafalan:</strong> <?php echo count($setoran_list); ?> kali</p>
            <p><strong>Rata-rata Nilai Angka:</strong> <span style="font-weight: bold; font-size: 16px;"><?php echo $score_count > 0 ? number_format($avg_score, 1) : '-'; ?></span></p>
            <p><strong>Predikat Rata-rata Kelancaran:</strong> <span style="font-weight: bold; color: #0d5c34; font-size: 16px;"><?php echo $avg_grade_letter; ?></span></p>
            <p style="margin-top: 10px;"><em>*Keterangan Predikat Kelancaran: Sangat Lancar (A+ dari nilai 95-100, A dari nilai 90-94, A- dari nilai 85-89), Lancar Terbata-Bata (B+ dari nilai 80-84, B dari nilai 75-79, B- 70-74), Lancar dengan Bantuan (C+ dari nilai 70-75, C 65-69 , C-60-64 ), Tidak Lancar / Ulangi (D dari 1-50).</em></p>
        </div>
        
        <div class="signature-area">
            <div class="signature-box">
                <p>Orang Tua / Wali Murid</p>
                <div class="signature-space"></div>
                <p>........................................</p>
            </div>
            <div class="signature-box">
                <p>Jakarta, <?php echo date('d F Y'); ?></p>
                <p>Guru Pembimbing Tahfidz</p>
                <div class="signature-space"></div>
                <p><strong><u><?php echo htmlspecialchars($guru['nama_lengkap']); ?></u></strong><br>NIP: <?php echo htmlspecialchars($guru['nip'] ?? '-'); ?></p>
            </div>
        </div>
        
        <script>
            // Auto trigger print window on load
            window.addEventListener('load', function() {
                // window.print(); // uncomment if user wants immediate pop up
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Render interface normal
require_once 'header.php';

$error = '';
$siswa_grades = [];

try {
    // 1. Ambil daftar kelas bimbingan guru
    $stmt_kelas = $pdo->prepare("SELECT id FROM kelas WHERE wali_kelas_id = :guru_id");
    $stmt_kelas->execute(['guru_id' => $guru_id]);
    $kelas_ids = array_column($stmt_kelas->fetchAll(), 'id');
    
    if (!empty($kelas_ids)) {
        $in_clause = implode(',', array_fill(0, count($kelas_ids), '?'));
        // Ambil data siswa
        $stmt_siswa = $pdo->prepare("
            SELECT s.id, s.nisn, s.nama_lengkap, k.nama_kelas 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id
            WHERE s.kelas_id IN ($in_clause) AND s.status_aktif = 'aktif'
            ORDER BY s.nama_lengkap ASC
        ");
        $stmt_siswa->execute($kelas_ids);
        $siswa_list = $stmt_siswa->fetchAll();
        
        // Loop siswa untuk menghitung poin nilai
        foreach ($siswa_list as $siswa) {
            $query_setoran = "SELECT nilai, nilai_angka FROM setoran_tahfidz WHERE siswa_id = :siswa_id" . $filter_sql;
            $stmt_setoran = $pdo->prepare($query_setoran);
            $stmt_setoran->execute(['siswa_id' => $siswa['id']]);
            $setoran = $stmt_setoran->fetchAll();
            
            $total_setoran = count($setoran);
            $total_score = 0;
            $score_count = 0;
            $predikat = 'Belum Dinilai';
            
            $grade_counts = [
                'Sangat Lancar' => 0,
                'Lancar Terbata-Bata' => 0,
                'Lancar dengan Bantuan' => 0,
                'Tidak Lancar / Ulangi' => 0
            ];
            
            foreach ($setoran as $s) {
                $nilai = trim($s['nilai']);
                if (strcasecmp($nilai, 'Sangat Lancar') === 0) {
                    $grade_counts['Sangat Lancar']++;
                } elseif (strcasecmp($nilai, 'Lancar Terbata-Bata') === 0) {
                    $grade_counts['Lancar Terbata-Bata']++;
                } elseif (strcasecmp($nilai, 'Lancar dengan Bantuan') === 0) {
                    $grade_counts['Lancar dengan Bantuan']++;
                } else {
                    $grade_counts['Tidak Lancar / Ulangi']++;
                }
                
                if ($s['nilai_angka'] !== null) {
                    $total_score += intval($s['nilai_angka']);
                    $score_count++;
                } else {
                    // Fallback
                    if (strcasecmp($nilai, 'Sangat Lancar') === 0) $total_score += 90;
                    elseif (strcasecmp($nilai, 'Lancar Terbata-Bata') === 0) $total_score += 75;
                    elseif (strcasecmp($nilai, 'Lancar dengan Bantuan') === 0) $total_score += 65;
                    else $total_score += 50;
                    $score_count++;
                }
            }
            
            $avg_score = $score_count > 0 ? ($total_score / $score_count) : 0;
            
            if ($score_count > 0) {
                if ($avg_score >= 85) $predikat = 'Sangat Lancar';
                elseif ($avg_score >= 70) $predikat = 'Lancar Terbata-Bata';
                elseif ($avg_score >= 60) $predikat = 'Lancar dengan Bantuan';
                else $predikat = 'Tidak Lancar / Ulangi';
            }
            
            $siswa_grades[] = [
                'id' => $siswa['id'],
                'nisn' => $siswa['nisn'],
                'nama_lengkap' => $siswa['nama_lengkap'],
                'kelas' => $siswa['nama_kelas'],
                'total_setoran' => $total_setoran,
                'avg_score' => $avg_score,
                'predikat' => $predikat,
                'breakdown' => $grade_counts
            ];
        }
    }
} catch (\PDOException $e) {
    $error = 'Gagal menghitung rekapitulasi nilai: ' . $e->getMessage();
}
?>

<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <p style="font-size: 14px; color: var(--text-muted); margin: 0;">
        Rekapitulasi capaian predikat kelancaran hafalan seluruh siswa halaqah bimbingan Anda. Unduh atau cetak laporan nilai per siswa.
    </p>
    <div>
        <label style="font-size: 12px; font-weight: bold; color: var(--text-muted); margin-right: 8px;">Periode Laporan:</label>
        <select onchange="location.href='nilai.php?periode_filter=' + this.value" class="form-control" style="width: 220px; display: inline-block; font-size: 13px; padding: 6px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; height: auto;">
            <option value="semua" <?php echo ($periode_filter === 'semua') ? 'selected' : ''; ?>>Semua Semester Aktif</option>
            <option value="minggu" <?php echo ($periode_filter === 'minggu') ? 'selected' : ''; ?>>Per Minggu (Minggu Ini)</option>
            <option value="bulan" <?php echo ($periode_filter === 'bulan') ? 'selected' : ''; ?>>Per Bulan (Bulan Ini)</option>
            <option value="tengah_semester" <?php echo ($periode_filter === 'tengah_semester') ? 'selected' : ''; ?>>Per Tengah Semester (90 Hari)</option>
            <option value="tiga_semester" <?php echo ($periode_filter === 'tiga_semester') ? 'selected' : ''; ?>>Per 3 Semester (1.5 Tahun)</option>
        </select>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div class="admin-card-table">
    <div class="admin-card-header">
        <h2>Laporan Nilai Kelancaran Setoran Siswa</h2>
    </div>
    
    <div style="padding: 15px 20px 0 20px;">
        <div style="position: relative; max-width: 350px;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
            <input type="text" id="search_nilai_input" onkeyup="filterNilaiTable()" placeholder="Cari nama siswa..." class="form-control" style="font-size: 13px; padding: 8px 12px 8px 35px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;">
        </div>
    </div>
    
    <div class="table-responsive">
        <?php if (empty($siswa_grades)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                Belum ada data siswa bimbingan atau data setoran untuk dinilai.
            </div>
        <?php else: ?>
            <table class="table-admin">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th style="text-align: center;">Total Setoran</th>
                        <th style="text-align: center; color: #16a34a;">Sangat Lancar</th>
                        <th style="text-align: center; color: #0d5c34;">Lancar Terbata-Bata</th>
                        <th style="text-align: center; color: #ca8a04;">Lancar dengan Bantuan</th>
                        <th style="text-align: center; color: #dc2626;">Tidak Lancar / Ulangi</th>
                        <th style="text-align: center;">Rata-rata Skor</th>
                        <th style="text-align: center;">Predikat Kelancaran</th>
                        <th style="text-align: center; width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($siswa_grades as $row): 
                        $pred_style = '';
                        if (strpos($row['predikat'], 'Sangat Lancar') !== false) {
                            $pred_style = 'color: #16a34a; font-weight: 700;';
                        } elseif (strpos($row['predikat'], 'Lancar Terbata-Bata') !== false) {
                            $pred_style = 'color: #0d5c34; font-weight: 700;';
                        } elseif (strpos($row['predikat'], 'Lancar dengan Bantuan') !== false) {
                            $pred_style = 'color: #ca8a04; font-weight: 700;';
                        } elseif (strpos($row['predikat'], 'Tidak Lancar / Ulangi') !== false) {
                            $pred_style = 'color: #dc2626; font-weight: 700;';
                        } else {
                            $pred_style = 'color: var(--text-muted);';
                        }
                    ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                            <td>
                                <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong>
                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">NISN: <?php echo htmlspecialchars($row['nisn']); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['kelas']); ?></td>
                            <td style="text-align: center; font-weight: bold;"><?php echo $row['total_setoran']; ?></td>
                            
                            <td style="text-align: center; color: #16a34a; font-weight: 600;"><?php echo $row['breakdown']['Sangat Lancar']; ?></td>
                            <td style="text-align: center; color: #0d5c34; font-weight: 600;"><?php echo $row['breakdown']['Lancar Terbata-Bata']; ?></td>
                            <td style="text-align: center; color: #ca8a04; font-weight: 600;"><?php echo $row['breakdown']['Lancar dengan Bantuan']; ?></td>
                            <td style="text-align: center; color: #dc2626; font-weight: 600;"><?php echo $row['breakdown']['Tidak Lancar / Ulangi']; ?></td>
                            
                            <td style="text-align: center; font-weight: bold; color: var(--primary-color);">
                                <?php echo $row['total_setoran'] > 0 ? number_format($row['avg_score'], 1) : '-'; ?>
                            </td>
                            <td style="text-align: center; <?php echo $pred_style; ?>">
                                <?php echo htmlspecialchars($row['predikat'] !== 'Belum Dinilai' ? formatGrade($row['predikat'], $row['avg_score']) : $row['predikat']); ?>
                            </td>
                            
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center;">
                                    <a href="progres.php?siswa_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" style="width: auto; padding: 5px 10px;">
                                        <i class="fa-solid fa-magnifying-glass"></i> Rincian
                                    </a>
                                    <a href="nilai.php?print_siswa_id=<?php echo $row['id']; ?>&periode_filter=<?php echo $periode_filter; ?>" target="_blank" class="btn btn-secondary btn-sm" style="width: auto; padding: 5px 10px; color: var(--primary-color); background-color: #f0fdf4; border-color: rgba(13, 92, 52, 0.15);">
                                        <i class="fa-solid fa-print"></i> Cetak Rapor
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

</main>
</div>
</div>
<script>
function filterNilaiTable() {
    var searchVal = document.getElementById('search_nilai_input').value.toLowerCase();
    var table = document.querySelector('.table-admin');
    if (!table) return;
    var rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(function(row) {
        // Name is in second column (index 1)
        var nameCell = row.cells[1];
        if (nameCell) {
            var name = nameCell.querySelector('strong') ? nameCell.querySelector('strong').innerText.toLowerCase() : nameCell.innerText.toLowerCase();
            if (name.includes(searchVal)) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
}
</script>
</body>
</html>
