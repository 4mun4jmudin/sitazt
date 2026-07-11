<?php
// admin/tahun_ajaran.php
require_once '../config/database.php';

// AJAX Endpoint untuk Kenaikan Kelas Siswa Massal
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Cek apakah admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $action = $_GET['action'] ?? '';
    if ($action === 'get_promo_students') {
        $kelas_asal = intval($_GET['kelas_asal'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT nisn, nama_lengkap FROM siswa WHERE kelas_id = :kelas_id AND status_aktif = 'aktif' ORDER BY nama_lengkap ASC");
            $stmt->execute(['kelas_id' => $kelas_asal]);
            $students = $stmt->fetchAll();
            
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'students' => $students]);
            exit;
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }
}

require_once 'header.php';

$error = '';
$success = '';

// Proses Aksi (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $tahun = trim($_POST['tahun'] ?? '');
        $semester = $_POST['semester'] ?? '';
        $status = $_POST['status'] ?? 'tidak_aktif';
        
        if ($tahun === '' || $semester === '') {
            $error = 'Semua kolom wajib diisi.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Jika tahun ajaran baru langsung diset aktif, nonaktifkan yang lain dahulu
                if ($status === 'aktif') {
                    $pdo->query("UPDATE tahun_ajaran SET status = 'tidak_aktif'");
                }
                
                $stmt = $pdo->prepare("INSERT INTO tahun_ajaran (tahun, semester, status) VALUES (:tahun, :semester, :status)");
                $stmt->execute([
                    'tahun' => $tahun,
                    'semester' => $semester,
                    'status' => $status
                ]);
                
                logActivity($pdo, $_SESSION['user_id'], "Menambahkan tahun ajaran baru: $tahun ($semester)");
                $pdo->commit();
                $success = 'Tahun ajaran berhasil ditambahkan.';
            } catch (\PDOException $e) {
                $pdo->rollBack();
                $error = 'Gagal menambahkan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $tahun = trim($_POST['tahun'] ?? '');
        $semester = $_POST['semester'] ?? '';
        
        if ($tahun === '' || $semester === '') {
            $error = 'Semua kolom wajib diisi.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE tahun_ajaran SET tahun = :tahun, semester = :semester WHERE id = :id");
                $stmt->execute([
                    'tahun' => $tahun,
                    'semester' => $semester,
                    'id' => $id
                ]);
                
                logActivity($pdo, $_SESSION['user_id'], "Mengubah tahun ajaran ID $id menjadi: $tahun ($semester)");
                $success = 'Tahun ajaran berhasil diubah.';
            } catch (\PDOException $e) {
                $error = 'Gagal mengubah data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'activate') {
        $id = intval($_POST['id'] ?? 0);
        try {
            $pdo->beginTransaction();
            
            // Set semua menjadi tidak aktif
            $pdo->query("UPDATE tahun_ajaran SET status = 'tidak_aktif'");
            
            // Set yang terpilih menjadi aktif
            $stmt = $pdo->prepare("UPDATE tahun_ajaran SET status = 'aktif' WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            // Ambil info tahun ajaran untuk log
            $stmt_info = $pdo->prepare("SELECT tahun, semester FROM tahun_ajaran WHERE id = :id");
            $stmt_info->execute(['id' => $id]);
            $info = $stmt_info->fetch();
            $ta_name = $info['tahun'] . ' (' . $info['semester'] . ')';
            
            logActivity($pdo, $_SESSION['user_id'], "Mengaktifkan tahun ajaran: $ta_name");
            
            $pdo->commit();
            $success = "Tahun ajaran $ta_name berhasil diaktifkan.";
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $error = 'Gagal mengaktifkan data: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // Ambil info untuk log
            $stmt_info = $pdo->prepare("SELECT tahun, semester, status FROM tahun_ajaran WHERE id = :id");
            $stmt_info->execute(['id' => $id]);
            $info = $stmt_info->fetch();
            
            if ($info && $info['status'] === 'aktif') {
                $error = 'Tahun ajaran aktif tidak dapat dihapus. Nonaktifkan terlebih dahulu.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM tahun_ajaran WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                logActivity($pdo, $_SESSION['user_id'], "Menghapus tahun ajaran ID $id (" . $info['tahun'] . " " . $info['semester'] . ")");
                $success = 'Tahun ajaran berhasil dihapus.';
            }
        } catch (\PDOException $e) {
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    } elseif ($action === 'mass_promote') {
        $kelas_asal = intval($_POST['kelas_asal'] ?? 0);
        $kelas_tujuan = $_POST['kelas_tujuan'] ?? '';
        
        if ($kelas_asal === 0 || $kelas_tujuan === '') {
            $error = 'Kelas Asal dan Kelas Tujuan wajib dipilih.';
        } elseif ($kelas_asal == $kelas_tujuan) {
            $error = 'Kelas Asal dan Kelas Tujuan tidak boleh sama.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Ambil info nama kelas asal
                $stmt_asal = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = :id");
                $stmt_asal->execute(['id' => $kelas_asal]);
                $nama_asal = $stmt_asal->fetchColumn();
                
                if ($kelas_tujuan === 'alumni') {
                    // Set status_aktif = 'alumni' & kelas_id = NULL
                    $stmt = $pdo->prepare("UPDATE siswa SET status_aktif = 'alumni', kelas_id = NULL WHERE kelas_id = :kelas_asal AND status_aktif = 'aktif'");
                    $stmt->execute(['kelas_asal' => $kelas_asal]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Meluluskan semua siswa dari kelas $nama_asal (Set status alumni)");
                    $success = "Semua siswa dari kelas $nama_asal berhasil diset lulus sebagai Alumni.";
                } else {
                    $kelas_tujuan_id = intval($kelas_tujuan);
                    // Ambil info nama kelas tujuan
                    $stmt_tujuan = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = :id");
                    $stmt_tujuan->execute(['id' => $kelas_tujuan_id]);
                    $nama_tujuan = $stmt_tujuan->fetchColumn();
                    
                    $stmt = $pdo->prepare("UPDATE siswa SET kelas_id = :kelas_tujuan WHERE kelas_id = :kelas_asal AND status_aktif = 'aktif'");
                    $stmt->execute(['kelas_tujuan' => $kelas_tujuan_id, 'kelas_asal' => $kelas_asal]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Memindahkan massal siswa dari kelas $nama_asal ke kelas $nama_tujuan");
                    $success = "Semua siswa dari kelas $nama_asal berhasil dipindahkan ke kelas $nama_tujuan.";
                }
                
                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal memproses kenaikan kelas: ' . $e->getMessage();
            }
        }
    }
}

// Ambil semua data tahun ajaran
$list_ta = [];
$list_kelas_promo = [];
try {
    $list_ta = $pdo->query("SELECT * FROM tahun_ajaran ORDER BY tahun DESC, semester DESC")->fetchAll();
    
    // Ambil daftar kelas untuk kenaikan kelas
    $list_kelas_promo = $pdo->query("
        SELECT k.id, k.nama_kelas, COUNT(s.id) AS jumlah_siswa 
        FROM kelas k 
        LEFT JOIN siswa s ON s.kelas_id = k.id AND s.status_aktif = 'aktif'
        GROUP BY k.id 
        ORDER BY k.nama_kelas ASC
    ")->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal mengambil data dari database.';
}
?>

<!-- Tombol Tambah & Notifikasi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Kelola semester aktif sekolah dan daftar periode akademik.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-plus"></i> Tambah Periode
    </button>
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
        <h2>Daftar Tahun Ajaran</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tahun Akademik</th>
                    <th>Semester</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_ta)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data tahun ajaran.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_ta as $ta): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($ta['tahun']); ?></td>
                            <td><?php echo htmlspecialchars($ta['semester']); ?></td>
                            <td>
                                <?php if ($ta['status'] === 'aktif'): ?>
                                    <span class="badge-status badge-active">Aktif</span>
                                <?php else: ?>
                                    <span class="badge-status badge-inactive">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($ta['status'] !== 'aktif'): ?>
                                        <form action="tahun_ajaran.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan periode ini?');">
                                            <input type="hidden" name="action" value="activate">
                                            <input type="hidden" name="id" value="<?php echo $ta['id']; ?>">
                                            <button type="submit" class="btn btn-primary btn-sm" style="background: var(--success-color); padding: 4px 8px; font-size: 11px;">
                                                <i class="fa-solid fa-circle-play"></i> Aktifkan
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button onclick="showEditModal(<?php echo $ta['id']; ?>, '<?php echo htmlspecialchars($ta['tahun']); ?>', '<?php echo $ta['semester']; ?>')" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    
                                    <?php if ($ta['status'] !== 'aktif'): ?>
                                        <form action="tahun_ajaran.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tahun ajaran ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $ta['id']; ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--error-color); border-color: rgba(239, 68, 68, 0.2); padding: 4px 8px; font-size: 11px;">
                                                <i class="fa-solid fa-trash-can"></i> Hapus
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Data -->
<div class="modal-overlay" id="addModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Tambah Periode Akademik</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="tahun_ajaran.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="tahun" class="form-label">Tahun Ajaran</label>
                    <input type="text" id="tahun" name="tahun" class="form-control" placeholder="Contoh: 2026/2027" style="padding-left: 15px;" required>
                    
                    <div style="margin-top: 8px; display: flex; gap: 8px; align-items: center;">
                        <span style="font-size: 11px; color: var(--text-muted);">Pintasan:</span>
                        <?php
                            $currentYear = (int)date('Y');
                            // Jika sekarang sebelum Juli, biasanya masuk ke tahun ajaran sebelumnya
                            $startYear = (date('m') < 7) ? $currentYear - 1 : $currentYear;
                            
                            for ($i = 0; $i < 3; $i++) {
                                $y1 = $startYear + $i;
                                $y2 = $y1 + 1;
                                $ta_str = $y1 . '/' . $y2;
                                echo '<button type="button" onclick="document.getElementById(\'tahun\').value=\''.$ta_str.'\'" style="font-size: 11px; padding: 3px 10px; border: 1px solid var(--primary-color); background: transparent; color: var(--primary-color); border-radius: 12px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background=\'var(--primary-color)\'; this.style.color=\'#fff\';" onmouseout="this.style.background=\'transparent\'; this.style.color=\'var(--primary-color)\';">'.$ta_str.'</button>';
                            }
                        ?>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="semester" class="form-label">Semester</label>
                    <select id="semester" name="semester" class="form-control" style="padding-left: 15px;" required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="status" class="form-label">Status Awal</label>
                    <select id="status" name="status" class="form-control" style="padding-left: 15px;">
                        <option value="tidak_aktif">Tidak Aktif</option>
                        <option value="aktif">Langsung Aktifkan</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px;">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Data -->
<div class="modal-overlay" id="editModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Edit Periode Akademik</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="tahun_ajaran.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_tahun" class="form-label">Tahun Ajaran</label>
                    <input type="text" id="edit_tahun" name="tahun" class="form-control" placeholder="Contoh: 2026/2027" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_semester" class="form-label">Semester</label>
                    <select id="edit_semester" name="semester" class="form-control" style="padding-left: 15px;" required>
                        <option value="Ganjil">Ganjil</option>
                        <option value="Genap">Genap</option>
                    </select>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px;">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Kenaikan Kelas Siswa Massal -->
<div class="admin-card-table" style="margin-top: 30px; padding: 24px;">
    <div class="admin-card-header" style="border-bottom: none; padding: 0 0 15px 0;">
        <h2>Kenaikan Kelas Siswa Massal (Wizard)</h2>
    </div>
    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
        Pindahkan semua siswa dari satu kelas (Kelas Asal) ke kelas lain (Kelas Tujuan) secara massal. Siswa yang lulus dapat diubah statusnya menjadi <strong>Alumni</strong>.
    </p>
    
    <form action="tahun_ajaran.php" method="POST" id="promotionForm" onsubmit="return confirm('Apakah Anda yakin ingin memproses kenaikan kelas massal ini?');">
        <input type="hidden" name="action" value="mass_promote">
        
        <div class="form-row">
            <div class="form-group">
                <label for="kelas_asal" class="form-label">Kelas Asal</label>
                <select id="kelas_asal" name="kelas_asal" class="form-control" style="padding-left: 15px;" required onchange="previewPromotionStudents(this.value)">
                    <option value="">-- Pilih Kelas Asal --</option>
                    <?php foreach ($list_kelas_promo as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['nama_kelas']); ?> (<?php echo $k['jumlah_siswa']; ?> Siswa)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="kelas_tujuan" class="form-label">Kelas Tujuan</label>
                <select id="kelas_tujuan" name="kelas_tujuan" class="form-control" style="padding-left: 15px;" required>
                    <option value="">-- Pilih Kelas Tujuan --</option>
                    <option value="alumni">Lulus (Set Status Alumni)</option>
                    <?php foreach ($list_kelas_promo as $k): ?>
                        <option value="<?php echo $k['id']; ?>"><?php echo htmlspecialchars($k['nama_kelas']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <!-- Pratinjau Siswa -->
        <div id="preview_siswa_box" style="margin-top: 15px; display: none;">
            <h4 style="font-size: 13px; color: var(--primary-dark); margin-bottom: 8px;">Pratinjau Siswa yang Akan Dipromosikan:</h4>
            <div id="preview_siswa_list" style="max-height: 150px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; background-color: #f8fafc; font-size: 13px;">
                <!-- Dinamis via JS -->
            </div>
        </div>
        
        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary" style="width: auto; padding: 12px 30px;">
                <i class="fa-solid fa-angles-up"></i> Proses Kenaikan Kelas
            </button>
        </div>
    </form>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').classList.add('show');
}
function closeAddModal() {
    document.getElementById('addModal').classList.remove('show');
}
function showEditModal(id, tahun, semester) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_tahun').value = tahun;
    document.getElementById('edit_semester').value = semester;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// JS Kenaikan Kelas Massal
function previewPromotionStudents(kelasId) {
    const box = document.getElementById('preview_siswa_box');
    const list = document.getElementById('preview_siswa_list');
    
    if (!kelasId) {
        box.style.display = 'none';
        list.innerHTML = '';
        return;
    }
    
    list.innerHTML = 'Memuat pratinjau siswa...';
    box.style.display = 'block';
    
    fetch(`tahun_ajaran.php?ajax=1&action=get_promo_students&kelas_asal=${kelasId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.students.length === 0) {
                    list.innerHTML = '<span style="color: var(--text-muted); font-style: italic;">Tidak ada siswa aktif di kelas asal.</span>';
                } else {
                    let html = '<ol style="padding-left: 20px; margin: 0;">';
                    data.students.forEach(s => {
                        html += `<li><strong>${s.nama_lengkap}</strong> (NISN: ${s.nisn})</li>`;
                    });
                    html += '</ol>';
                    list.innerHTML = html;
                }
            } else {
                list.innerHTML = `<span style="color: var(--error-color);">Gagal memuat: ${data.message}</span>`;
            }
        })
        .catch(err => {
            list.innerHTML = `<span style="color: var(--error-color);">Error: ${err.message}</span>`;
        });
}
</script>

</main>
</div>
</div>
</body>
</html>
