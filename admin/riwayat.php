<?php
// admin/riwayat.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Proses Hapus Log (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_logs') {
    try {
        $pdo->query("TRUNCATE TABLE riwayat_pengguna");
        logActivity($pdo, $_SESSION['user_id'], "Membersihkan seluruh data log riwayat aktivitas pengguna");
        $success = 'Seluruh log aktivitas berhasil dibersihkan.';
    } catch (\PDOException $e) {
        $error = 'Gagal membersihkan log: ' . $e->getMessage();
    }
}

// Fitur Pencarian & Filter
$search = trim($_GET['search'] ?? '');

// Ambil data riwayat pengguna
$riwayat_logs = [];
try {
    if ($search !== '') {
        $stmt = $pdo->prepare("
            SELECT r.*, u.nama_lengkap, u.username, u.role 
            FROM riwayat_pengguna r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE u.nama_lengkap LIKE :search 
               OR u.username LIKE :search 
               OR r.aktivitas LIKE :search 
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['search' => "%$search%"]);
        $riwayat_logs = $stmt->fetchAll();
    } else {
        $riwayat_logs = $pdo->query("
            SELECT r.*, u.nama_lengkap, u.username, u.role 
            FROM riwayat_pengguna r 
            LEFT JOIN users u ON r.user_id = u.id 
            ORDER BY r.created_at DESC
        ")->fetchAll();
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat log riwayat dari database.';
}
?>

<!-- Form Pencarian & Tombol Bersihkan -->
<div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <!-- Form Cari -->
    <form action="riwayat.php" method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 400px;">
        <input type="text" name="search" class="form-control" placeholder="Cari nama, username, atau aktivitas..." style="padding: 10px 15px; font-size: 13px;" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="width: auto; padding: 0 15px;">
            <i class="fa-solid fa-magnifying-glass"></i> Cari
        </button>
        <?php if ($search !== ''): ?>
            <a href="riwayat.php" class="btn btn-secondary btn-sm" style="width: auto; padding: 0 15px; display: inline-flex; align-items: center; text-decoration: none;">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Tombol Bersihkan Log -->
    <?php if (!empty($riwayat_logs) && $search === ''): ?>
        <form action="riwayat.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus SELURUH catatan riwayat aktivitas pengguna? Tindakan ini tidak dapat dibatalkan.');">
            <input type="hidden" name="action" value="clear_logs">
            <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--error-color); border-color: rgba(239, 68, 68, 0.2); width: auto;">
                <i class="fa-solid fa-trash-can"></i> Bersihkan Semua Log
            </button>
        </form>
    <?php endif; ?>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
<?php endif; ?>

<!-- Tabel Data -->
<div class="admin-card-table">
    <div class="admin-card-header">
        <h2>Daftar Aktivitas Sistem</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th style="width: 60px;">No</th>
                    <th style="width: 170px;">Tanggal & Waktu</th>
                    <th style="width: 200px;">Pengguna</th>
                    <th>Aktivitas / Tindakan</th>
                    <th style="width: 140px;">Alamat IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat_logs)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted);">Tidak ditemukan catatan riwayat aktivitas.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($riwayat_logs as $log): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <i class="fa-regular fa-clock" style="color: var(--text-muted); margin-right: 5px;"></i>
                                <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--primary-dark);">
                                    <?php echo htmlspecialchars($log['nama_lengkap'] ?? 'Sistem'); ?>
                                </div>
                                <?php if ($log['username']): ?>
                                    <span style="font-size: 11px; color: var(--text-muted);">
                                        @<?php echo htmlspecialchars($log['username']); ?> 
                                        (<?php echo $log['role'] === 'admin' ? 'Admin' : ($log['role'] === 'guru_tahfidz' ? 'Guru' : 'Wali'); ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="line-height: 1.4;"><?php echo htmlspecialchars($log['aktivitas']); ?></td>
                            <td>
                                <span style="font-family: monospace; font-size: 12px; color: #64748b;">
                                    <?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main>
</div>
</div>
</body>
</html>
