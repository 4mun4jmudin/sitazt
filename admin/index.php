<?php
// admin/index.php
require_once '../config/database.php';
require_once 'header.php';

// 1. Ambil jumlah statistik
try {
    $count_siswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
    $count_guru = $pdo->query("SELECT COUNT(*) FROM guru_tahfidz")->fetchColumn();
    $count_ortu = $pdo->query("SELECT COUNT(*) FROM orang_tua")->fetchColumn();
    $count_kelas = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
    $count_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Ambil tahun ajaran aktif
    $stmt_ta = $pdo->query("SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
    $ta_aktif = $stmt_ta->fetch();
    $ta_display = $ta_aktif ? $ta_aktif['tahun'] . ' (' . $ta_aktif['semester'] . ')' : 'Tidak Ada';
} catch (\PDOException $e) {
    $count_siswa = $count_guru = $count_ortu = $count_kelas = $count_users = 0;
    $ta_display = 'Error DB';
}

// 2. Ambil 5 riwayat aktivitas terbaru
$riwayat = [];
try {
    $stmt_riwayat = $pdo->query("
        SELECT r.*, u.nama_lengkap 
        FROM riwayat_pengguna r 
        LEFT JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 1
    ");
    $riwayat = $stmt_riwayat->fetchAll();
} catch (\PDOException $e) {
    // Abaikan
}

// 3. Ambil data Chart Siswa per Kelas
$chart_kelas_labels = [];
$chart_kelas_data = [];
try {
    $list_chart_kelas = $pdo->query("
        SELECT k.nama_kelas, COUNT(s.id) as jumlah 
        FROM kelas k 
        LEFT JOIN siswa s ON s.kelas_id = k.id 
        GROUP BY k.id, k.nama_kelas
        ORDER BY k.nama_kelas ASC
    ")->fetchAll();
    foreach ($list_chart_kelas as $ck) {
        $chart_kelas_labels[] = $ck['nama_kelas'];
        $chart_kelas_data[] = intval($ck['jumlah']);
    }
} catch (\PDOException $e) {
    // Abaikan
}

// 4. Ambil data Chart Sebaran Jenis Kelamin
$chart_gender_labels = ['Laki-laki', 'Perempuan', 'Belum Diisi'];
$chart_gender_data = [0, 0, 0];
try {
    $list_chart_gender = $pdo->query("
        SELECT 
            jenis_kelamin, 
            COUNT(*) as jumlah 
        FROM siswa 
        GROUP BY jenis_kelamin
    ")->fetchAll();
    foreach ($list_chart_gender as $cg) {
        if ($cg['jenis_kelamin'] === 'L') {
            $chart_gender_data[0] = intval($cg['jumlah']);
        } elseif ($cg['jenis_kelamin'] === 'P') {
            $chart_gender_data[1] = intval($cg['jumlah']);
        } else {
            $chart_gender_data[2] += intval($cg['jumlah']);
        }
    }
} catch (\PDOException $e) {
    // Abaikan
}

$total_gender = array_sum($chart_gender_data);
$pct_L = $total_gender > 0 ? round(($chart_gender_data[0] / $total_gender) * 100) : 0;
$pct_P = $total_gender > 0 ? round(($chart_gender_data[1] / $total_gender) * 100) : 0;
?>

<!-- Welcome Area -->
<div class="card" style="margin-bottom: 25px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%;">
    <h2 style="font-family: var(--font-heading); color: var(--primary-color); margin-bottom: 10px;">
        Selamat Datang di Portal Admin
    </h2>
    <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
        Melalui portal kontrol ini, Anda memiliki kendali penuh atas manajemen akademik dan autentikasi pengguna MI
        Al-Adzkiya. Silakan gunakan menu sidebar di sebelah kiri untuk memulai pengelolaan data.
    </p>
</div>

<!-- Statistik Widget Grid -->
<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Siswa</h3>
            <p><?php echo $count_siswa; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-green">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Guru Tahfidz</h3>
            <p><?php echo $count_guru; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-blue">
            <i class="fa-solid fa-chalkboard-user"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total Orang Tua</h3>
            <p><?php echo $count_ortu; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-green" style="background-color: #ecfdf5; color: #059669;">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Rombel Kelas</h3>
            <p><?php echo $count_kelas; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-yellow">
            <i class="fa-solid fa-school"></i>
        </div>
    </div>
    <div class="stat-card span-2-mobile"
        style="display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 15px;">
        <h3
            style="font-size: 12px; margin: 0 0 8px 0; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">
            Sebaran Gender</h3>
        <div style="position: relative; height: 90px; width: 90px;">
            <canvas id="chartGenderSiswa"></canvas>
            <div
                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; pointer-events: none;">
                <span style="font-size: 9px; font-weight: bold; color: #3b82f6; line-height: 1.1;">L
                    <?php echo $pct_L; ?>%</span>
                <span style="font-size: 9px; font-weight: bold; color: #ec4899; line-height: 1.1;">P
                    <?php echo $pct_P; ?>%</span>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Total User</h3>
            <p><?php echo $count_users; ?></p>
        </div>
        <div class="stat-icon-box stat-icon-blue" style="background-color: #f0f9ff; color: #0284c7;">
            <i class="fa-solid fa-user-gear"></i>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <h3>Tahun Ajaran Aktif</h3>
            <p style="font-size: 16px; margin-top: 10px; font-weight: bold; color: var(--primary-dark);">
                <?php echo htmlspecialchars($ta_display); ?></p>
        </div>
        <div class="stat-icon-box stat-icon-purple">
            <i class="fa-solid fa-calendar-days"></i>
        </div>
    </div>
    <div class="stat-card span-2 span-2-mobile"
        style="display: flex; flex-direction: column; justify-content: center; padding: 15px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3 style="font-size: 14px; margin: 0; color: var(--text-main);">Aktivitas Terbaru</h3>
            <a href="riwayat.php"
                style="font-size: 11px; color: var(--primary-color); text-decoration: none; font-weight: 600;">Lihat
                Semua</a>
        </div>
        <?php if (empty($riwayat)): ?>
            <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Belum ada aktivitas tercatat.</p>
        <?php else: ?>
            <?php foreach ($riwayat as $log): ?>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong
                            style="color: var(--primary-dark); font-size: 12px;"><?php echo htmlspecialchars($log['nama_lengkap'] ?? 'Sistem'); ?></strong>
                        <span
                            style="font-size: 10px; color: var(--text-muted);"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></span>
                    </div>
                    <div style="color: var(--text-muted); line-height: 1.3; font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                        title="<?php echo htmlspecialchars($log['aktivitas']); ?>">
                        <?php echo htmlspecialchars($log['aktivitas']); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Chart Sebaran Jenis Kelamin
        const ctxGender = document.getElementById('chartGenderSiswa').getContext('2d');
        new Chart(ctxGender, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($chart_gender_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($chart_gender_data); ?>,
                    backgroundColor: [
                        '#3b82f6', // Laki-laki
                        '#ec4899', // Perempuan
                        '#94a3b8'  // Belum Diisi
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                rotation: 0,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        titleFont: { size: 10 },
                        bodyFont: { size: 11 }
                    }
                },
                cutout: '70%'
            }
        });
    });
</script>

</main>
</div>
</div>
</body>

</html>