<?php
// admin/siswa.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Proses Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nisn = trim($_POST['nisn'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $ttl = '';
        if ($tempat_lahir !== '' && $tanggal_lahir !== '') {
            $ttl = $tempat_lahir . ', ' . $tanggal_lahir;
        } elseif ($tempat_lahir !== '') {
            $ttl = $tempat_lahir;
        } elseif ($tanggal_lahir !== '') {
            $ttl = $tanggal_lahir;
        }
        $jenis_kelamin = (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] !== '') ? $_POST['jenis_kelamin'] : null;
        $kelas_id = null;
        $orang_tua_id = null;
        $status_aktif = 'aktif';
        
        if ($nisn === '' || $nama_lengkap === '') {
            $error = 'NISN dan Nama Lengkap wajib diisi.';
        } else {
            try {
                // Periksa duplikasi NISN
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn");
                $stmt_check->execute(['nisn' => $nisn]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = 'NISN siswa sudah terdaftar.';
                } else {
                    $foto_profil = null;

                    $stmt = $pdo->prepare("
                        INSERT INTO siswa (nisn, nama_lengkap, ttl, jenis_kelamin, kelas_id, orang_tua_id, status_aktif, foto_profil) 
                        VALUES (:nisn, :nama_lengkap, :ttl, :jenis_kelamin, :kelas_id, :orang_tua_id, :status_aktif, :foto_profil)
                    ");
                    $stmt->execute([
                        'nisn' => $nisn,
                        'nama_lengkap' => $nama_lengkap,
                        'ttl' => $ttl !== '' ? $ttl : null,
                        'jenis_kelamin' => $jenis_kelamin,
                        'kelas_id' => $kelas_id,
                        'orang_tua_id' => $orang_tua_id,
                        'status_aktif' => $status_aktif,
                        'foto_profil' => $foto_profil
                    ]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Menambahkan siswa baru: $nama_lengkap (NISN: $nisn)");
                    $success = 'Data Siswa berhasil ditambahkan.';
                }
            } catch (\Exception $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nisn = trim($_POST['nisn'] ?? '');
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        
        $tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
        $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
        $ttl = '';
        if ($tempat_lahir !== '' && $tanggal_lahir !== '') {
            $ttl = $tempat_lahir . ', ' . $tanggal_lahir;
        } elseif ($tempat_lahir !== '') {
            $ttl = $tempat_lahir;
        } elseif ($tanggal_lahir !== '') {
            $ttl = $tanggal_lahir;
        }
        
        $jenis_kelamin = (isset($_POST['jenis_kelamin']) && $_POST['jenis_kelamin'] !== '') ? $_POST['jenis_kelamin'] : null;
        $kelas_id = (isset($_POST['kelas_id']) && $_POST['kelas_id'] !== '') ? intval($_POST['kelas_id']) : null;
        $orang_tua_id = (isset($_POST['orang_tua_id']) && $_POST['orang_tua_id'] !== '') ? intval($_POST['orang_tua_id']) : null;
        $status_aktif = $_POST['status_aktif'] ?? 'aktif';
        
        if ($nisn === '' || $nama_lengkap === '') {
            $error = 'NISN dan Nama Lengkap wajib diisi.';
        } else {
            try {
                // Periksa duplikasi NISN
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE nisn = :nisn AND id != :id");
                $stmt_check->execute(['nisn' => $nisn, 'id' => $id]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = 'NISN sudah digunakan oleh siswa lain.';
                } else {
                    // Ambil foto profil lama jika ada
                    $stmt_old = $pdo->prepare("SELECT foto_profil FROM siswa WHERE id = :id");
                    $stmt_old->execute(['id' => $id]);
                    $old_photo = $stmt_old->fetchColumn();
                    
                    $foto_profil = $old_photo;
                    $update_photo = false;
                    
                    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['foto_profil']['tmp_name'];
                        $file_name = $_FILES['foto_profil']['name'];
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png'];
                        
                        if (in_array($ext, $allowed_exts)) {
                            if (!file_exists('../uploads/siswa/')) {
                                mkdir('../uploads/siswa/', 0777, true);
                            }
                            $new_filename = 'siswa_' . $nisn . '_' . time() . '.' . $ext;
                            $dest_path = '../uploads/siswa/' . $new_filename;
                            if (move_uploaded_file($file_tmp, $dest_path)) {
                                $foto_profil = $new_filename;
                                $update_photo = true;
                                // Hapus foto lama
                                if ($old_photo && file_exists('../uploads/siswa/' . $old_photo)) {
                                    @unlink('../uploads/siswa/' . $old_photo);
                                }
                            }
                        } else {
                            throw new \Exception('Format foto harus JPG, JPEG, atau PNG.');
                        }
                    }

                    if ($update_photo) {
                        $stmt = $pdo->prepare("
                            UPDATE siswa 
                            SET nisn = :nisn, nama_lengkap = :nama_lengkap, ttl = :ttl, jenis_kelamin = :jenis_kelamin,
                                kelas_id = :kelas_id, orang_tua_id = :orang_tua_id, status_aktif = :status_aktif, foto_profil = :foto_profil 
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'nisn' => $nisn,
                            'nama_lengkap' => $nama_lengkap,
                            'ttl' => $ttl !== '' ? $ttl : null,
                            'jenis_kelamin' => $jenis_kelamin,
                            'kelas_id' => $kelas_id,
                            'orang_tua_id' => $orang_tua_id,
                            'status_aktif' => $status_aktif,
                            'foto_profil' => $foto_profil,
                            'id' => $id
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE siswa 
                            SET nisn = :nisn, nama_lengkap = :nama_lengkap, ttl = :ttl, jenis_kelamin = :jenis_kelamin,
                                kelas_id = :kelas_id, orang_tua_id = :orang_tua_id, status_aktif = :status_aktif 
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            'nisn' => $nisn,
                            'nama_lengkap' => $nama_lengkap,
                            'ttl' => $ttl !== '' ? $ttl : null,
                            'jenis_kelamin' => $jenis_kelamin,
                            'kelas_id' => $kelas_id,
                            'orang_tua_id' => $orang_tua_id,
                            'status_aktif' => $status_aktif,
                            'id' => $id
                        ]);
                    }
                    
                    logActivity($pdo, $_SESSION['user_id'], "Mengubah data siswa ID $id: $nama_lengkap");
                    $success = 'Data Siswa berhasil diperbarui.';
                }
            } catch (\Exception $e) {
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        try {
            // Ambil info siswa untuk log & hapus foto
            $stmt_info = $pdo->prepare("SELECT nama_lengkap, foto_profil FROM siswa WHERE id = :id");
            $stmt_info->execute(['id' => $id]);
            $siswa_info = $stmt_info->fetch();
            
            if ($siswa_info) {
                $name = $siswa_info['nama_lengkap'];
                $old_photo = $siswa_info['foto_profil'];
                
                if ($old_photo && file_exists('../uploads/siswa/' . $old_photo)) {
                    @unlink('../uploads/siswa/' . $old_photo);
                }
                
                $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                logActivity($pdo, $_SESSION['user_id'], "Menghapus siswa: $name");
                $success = 'Data Siswa berhasil dihapus.';
            } else {
                $error = 'Siswa tidak ditemukan.';
            }
        } catch (\PDOException $e) {
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    }
}

// Ambil data pendukung
$list_siswa = [];
$list_kelas = [];
$list_ortu = [];
try {
    // Ambil data siswa dengan relasi kelas dan orang tua
    $list_siswa = $pdo->query("
        SELECT s.*, k.nama_kelas, o.nama_lengkap AS nama_orang_tua 
        FROM siswa s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        LEFT JOIN orang_tua o ON s.orang_tua_id = o.id 
        ORDER BY s.nama_lengkap ASC
    ")->fetchAll();
    
    // Ambil kelas untuk dropdown
    $list_kelas = $pdo->query("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas ASC")->fetchAll();
    
    // Ambil orang tua untuk dropdown
    $list_ortu = $pdo->query("SELECT id, nama_lengkap FROM orang_tua ORDER BY nama_lengkap ASC")->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat data dari database.';
}
?>

<!-- Tombol Tambah & Deskripsi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Kelola pendaftaran siswa, pembagian kelas, dan pemetaan ke orang tua wali.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-user-plus"></i> Tambah Siswa
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
    <script>
        alert("Pemberitahuan: <?php echo htmlspecialchars($success); ?>");
    </script>
<?php endif; ?>

<!-- Tabel Data -->
<div class="admin-card-table">
    <div class="admin-card-header">
        <h2>Daftar Siswa</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>NISN</th>
                    <th>Nama Siswa</th>
                    <th>L/P</th>
                    <th>TTL</th>
                    <th>Kelas</th>
                    <th>Orang Tua / Wali</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_siswa)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted);">Belum ada data siswa.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_siswa as $siswa): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <?php if ($siswa['foto_profil'] && file_exists('../uploads/siswa/' . $siswa['foto_profil'])): ?>
                                    <img src="../uploads/siswa/<?php echo htmlspecialchars($siswa['foto_profil']); ?>" alt="Foto <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; display: block; border: 1.5px solid var(--primary-color);">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; border: 1.5px solid #e2e8f0; display: flex; justify-content: center; align-items: center; color: var(--text-muted); font-size: 14px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><span style="font-family: monospace; font-weight: 500;"><?php echo htmlspecialchars($siswa['nisn']); ?></span></td>
                            <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></td>
                            <td>
                                <?php 
                                if ($siswa['jenis_kelamin'] === 'L') {
                                    echo 'L';
                                } elseif ($siswa['jenis_kelamin'] === 'P') {
                                    echo 'P';
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($siswa['ttl'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($siswa['nama_kelas'] ?? 'Belum Diplot'); ?></td>
                            <td>
                                <?php if ($siswa['nama_orang_tua']): ?>
                                    <i class="fa-solid fa-user" style="color: var(--text-muted); margin-right: 5px; font-size: 12px;"></i>
                                    <?php echo htmlspecialchars($siswa['nama_orang_tua']); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Belum Diplot</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($siswa['status_aktif'] === 'aktif'): ?>
                                    <span class="badge-status badge-active">Aktif</span>
                                <?php elseif ($siswa['status_aktif'] === 'alumni'): ?>
                                    <span class="badge-status" style="background-color: #e0f2fe; color: #0369a1;">Alumni</span>
                                <?php else: ?>
                                    <span class="badge-status badge-inactive">Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                 <div class="action-buttons">
                                     <button class="btn btn-secondary btn-sm btn-edit-siswa" 
                                             style="padding: 4px 8px; font-size: 11px;"
                                             data-id="<?php echo $siswa['id']; ?>"
                                             data-nisn="<?php echo htmlspecialchars($siswa['nisn'], ENT_QUOTES); ?>"
                                             data-nama="<?php echo htmlspecialchars($siswa['nama_lengkap'], ENT_QUOTES); ?>"
                                             data-ttl="<?php echo htmlspecialchars($siswa['ttl'] ?? '', ENT_QUOTES); ?>"
                                             data-jenis-kelamin="<?php echo htmlspecialchars($siswa['jenis_kelamin'] ?? '', ENT_QUOTES); ?>"
                                             data-kelas-id="<?php echo htmlspecialchars($siswa['kelas_id'] ?? '', ENT_QUOTES); ?>"
                                             data-orang-tua-id="<?php echo htmlspecialchars($siswa['orang_tua_id'] ?? '', ENT_QUOTES); ?>"
                                             data-status="<?php echo htmlspecialchars($siswa['status_aktif'], ENT_QUOTES); ?>">
                                         <i class="fa-solid fa-pen-to-square"></i> Edit
                                     </button>
                                    
                                    <form action="siswa.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data siswa ini?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $siswa['id']; ?>">
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

<!-- Modal Tambah -->
<div class="modal-overlay" id="addModal">
    <div class="admin-modal admin-modal-large">
        <div class="admin-modal-header">
            <h3>Pendaftaran Siswa Baru</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="siswa.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nisn" class="form-label">NISN (Nomor Induk Siswa Nasional)</label>
                        <input type="text" id="nisn" name="nisn" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tempat_lahir" class="form-label">Tempat Lahir</label>
                        <input type="text" id="tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Contoh: Jakarta" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="tanggal_lahir" class="form-label">Tanggal Lahir</label>
                        <input type="date" id="tanggal_lahir" name="tanggal_lahir" class="form-control" style="padding-left: 15px;">
                    </div>
                    <div class="form-group">
                        <!-- Space for alignment if needed, or left empty -->
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px;">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="editModal">
    <div class="admin-modal admin-modal-large">
        <div class="admin-modal-header">
            <h3>Edit Data Siswa</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="siswa.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="admin-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nisn" class="form-label">NISN</label>
                        <input type="text" id="edit_nisn" name="nisn" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nama" class="form-label">Nama Lengkap Siswa</label>
                        <input type="text" id="edit_nama" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_jenis_kelamin" class="form-label">Jenis Kelamin</label>
                        <select id="edit_jenis_kelamin" name="jenis_kelamin" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Pilih Jenis Kelamin --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_tempat_lahir" class="form-label">Tempat Lahir</label>
                        <input type="text" id="edit_tempat_lahir" name="tempat_lahir" class="form-control" placeholder="Contoh: Jakarta" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_tanggal_lahir" class="form-label">Tanggal Lahir</label>
                        <input type="date" id="edit_tanggal_lahir" name="tanggal_lahir" class="form-control" style="padding-left: 15px;">
                    </div>
                    <div class="form-group">
                        <!-- Spacer -->
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_kelas_id" class="form-label">Rombel Kelas</label>
                        <select id="edit_kelas_id" name="kelas_id" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Pilih Rombel Kelas --</option>
                            <?php foreach ($list_kelas as $kelas): ?>
                                <option value="<?php echo $kelas['id']; ?>"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_orang_tua_id" class="form-label">Orang Tua / Wali</label>
                        <select id="edit_orang_tua_id" name="orang_tua_id" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Hubungkan dengan Orang Tua/Wali --</option>
                            <?php foreach ($list_ortu as $ortu): ?>
                                <option value="<?php echo $ortu['id']; ?>"><?php echo htmlspecialchars($ortu['nama_lengkap']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_status" class="form-label">Status Siswa</label>
                        <select id="edit_status" name="status_aktif" class="form-control" style="padding-left: 15px;">
                            <option value="aktif">Aktif Belajar</option>
                            <option value="alumni">Alumni</option>
                            <option value="keluar">Keluar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="edit_foto_profil" class="form-label">Foto Profil Baru (Opsional)</label>
                        <input type="file" id="edit_foto_profil" name="foto_profil" class="form-control" accept="image/png, image/jpeg, image/jpg" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px;">Simpan Perubahan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').classList.add('show');
}
function closeAddModal() {
    document.getElementById('addModal').classList.remove('show');
}
function showEditModal(id, nisn, nama, ttl, jenis_kelamin, kelas_id, ortu_id, status) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nisn').value = nisn;
    document.getElementById('edit_nama').value = nama;
    
    // Parse & Normalize TTL (format: Tempat, YYYY-MM-DD or DD-MM-YYYY)
    let tempat = '';
    let tanggal = '';
    if (ttl && ttl.includes(',')) {
        let parts = ttl.split(',');
        tempat = parts[0].trim();
        let datePart = parts[1].trim();
        if (datePart.match(/^\d{4}-\d{2}-\d{2}$/)) {
            tanggal = datePart;
        } else if (datePart.match(/^\d{2}-\d{2}-\d{4}$/)) { // DD-MM-YYYY
            let dp = datePart.split('-');
            tanggal = `${dp[2]}-${dp[1]}-${dp[0]}`;
        } else if (datePart.match(/^\d{2}\/\d{2}\/\d{4}$/)) { // DD/MM/YYYY
            let dp = datePart.split('/');
            tanggal = `${dp[2]}-${dp[1]}-${dp[0]}`;
        } else {
            tanggal = datePart;
        }
    } else if (ttl && ttl.match(/^\d{4}-\d{2}-\d{2}$/)) {
        tanggal = ttl.trim();
    } else if (ttl && ttl.match(/^\d{2}-\d{2}-\d{4}$/)) {
        let dp = ttl.trim().split('-');
        tanggal = `${dp[2]}-${dp[1]}-${dp[0]}`;
    } else {
        tempat = ttl ? ttl.trim() : '';
    }
    
    document.getElementById('edit_tempat_lahir').value = tempat;
    document.getElementById('edit_tanggal_lahir').value = tanggal;
    document.getElementById('edit_jenis_kelamin').value = jenis_kelamin;
    document.getElementById('edit_kelas_id').value = kelas_id;
    document.getElementById('edit_orang_tua_id').value = ortu_id;
    document.getElementById('edit_status').value = status;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Delegasi event listener untuk menangani klik Edit secara aman
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-siswa');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const nisn = btn.getAttribute('data-nisn');
        const nama = btn.getAttribute('data-nama');
        const ttl = btn.getAttribute('data-ttl');
        const jenisKelamin = btn.getAttribute('data-jenis-kelamin');
        const kelasId = btn.getAttribute('data-kelas-id');
        const orangTuaId = btn.getAttribute('data-orang-tua-id');
        const status = btn.getAttribute('data-status');
        
        showEditModal(id, nisn, nama, ttl, jenisKelamin, kelasId, orangTuaId, status);
    }
});

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

// Instantiate for student birth dates
const currentYear = new Date().getFullYear();
initDropdownDatePicker(document.getElementById('tanggal_lahir'), currentYear - 25, currentYear);
initDropdownDatePicker(document.getElementById('edit_tanggal_lahir'), currentYear - 25, currentYear);
</script>

</main>
</div>
</div>
</body>
</html>
