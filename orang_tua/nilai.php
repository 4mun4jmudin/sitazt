<?php
// orang_tua/nilai.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$setoran_list = [];
$avg_grade_letter = 'N/A';
$grade_counts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
$total_setoran = 0;

if ($anak_aktif) {
    try {
        $stmt = $pdo->prepare("
            SELECT st.*, gt.nama_lengkap AS nama_guru 
            FROM setoran_tahfidz st
            JOIN guru_tahfidz gt ON st.guru_id = gt.id
            WHERE st.siswa_id = :siswa_id
            ORDER BY st.tanggal DESC, st.id DESC
        ");
        $stmt->execute(['siswa_id' => $anak_aktif['id']]);
        $setoran_list = $stmt->fetchAll();
        
        $total_setoran = count($setoran_list);
        if ($total_setoran > 0) {
            $total_points = 0;
            $graded_count = 0;
            foreach ($setoran_list as $s) {
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
                } elseif ($nilai === 'D') {
                    $points = 1.0;
                    $grade_counts['D']++;
                } else {
                    $points = 1.0;
                    $grade_counts['D']++;
                }
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
        }
    } catch (\PDOException $e) {
        $error = 'Gagal memuat rekap nilai setoran.';
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
    <!-- Ringkasan Nilai -->
    <div class="stat-grid">
        <div class="stat-card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <div class="stat-info">
                <h3>Predikat Rata-rata</h3>
                <p style="font-size: 18px; font-weight: 700; margin-top: 5px; color: var(--primary-dark);">
                    <?php echo $avg_grade_letter; ?>
                </p>
            </div>
            <div class="stat-icon-box stat-icon-green">
                <i class="fa-solid fa-graduation-cap"></i>
            </div>
        </div>
        
        <div class="stat-card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <div class="stat-info">
                <h3>Total Setoran Dinilai</h3>
                <p><?php echo $total_setoran; ?></p>
            </div>
            <div class="stat-icon-box stat-icon-blue">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
        
        <div class="stat-card" style="grid-column: span 2; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); display: flex; flex-direction: column; align-items: flex-start; justify-content: center; gap: 8px;">
            <h3 style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; letter-spacing: 0.5px; width: 100%;">
                Penyebaran Nilai Hafalan
            </h3>
            <div style="display: flex; gap: 15px; width: 100%; justify-content: space-between; align-items: center; margin-top: 5px;">
                <div style="flex: 1; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px;">
                    <div style="font-size: 11px; font-weight: 600; color: #16a34a; text-transform: uppercase;">A / A-</div>
                    <div style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?php echo $grade_counts['A']; ?></div>
                </div>
                <div style="flex: 1; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px;">
                    <div style="font-size: 11px; font-weight: 600; color: #2563eb; text-transform: uppercase;">B+ / B / B-</div>
                    <div style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?php echo $grade_counts['B']; ?></div>
                </div>
                <div style="flex: 1; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px;">
                    <div style="font-size: 11px; font-weight: 600; color: #ca8a04; text-transform: uppercase;">C+ / C</div>
                    <div style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?php echo $grade_counts['C']; ?></div>
                </div>
                <div style="flex: 1; text-align: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; border-radius: 10px;">
                    <div style="font-size: 11px; font-weight: 600; color: #dc2626; text-transform: uppercase;">D & Lainnya</div>
                    <div style="font-size: 18px; font-weight: 800; color: var(--text-main); margin-top: 2px;"><?php echo $grade_counts['D']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Nilai -->
    <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%;">
        <div class="admin-card-header" style="padding: 24px;">
            <div>
                <h2 style="font-size: 18px; font-family: var(--font-heading); color: var(--primary-color);">
                    Rekap Hasil Nilai Setoran
                </h2>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">
                    Nilai huruf lengkap dan predikat kelancaran setoran ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
                </p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin: 20px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <?php if (empty($setoran_list)): ?>
                <div style="padding: 50px 24px; text-align: center; color: var(--text-muted);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px; color: #94a3b8; font-size: 24px;">
                        <i class="fa-solid fa-star-half-stroke"></i>
                    </div>
                    <p style="font-weight: 500; font-size: 15px; color: var(--text-main);">Belum ada nilai setoran</p>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                        Rapor nilai akan tampil setelah guru memberikan nilai setoran hafalan siswa.
                    </p>
                </div>
            <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th>Tanggal</th>
                            <th>Surah & Ayat</th>
                            <th>Kategori</th>
                            <th style="text-align: center; width: 100px;">Nilai Huruf</th>
                            <th>Keterangan Kelancaran</th>
                            <th>Guru Penguji</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($setoran_list as $setoran): 
                            $nilai = strtoupper(trim($setoran['nilai']));
                            $predikat = '';
                            $nilai_style = '';
                            
                            if ($nilai === 'A' || $nilai === 'A+') {
                                $predikat = 'Sangat Lancar (Mumtaz)';
                                $nilai_style = 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
                            } elseif ($nilai === 'A-') {
                                $predikat = 'Lancar (Jayyid Jiddan)';
                                $nilai_style = 'background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;';
                            } elseif ($nilai === 'B+' || $nilai === 'B') {
                                $predikat = 'Cukup Lancar (Jayyid)';
                                $nilai_style = 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;';
                            } elseif ($nilai === 'B-') {
                                $predikat = 'Lancar Dengan Sedikit Bantuan';
                                $nilai_style = 'background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;';
                            } elseif ($nilai === 'C+' || $nilai === 'C') {
                                $predikat = 'Kurang Lancar (Maqbul)';
                                $nilai_style = 'background-color: #fefce8; color: #854d0e; border: 1px solid #fef08a;';
                            } else {
                                $predikat = 'Banyak Kesalahan (Dhaif)';
                                $nilai_style = 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;';
                            }
                            
                            $jenis_label = $setoran['jenis'] === 'ziadah' ? 'Ziadah' : 'Murajaah';
                            $jenis_style = $setoran['jenis'] === 'ziadah' 
                                ? 'background-color: rgba(13, 92, 52, 0.05); color: var(--primary-color);' 
                                : 'background-color: rgba(59, 130, 246, 0.05); color: #1d4ed8;';
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                                <td><?php echo date('d-m-Y', strtotime($setoran['tanggal'])); ?></td>
                                <td>
                                    <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($setoran['surah']); ?></strong>
                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                                        Ayat <?php echo $setoran['ayat_mulai']; ?> - <?php echo $setoran['ayat_selesai']; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status" style="<?php echo $jenis_style; ?> font-size: 11px; padding: 2px 8px; font-weight: 500;">
                                        <?php echo $jenis_label; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; width: 38px; height: 38px; line-height: 36px; text-align: center; border-radius: 50%; font-weight: 800; font-size: 14px; <?php echo $nilai_style; ?>">
                                        <?php echo htmlspecialchars($setoran['nilai']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 500; color: var(--text-main);">
                                    <?php echo $predikat; ?>
                                </td>
                                <td>
                                    <span style="font-size: 13px; color: var(--text-main);"><?php echo htmlspecialchars($setoran['nama_guru']); ?></span>
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
