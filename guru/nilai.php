<?php
// guru/nilai.php
require_once '../config/database.php';

// Cek apakah mode print diaktifkan
$print_siswa_id = intval($_GET['print_siswa_id'] ?? 0);
$periode_filter = $_GET['periode_filter'] ?? 'semua';
$filter_sql = "";
if ($periode_filter === 'minggu') {
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
        
        // Ambil tahun ajaran aktif
        $ta_aktif = $pdo->query("SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1")->fetch();
        
    } catch (\PDOException $e) {
        die("Error database: " . $e->getMessage());
    }
    
    // Hitung rata-rata nilai
    $total_points = 0;
    $graded_count = 0;
    $avg_grade_letter = 'Belum Dinilai';
    
    foreach ($setoran_list as $s) {
        $nilai = strtoupper(trim($s['nilai']));
        $points = 0;
        if ($nilai === 'A' || $nilai === 'A+') $points = 4.0;
        elseif ($nilai === 'A-') $points = 3.7;
        elseif ($nilai === 'B+') $points = 3.3;
        elseif ($nilai === 'B') $points = 3.0;
        elseif ($nilai === 'B-') $points = 2.7;
        elseif ($nilai === 'C+') $points = 2.3;
        elseif ($nilai === 'C') $points = 2.0;
        else $points = 1.0;
        
        $total_points += $points;
        $graded_count++;
    }
    
    if ($graded_count > 0) {
        $gpa = $total_points / $graded_count;
        if ($gpa >= 3.8) $avg_grade_letter = 'Mumtaz (A)';
        elseif ($gpa >= 3.4) $avg_grade_letter = 'Jayyid Jiddan (A-)';
        elseif ($gpa >= 3.0) $avg_grade_letter = 'Jayyid (B+)';
        elseif ($gpa >= 2.6) $avg_grade_letter = 'Jayyid (B)';
        elseif ($gpa >= 2.0) $avg_grade_letter = 'Maqbul (C)';
        else $avg_grade_letter = 'Dhaif (D)';
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
                text-align: center;
                border-bottom: 3px double #000000;
                padding-bottom: 15px;
                margin-bottom: 25px;
            }
            .kop-surat h2 {
                margin: 0;
                font-size: 20px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .kop-surat h1 {
                margin: 5px 0;
                font-size: 24px;
                color: #0d5c34;
            }
            .kop-surat p {
                margin: 2px 0;
                font-size: 12px;
                font-style: italic;
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
            <h2>Yayasan Pendidikan Islam</h2>
            <h1>MADRASAH IBTIDAIYAH AL-ADZKIYA</h1>
            <p>Jl. Pendidikan No. 45, Jakarta | Telp: 021-12345678 | Email: info@aladzkiya.sch.id</p>
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
                    <th style="width: 10%; text-align: center;">Nilai</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($setoran_list)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; font-style: italic;">Belum ada riwayat setoran hafalan.</td>
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
                            <td style="text-align: center; font-weight: bold;"><?php echo htmlspecialchars($row['nilai']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h3>B. Ringkasan & Hasil Evaluasi</h3>
        <div class="summary-box">
            <p><strong>Total Setoran Hafalan:</strong> <?php echo count($setoran_list); ?> kali</p>
            <p><strong>Predikat Rata-rata Kelancaran:</strong> <span style="font-weight: bold; color: #0d5c34; font-size: 16px;"><?php echo $avg_grade_letter; ?></span></p>
            <p style="margin-top: 10px;"><em>*Keterangan Predikat Kelancaran: Mumtaz (Sangat Lancar), Jayyid Jiddan (Lancar), Jayyid (Cukup Lancar), Maqbul (Kurang Lancar), Dhaif (Banyak Kesalahan).</em></p>
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
            $query_setoran = "SELECT nilai FROM setoran_tahfidz WHERE siswa_id = :siswa_id" . $filter_sql;
            $stmt_setoran = $pdo->prepare($query_setoran);
            $stmt_setoran->execute(['siswa_id' => $siswa['id']]);
            $setoran = $stmt_setoran->fetchAll();
            
            $total_setoran = count($setoran);
            $total_points = 0;
            $graded_count = 0;
            $predikat = 'Belum Dinilai';
            
            $grade_counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            
            foreach ($setoran as $s) {
                $nilai = strtoupper(trim($s['nilai']));
                $points = 0;
                
                if ($nilai === 'A' || $nilai === 'A+') {
                    $points = 4.0;
                    $grade_counts['A']++;
                } elseif ($nilai === 'A-') {
                    $points = 3.7;
                    $grade_counts['A']++;
                } elseif ($nilai === 'B+') {
                    $points = 3.3;
                    $grade_counts['B']++;
                } elseif ($nilai === 'B') {
                    $points = 3.0;
                    $grade_counts['B']++;
                } elseif ($nilai === 'B-') {
                    $points = 2.7;
                    $grade_counts['B']++;
                } elseif ($nilai === 'C+') {
                    $points = 2.3;
                    $grade_counts['C']++;
                } elseif ($nilai === 'C') {
                    $points = 2.0;
                    $grade_counts['C']++;
                } else {
                    $points = 1.0;
                    $grade_counts['D']++;
                }
                
                $total_points += $points;
                $graded_count++;
            }
            
            if ($graded_count > 0) {
                $gpa = $total_points / $graded_count;
                if ($gpa >= 3.8) $predikat = 'Mumtaz (A)';
                elseif ($gpa >= 3.4) $predikat = 'Jayyid Jiddan (A-)';
                elseif ($gpa >= 3.0) $predikat = 'Jayyid (B+)';
                elseif ($gpa >= 2.6) $predikat = 'Jayyid (B)';
                elseif ($gpa >= 2.0) $predikat = 'Maqbul (C)';
                else $predikat = 'Dhaif (D)';
            }
            
            $siswa_grades[] = [
                'id' => $siswa['id'],
                'nisn' => $siswa['nisn'],
                'nama_lengkap' => $siswa['nama_lengkap'],
                'kelas' => $siswa['nama_kelas'],
                'total_setoran' => $total_setoran,
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
    
    <div class="table-responsive">
        <?php if (empty($siswa_grades)): ?>
            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                Belum ada data siswa bimbingan atau data setoran untuk dinilai.
            </div>
        <?php else: ?>
            <table class="table-admin">
                <thead>
                    <tr>
                        <th style="width: 60px; text-align: center;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th style="text-align: center;">Total Setoran</th>
                        <th style="text-align: center;">A / A-</th>
                        <th style="text-align: center;">B+ / B / B-</th>
                        <th style="text-align: center;">C+ / C</th>
                        <th style="text-align: center;">D</th>
                        <th style="text-align: center;">Predikat Kelancaran</th>
                        <th style="text-align: center; width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    foreach ($siswa_grades as $row): 
                        $pred_style = '';
                        if (strpos($row['predikat'], '(A)') !== false || strpos($row['predikat'], '(A-)') !== false) {
                            $pred_style = 'color: #16a34a; font-weight: 700;';
                        } elseif (strpos($row['predikat'], '(B') !== false) {
                            $pred_style = 'color: #2563eb; font-weight: 700;';
                        } elseif (strpos($row['predikat'], '(C') !== false) {
                            $pred_style = 'color: #ca8a04; font-weight: 700;';
                        } elseif (strpos($row['predikat'], '(D') !== false) {
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
                            
                            <td style="text-align: center; color: #16a34a; font-weight: 600;"><?php echo $row['breakdown']['A']; ?></td>
                            <td style="text-align: center; color: #2563eb; font-weight: 600;"><?php echo $row['breakdown']['B']; ?></td>
                            <td style="text-align: center; color: #ca8a04; font-weight: 600;"><?php echo $row['breakdown']['C']; ?></td>
                            <td style="text-align: center; color: #dc2626; font-weight: 600;"><?php echo $row['breakdown']['D']; ?></td>
                            
                            <td style="text-align: center; <?php echo $pred_style; ?>">
                                <?php echo $row['predikat']; ?>
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
</body>
</html>
