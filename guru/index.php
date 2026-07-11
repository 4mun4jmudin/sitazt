<?php
// guru/index.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$count_siswa = 0;
$count_setoran_minggu_ini = 0;
$count_belum_setoran_minggu_ini = 0;
$count_target_tercapai = 0;
$hafalan_tertinggi = "Belum ada data";
$jumlah_pesan_hari_ini = 0;

$siswa_list = [];
$nama_kelas_list = [];

// Fungsi pembantu untuk format waktu relatif (notifikasi)
function time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v;
        } else {
            unset($string[$k]);
        }
    }

    if (!$full)
        $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' yang lalu' : 'baru saja';
}

try {
    // 1. Ambil daftar kelas yang dibimbing oleh guru ini
    $stmt_kelas = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = :guru_id");
    $stmt_kelas->execute(['guru_id' => $guru_id]);
    $kelas_list = $stmt_kelas->fetchAll();

    $kelas_ids = array_column($kelas_list, 'id');
    $nama_kelas_list = array_column($kelas_list, 'nama_kelas');

    if (!empty($kelas_ids)) {
        $in_clause = implode(',', array_fill(0, count($kelas_ids), '?'));

        // Query siswa bimbingan
        $stmt_siswa = $pdo->prepare("
            SELECT s.*, k.nama_kelas, o.nama_lengkap AS nama_ortu 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id 
            LEFT JOIN orang_tua o ON s.orang_tua_id = o.id
            WHERE s.kelas_id IN ($in_clause) AND s.status_aktif = 'aktif'
            ORDER BY s.nama_lengkap ASC
        ");
        $stmt_siswa->execute($kelas_ids);
        $siswa_list = $stmt_siswa->fetchAll();
        $count_siswa = count($siswa_list);

        // 2. Hitung jumlah siswa yang setoran minggu ini (distinct siswa_id)
        $stmt_set_minggu = $pdo->prepare("
            SELECT COUNT(DISTINCT st.siswa_id) 
            FROM setoran_tahfidz st
            JOIN siswa s ON st.siswa_id = s.id
            WHERE s.kelas_id IN ($in_clause)
              AND s.status_aktif = 'aktif'
              AND YEARWEEK(st.tanggal, 1) = YEARWEEK(CURRENT_DATE(), 1)
        ");
        $stmt_set_minggu->execute($kelas_ids);
        $count_setoran_minggu_ini = $stmt_set_minggu->fetchColumn();

        // 3. Hitung jumlah siswa yang belum setoran minggu ini
        $count_belum_setoran_minggu_ini = max(0, $count_siswa - $count_setoran_minggu_ini);

        // 4. Ambil tahun ajaran aktif
        $stmt_ta = $pdo->query("SELECT id, tahun, semester FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
        $ta_aktif = $stmt_ta->fetch();

        // 5. Hitung target hafalan yang sudah tercapai
        if ($ta_aktif) {
            // Karena execute dengan array campuran parameter posisional & bernama tidak didukung, kita pakai parameter posisional
            $stmt_targets = $pdo->prepare("
                SELECT th.* 
                FROM target_hafalan th
                JOIN siswa s ON th.siswa_id = s.id
                WHERE s.kelas_id IN ($in_clause) 
                  AND s.status_aktif = 'aktif'
                  AND th.tahun_ajaran_id = ?
            ");
            $params = $kelas_ids;
            $params[] = $ta_aktif['id'];
            $stmt_targets->execute($params);
            $active_targets = $stmt_targets->fetchAll();

            foreach ($active_targets as $target) {
                $s_id = $target['siswa_id'];
                $target_surah = trim($target['target_surah'] ?? '');
                $target_juz = trim($target['target_juz'] ?? '');

                $achieved = false;

                if ($target_surah !== '') {
                    // Cek setoran
                    $stmt_check_setoran = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM setoran_tahfidz 
                        WHERE siswa_id = :siswa_id 
                          AND jenis = 'ziadah' 
                          AND surah = :surah
                    ");
                    $stmt_check_setoran->execute([
                        'siswa_id' => $s_id,
                        'surah' => $target_surah
                    ]);
                    if ($stmt_check_setoran->fetchColumn() > 0) {
                        $achieved = true;
                    }
                } elseif ($target_juz !== '') {
                    // Cek sertifikat
                    $stmt_check_cert = $pdo->prepare("
                        SELECT COUNT(*) 
                        FROM sertifikat 
                        WHERE siswa_id = :siswa_id 
                          AND (juz_dihafal LIKE :juz OR :juz_raw LIKE CONCAT('%', juz_dihafal, '%'))
                    ");
                    $stmt_check_cert->execute([
                        'siswa_id' => $s_id,
                        'juz' => '%' . $target_juz . '%',
                        'juz_raw' => $target_juz
                    ]);
                    if ($stmt_check_cert->fetchColumn() > 0) {
                        $achieved = true;
                    }
                }

                if ($achieved) {
                    $count_target_tercapai++;
                }
            }
        }

        // 6. Tentukan hafalan tertinggi di kelas bimbingan
        // Cek sertifikat tertinggi
        $stmt_cert_highest = $pdo->prepare("
            SELECT s.nama_lengkap, c.juz_dihafal
            FROM sertifikat c
            JOIN siswa s ON c.siswa_id = s.id
            WHERE s.kelas_id IN ($in_clause) AND s.status_aktif = 'aktif'
            ORDER BY c.juz_dihafal DESC, c.id DESC
            LIMIT 1
        ");
        $stmt_cert_highest->execute($kelas_ids);
        $cert_highest = $stmt_cert_highest->fetch();

        // Cek surah terbanyak dari setoran
        $stmt_highest = $pdo->prepare("
            SELECT s.nama_lengkap, COUNT(DISTINCT st.surah) as total_surah
            FROM setoran_tahfidz st
            JOIN siswa s ON st.siswa_id = s.id
            WHERE s.kelas_id IN ($in_clause) 
              AND s.status_aktif = 'aktif' 
              AND st.jenis = 'ziadah'
            GROUP BY s.id, s.nama_lengkap
            ORDER BY total_surah DESC
            LIMIT 1
        ");
        $stmt_highest->execute($kelas_ids);
        $highest_data = $stmt_highest->fetch();

        if ($cert_highest) {
            $hafalan_tertinggi = htmlspecialchars($cert_highest['nama_lengkap']) . " (" . htmlspecialchars($cert_highest['juz_dihafal']) . ")";
        } elseif ($highest_data) {
            $hafalan_tertinggi = htmlspecialchars($highest_data['nama_lengkap']) . " (" . $highest_data['total_surah'] . " Surah)";
        }
    }

    // 7. Hitung jumlah pesan konsultasi masuk yang belum dibaca (penerima adalah user_id guru)
    $stmt_konsul_unread = $pdo->prepare("
        SELECT COUNT(*) 
        FROM konsultasi 
        WHERE penerima_id = :user_id 
          AND is_read = 0
    ");
    $stmt_konsul_unread->execute(['user_id' => $user_id]);
    $jumlah_pesan_belum_dibaca = (int) $stmt_konsul_unread->fetchColumn();

    // Chart data preparation
    $counts_per_kelas = [];
    foreach ($kelas_list as $k) {
        $counts_per_kelas[$k['nama_kelas']] = 0;
    }
    foreach ($siswa_list as $s) {
        $kelasName = $s['nama_kelas'];
        if (isset($counts_per_kelas[$kelasName])) {
            $counts_per_kelas[$kelasName]++;
        } else {
            $counts_per_kelas[$kelasName] = 1;
        }
    }
    $chart_labels = json_encode(array_keys($counts_per_kelas));
    $chart_data = json_encode(array_values($counts_per_kelas));

} catch (\PDOException $e) {
    $error = 'Gagal memuat ringkasan data dashboard: ' . $e->getMessage();
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<!-- Statistik Widget Grid -->
<div class="stat-grid">
    <!-- Card 1: Jumlah Siswa -->
    <div class="stat-card">
        <div class="stat-info">
            <h3>Siswa Bimbingan</h3>
            <p><?php echo $count_siswa; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-green">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
    </div>

    <!-- Card 2: Setoran Minggu Ini -->
    <div class="stat-card">
        <div class="stat-info">
            <h3>Setoran Minggu Ini</h3>
            <p><?php echo $count_setoran_minggu_ini; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-blue">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <!-- Card 3: Belum Setoran Minggu Ini -->
    <div class="stat-card">
        <div class="stat-info">
            <h3>Belum Setoran</h3>
            <p><?php echo $count_belum_setoran_minggu_ini; ?></p>
        </div>
        <div class="stat-icon-box" style="background-color: #fef2f2; color: #ef4444;">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
    </div>

    <!-- Card 4: Target Tercapai -->
    <div class="stat-card">
        <div class="stat-info">
            <h3>Target Tercapai</h3>
            <p><?php echo $count_target_tercapai; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-purple">
            <i class="fa-solid fa-bullseye"></i>
        </div>
    </div>

    <!-- Card 5: Hafalan Tertinggi -->
    <div class="stat-card" style="grid-column: span 2;">
        <div class="stat-info" style="width: calc(100% - 60px);">
            <h3>Hafalan Tertinggi</h3>
            <p style="font-size: 18px; margin-top: 5px; color: var(--primary-dark); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                title="<?php echo $hafalan_tertinggi; ?>">
                <?php echo $hafalan_tertinggi; ?>
            </p>
        </div>
        <div class="stat-icon-box" style="background-color: #fff7ed; color: #ea580c;">
            <i class="fa-solid fa-crown"></i>
        </div>
    </div>
</div>

<!-- Notifikasi Konsultasi Card -->
<div class="card"
    style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 25px; width: 100%; max-width: 100%;">
    <h3
        style="font-family: var(--font-heading); color: var(--primary-color); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-comments"></i> Konsultasi Orang Tua
    </h3>

    <div style="text-align: center; padding: 20px 0;">
        <div style="font-size: 48px; font-weight: bold; color: <?php echo $jumlah_pesan_belum_dibaca > 0 ? 'var(--error-color)' : 'var(--primary-color)'; ?>; margin-bottom: 10px;">
            <?php echo $jumlah_pesan_belum_dibaca; ?>
        </div>
        <p style="font-size: 14px; color: var(--text-muted); margin: 0 0 15px 0;">
            Pesan masuk belum dibaca
        </p>
        <a href="konsultasi.php"
            style="display: inline-block; font-size: 12px; color: var(--primary-color); font-weight: 600; text-decoration: none; border: 1px solid var(--primary-color); padding: 8px 16px; border-radius: 6px; transition: all 0.2s;"
            onmouseover="this.style.backgroundColor='var(--primary-color)'; this.style.color='white';"
            onmouseout="this.style.backgroundColor='transparent'; this.style.color='var(--primary-color)';"
        >
            Buka Konsultasi <i class="fa-solid fa-arrow-right" style="font-size: 10px; margin-left: 5px;"></i>
        </a>
    </div>
</div>
</div>

</main>
</div>
</div>
</body>

</html>