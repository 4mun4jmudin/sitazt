<?php
// admin/kelas.php
require_once '../config/database.php';

// AJAX Endpoint untuk Kelola Siswa Rombel
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
    if ($action === 'get_students') {
        $kelas_id = intval($_GET['kelas_id'] ?? 0);
        try {
            // Siswa di kelas ini
            $stmt1 = $pdo->prepare("SELECT id, nisn, nama_lengkap FROM siswa WHERE kelas_id = :kelas_id AND status_aktif = 'aktif' ORDER BY nama_lengkap ASC");
            $stmt1->execute(['kelas_id' => $kelas_id]);
            $in_class = $stmt1->fetchAll();
            
            // Siswa tanpa kelas
            $stmt2 = $pdo->query("SELECT id, nisn, nama_lengkap FROM siswa WHERE kelas_id IS NULL AND status_aktif = 'aktif' ORDER BY nama_lengkap ASC");
            $out_class = $stmt2->fetchAll();
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'in_class' => $in_class,
                'out_class' => $out_class
            ]);
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

// Proses Aksi POST (CRUD)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $wali_kelas_id = $_POST['wali_kelas_id'] !== '' ? intval($_POST['wali_kelas_id']) : null;
        
        if ($nama_kelas === '') {
            $error = 'Nama kelas wajib diisi.';
        } else {
            try {
                // Periksa apakah nama kelas sudah terdaftar
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE nama_kelas = :nama_kelas");
                $stmt_check->execute(['nama_kelas' => $nama_kelas]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = 'Nama kelas sudah terdaftar.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO kelas (nama_kelas, wali_kelas_id) VALUES (:nama_kelas, :wali_kelas_id)");
                    $stmt->execute([
                        'nama_kelas' => $nama_kelas,
                        'wali_kelas_id' => $wali_kelas_id
                    ]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Menambahkan kelas baru: $nama_kelas");
                    $success = 'Kelas berhasil ditambahkan.';
                }
            } catch (\PDOException $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $wali_kelas_id = $_POST['wali_kelas_id'] !== '' ? intval($_POST['wali_kelas_id']) : null;
        
        if ($nama_kelas === '') {
            $error = 'Nama kelas wajib diisi.';
        } else {
            try {
                // Periksa apakah nama kelas sudah digunakan kelas lain
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE nama_kelas = :nama_kelas AND id != :id");
                $stmt_check->execute(['nama_kelas' => $nama_kelas, 'id' => $id]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = 'Nama kelas sudah digunakan kelas lain.';
                } else {
                    $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = :nama_kelas, wali_kelas_id = :wali_kelas_id WHERE id = :id");
                    $stmt->execute([
                        'nama_kelas' => $nama_kelas,
                        'wali_kelas_id' => $wali_kelas_id,
                        'id' => $id
                    ]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Mengubah kelas ID $id menjadi: $nama_kelas");
                    $success = 'Kelas berhasil diperbarui.';
                }
            } catch (\PDOException $e) {
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // Cek apakah ada siswa di kelas ini
            $stmt_siswa = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = :id");
            $stmt_siswa->execute(['id' => $id]);
            if ($stmt_siswa->fetchColumn() > 0) {
                $error = 'Kelas tidak dapat dihapus karena masih memiliki siswa terdaftar.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                logActivity($pdo, $_SESSION['user_id'], "Menghapus kelas ID $id");
                $success = 'Kelas berhasil dihapus.';
            }
        } catch (\PDOException $e) {
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    } elseif ($action === 'remove_siswa') {
        $siswa_id = intval($_POST['siswa_id'] ?? 0);
        $kelas_id = intval($_POST['kelas_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE siswa SET kelas_id = NULL WHERE id = :siswa_id");
            $stmt->execute(['siswa_id' => $siswa_id]);
            
            // Ambil nama siswa untuk log
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :siswa_id");
            $stmt_name->execute(['siswa_id' => $siswa_id]);
            $siswa_name = $stmt_name->fetchColumn();
            
            logActivity($pdo, $_SESSION['user_id'], "Mengeluarkan siswa $siswa_name dari kelas ID $kelas_id");
            $success = "Siswa berhasil dikeluarkan dari kelas.";
        } catch (\PDOException $e) {
            $error = "Gagal mengeluarkan siswa: " . $e->getMessage();
        }
    } elseif ($action === 'add_siswa') {
        $siswa_id = intval($_POST['siswa_id'] ?? 0);
        $kelas_id = intval($_POST['kelas_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE siswa SET kelas_id = :kelas_id WHERE id = :siswa_id");
            $stmt->execute(['kelas_id' => $kelas_id, 'siswa_id' => $siswa_id]);
            
            // Ambil nama siswa & kelas untuk log
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :siswa_id");
            $stmt_name->execute(['siswa_id' => $siswa_id]);
            $siswa_name = $stmt_name->fetchColumn();
            
            $stmt_kelas = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = :kelas_id");
             $stmt_kelas->execute(['kelas_id' => $kelas_id]);
            $kelas_name = $stmt_kelas->fetchColumn();
            
            logActivity($pdo, $_SESSION['user_id'], "Memasukkan siswa $siswa_name ke kelas $kelas_name");
            $success = "Siswa berhasil ditambahkan ke kelas.";
        } catch (\PDOException $e) {
            $error = "Gagal menambahkan siswa: " . $e->getMessage();
        }
    }
}

// Ambil data untuk tabel
$list_kelas = [];
$list_guru = [];
try {
    // Ambil data kelas berelasi ke wali kelas serta hitung jumlah siswanya
    $list_kelas = $pdo->query("
        SELECT k.*, g.nama_lengkap AS nama_wali, COUNT(s.id) AS jumlah_siswa 
        FROM kelas k 
        LEFT JOIN guru_tahfidz g ON k.wali_kelas_id = g.id 
        LEFT JOIN siswa s ON s.kelas_id = k.id
        GROUP BY k.id, g.nama_lengkap
        ORDER BY k.nama_kelas ASC
    ")->fetchAll();
    
    // Ambil data guru tahfidz untuk dropdown wali kelas
    $list_guru = $pdo->query("SELECT id, nama_lengkap FROM guru_tahfidz ORDER BY nama_lengkap ASC")->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat data: ' . $e->getMessage();
}
?>

<!-- Tombol Tambah & Deskripsi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Kelola rombongan belajar/kelas serta wali kelas tahfidz terkait.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-plus"></i> Tambah Kelas
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

<!-- Tabel Data Kelas -->
<div class="admin-card-table">
    <div class="admin-card-header">
        <h2>Daftar Kelas</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Kelas</th>
                    <th>Wali Kelas Tahfidz</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_kelas)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted);">Belum ada data kelas.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_kelas as $kelas): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></td>
                            <td>
                                <?php if ($kelas['nama_wali']): ?>
                                    <i class="fa-solid fa-user-tie" style="color: var(--primary-color); margin-right: 5px;"></i>
                                    <?php echo htmlspecialchars($kelas['nama_wali']); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Belum Diplot</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status badge-active" style="background-color: #f1f5f9; color: var(--primary-dark);">
                                    <?php echo intval($kelas['jumlah_siswa']); ?> Siswa
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button onclick="showManageSiswaModal(<?php echo $kelas['id']; ?>, '<?php echo htmlspecialchars($kelas['nama_kelas']); ?>')" class="btn btn-primary btn-sm" style="padding: 4px 8px; font-size: 11px;">
                                        <i class="fa-solid fa-users-gear"></i> Kelola Siswa
                                    </button>
                                    <button onclick="showEditModal(<?php echo $kelas['id']; ?>, '<?php echo htmlspecialchars($kelas['nama_kelas']); ?>', '<?php echo $kelas['wali_kelas_id']; ?>')" class="btn btn-secondary btn-sm" style="padding: 4px 8px; font-size: 11px;">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    
                                    <form action="kelas.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $kelas['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--error-color); border-color: rgba(239, 68, 68, 0.2); padding: 4px 8px; font-size: 11px;">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kelas -->
<div class="modal-overlay" id="addModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Tambah Kelas Baru</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="kelas.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" id="nama_kelas" name="nama_kelas" class="form-control" placeholder="Contoh: 7-A Tahfidz" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="wali_kelas_id" class="form-label">Wali Kelas Tahfidz</label>
                    <select id="wali_kelas_id" name="wali_kelas_id" class="form-control" style="padding-left: 15px;">
                        <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                        <?php foreach ($list_guru as $guru): ?>
                            <option value="<?php echo $guru['id']; ?>"><?php echo htmlspecialchars($guru['nama_lengkap']); ?></option>
                        <?php endforeach; ?>
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

<!-- Modal Edit Kelas -->
<div class="modal-overlay" id="editModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Edit Data Kelas</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="kelas.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_nama_kelas" class="form-label">Nama Kelas</label>
                    <input type="text" id="edit_nama_kelas" name="nama_kelas" class="form-control" placeholder="Contoh: 7-A Tahfidz" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_wali_kelas_id" class="form-label">Wali Kelas Tahfidz</label>
                    <select id="edit_wali_kelas_id" name="wali_kelas_id" class="form-control" style="padding-left: 15px;">
                        <option value="">-- Pilih Wali Kelas (Opsional) --</option>
                        <?php foreach ($list_guru as $guru): ?>
                            <option value="<?php echo $guru['id']; ?>"><?php echo htmlspecialchars($guru['nama_lengkap']); ?></option>
                        <?php endforeach; ?>
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

<!-- Modal Kelola Siswa Rombel -->
<div class="modal-overlay" id="manageSiswaModal">
    <div class="admin-modal admin-modal-large">
        <div class="admin-modal-header">
            <h3>Kelola Rombel Siswa - <span id="manage_kelas_title"></span></h3>
            <button class="modal-close" onclick="closeManageSiswaModal()">&times;</button>
        </div>
        <div class="admin-modal-body">
            <!-- Form Cepat Tambah Siswa Ke Kelas -->
            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="color: var(--primary-color); margin-bottom: 10px; font-size: 14px;"><i class="fa-solid fa-user-plus"></i> Masukkan Siswa ke Kelas Ini</h4>
                <form action="kelas.php" method="POST" style="display: flex; gap: 10px; align-items: flex-end;">
                    <input type="hidden" name="action" value="add_siswa">
                    <input type="hidden" name="kelas_id" id="manage_kelas_id_add">
                    
                    <div style="flex: 1;">
                        <label for="add_siswa_select" class="form-label" style="font-size: 11px;">Pilih Siswa (Belum Punya Kelas)</label>
                        <select id="add_siswa_select" name="siswa_id" class="form-control" style="padding-left: 15px;" required>
                            <option value="">-- Pilih Siswa --</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: auto; padding: 12px 20px; height: 45px;">
                        Masukkan
                    </button>
                </form>
            </div>
            
            <!-- Daftar Siswa Aktif di Kelas Ini -->
            <h4 style="margin-bottom: 10px; font-size: 14px; color: var(--primary-dark);"><i class="fa-solid fa-users"></i> Anggota Rombel Saat Ini</h4>
            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 12px;">
                <table class="table-admin" style="font-size: 13px;">
                    <thead>
                        <tr>
                            <th style="padding: 10px 15px;">NISN</th>
                            <th style="padding: 10px 15px;">Nama Siswa</th>
                            <th style="padding: 10px 15px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="in_class_tbody">
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').classList.add('show');
}
function closeAddModal() {
    document.getElementById('addModal').classList.remove('show');
}
function showEditModal(id, nama_kelas, wali_id) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama_kelas').value = nama_kelas;
    document.getElementById('edit_wali_kelas_id').value = wali_id;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// JS untuk Kelola Rombel Siswa
function showManageSiswaModal(kelasId, namaKelas) {
    document.getElementById('manage_kelas_title').innerText = namaKelas;
    document.getElementById('manage_kelas_id_add').value = kelasId;
    
    const addSelect = document.getElementById('add_siswa_select');
    const tbody = document.getElementById('in_class_tbody');
    
    // Reset state
    addSelect.innerHTML = '<option value="">-- Pilih Siswa --</option>';
    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">Memuat data...</td></tr>';
    
    document.getElementById('manageSiswaModal').classList.add('show');
    
    // Fetch data
    fetch(`kelas.php?ajax=1&action=get_students&kelas_id=${kelasId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // 1. Siswa tanpa kelas (dropdown)
                if (data.out_class.length === 0) {
                    addSelect.innerHTML = '<option value="">Semua siswa aktif sudah memiliki kelas</option>';
                } else {
                    data.out_class.forEach(s => {
                        const opt = document.createElement('option');
                        opt.value = s.id;
                        opt.innerText = `${s.nama_lengkap} (${s.nisn})`;
                        addSelect.appendChild(opt);
                    });
                }
                
                // 2. Siswa di kelas ini (tabel)
                tbody.innerHTML = '';
                if (data.in_class.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 15px;">Belum ada siswa di kelas ini.</td></tr>';
                } else {
                    data.in_class.forEach(s => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td style="padding: 10px 15px; font-family: monospace;">${s.nisn}</td>
                            <td style="padding: 10px 15px; font-weight: 600;">${s.nama_lengkap}</td>
                            <td style="padding: 10px 15px; text-align: right;">
                                <form action="kelas.php" method="POST" style="display:inline;" onsubmit="return confirm('Keluarkan siswa ini dari kelas?');">
                                    <input type="hidden" name="action" value="remove_siswa">
                                    <input type="hidden" name="siswa_id" value="${s.id}">
                                    <input type="hidden" name="kelas_id" value="${kelasId}">
                                    <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--error-color); border-color: rgba(239, 68, 68, 0.2); padding: 2px 6px; font-size: 10px; width: auto; display: inline-flex;">
                                        <i class="fa-solid fa-user-minus"></i> Keluarkan
                                    </button>
                                </form>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            } else {
                tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--error-color); padding: 15px;">Gagal memuat: ${data.message}</td></tr>`;
            }
        })
        .catch(err => {
            tbody.innerHTML = `<tr><td colspan="3" style="text-align: center; color: var(--error-color); padding: 15px;">Error: ${err.message}</td></tr>`;
        });
}
function closeManageSiswaModal() {
    document.getElementById('manageSiswaModal').classList.remove('show');
}
</script>

</main>
</div>
</div>
</body>
</html>
