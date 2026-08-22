<?php
// admin/orang_tua.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Proses Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $hubungan = $_POST['hubungan'] !== '' ? $_POST['hubungan'] : null;
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
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        if ($password === '') {
            $password = 'password123';
        }
        
        if ($nama_lengkap === '' || $username === '') {
            $error = 'Nama Lengkap dan Nama Pengguna wajib diisi.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 1. Periksa apakah username sudah terdaftar
                $stmt_check_user = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt_check_user->execute(['username' => $username]);
                if ($stmt_check_user->fetchColumn() > 0) {
                    throw new \Exception('Nama pengguna (username) sudah terdaftar.');
                }
                
                // 2. Masukkan ke tabel users
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("
                    INSERT INTO users (username, password, nama_lengkap, email, role, security_question, security_answer) 
                    VALUES (:username, :password, :nama_lengkap, :email, 'orang_tua', 'Nama sekolah dasar Anda?', 'mi al-adzkiya')
                ");
                $stmt_user->execute([
                    'username' => $username,
                    'password' => $hashed_password,
                    'nama_lengkap' => $nama_lengkap,
                    'email' => $email
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // 3. Masukkan ke tabel orang_tua
                $stmt_ortu = $pdo->prepare("
                    INSERT INTO orang_tua (user_id, nama_lengkap, hubungan, ttl, no_hp, alamat) 
                    VALUES (:user_id, :nama_lengkap, :hubungan, :ttl, :no_hp, :alamat)
                ");
                $stmt_ortu->execute([
                    'user_id' => $user_id,
                    'nama_lengkap' => $nama_lengkap,
                    'hubungan' => $hubungan,
                    'ttl' => $ttl !== '' ? $ttl : null,
                    'no_hp' => $no_hp,
                    'alamat' => $alamat !== '' ? $alamat : null
                ]);
                
                $ortu_id = $pdo->lastInsertId();
                
                // 4. Update siswa.orang_tua_id
                if (!empty($siswa_ids)) {
                    $placeholders = implode(',', array_fill(0, count($siswa_ids), '?'));
                    $stmt_update_siswa = $pdo->prepare("UPDATE siswa SET orang_tua_id = ? WHERE id IN ($placeholders)");
                    $params = array_merge([$ortu_id], $siswa_ids);
                    $stmt_update_siswa->execute($params);
                }
                
                logActivity($pdo, $_SESSION['user_id'], "Menambahkan orang tua/wali baru: $nama_lengkap (User ID: $user_id)");
                
                $pdo->commit();
                $success = 'Data Orang Tua/Wali dan akun login berhasil ditambahkan.';
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $hubungan = $_POST['hubungan'] !== '' ? $_POST['hubungan'] : null;
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
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        
        if ($nama_lengkap === '') {
            $error = 'Nama Lengkap wajib diisi.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 1. Update users
                $stmt_user = $pdo->prepare("UPDATE users SET nama_lengkap = :nama_lengkap, email = :email WHERE id = :user_id");
                $stmt_user->execute([
                    'nama_lengkap' => $nama_lengkap,
                    'email' => $email,
                    'user_id' => $user_id
                ]);
                
                // 2. Update orang_tua
                $stmt_ortu = $pdo->prepare("UPDATE orang_tua SET nama_lengkap = :nama_lengkap, hubungan = :hubungan, ttl = :ttl, no_hp = :no_hp, alamat = :alamat WHERE id = :id");
                $stmt_ortu->execute([
                    'nama_lengkap' => $nama_lengkap,
                    'hubungan' => $hubungan,
                    'ttl' => $ttl !== '' ? $ttl : null,
                    'no_hp' => $no_hp,
                    'alamat' => $alamat !== '' ? $alamat : null,
                    'id' => $id
                ]);
                
                // 3. Update siswa links
                $stmt_clear = $pdo->prepare("UPDATE siswa SET orang_tua_id = NULL WHERE orang_tua_id = :id");
                $stmt_clear->execute(['id' => $id]);
                
                if (!empty($siswa_ids)) {
                    $placeholders = implode(',', array_fill(0, count($siswa_ids), '?'));
                    $stmt_update_siswa = $pdo->prepare("UPDATE siswa SET orang_tua_id = ? WHERE id IN ($placeholders)");
                    $params = array_merge([$id], $siswa_ids);
                    $stmt_update_siswa->execute($params);
                }
                
                logActivity($pdo, $_SESSION['user_id'], "Mengubah data orang tua/wali ID $id: $nama_lengkap");
                
                $pdo->commit();
                $success = 'Data Orang Tua/Wali berhasil diperbarui.';
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id'] ?? 0);
        
        try {
            $pdo->beginTransaction();
            
            // Cek apakah orang tua ini memiliki siswa yang terikat
            $stmt_check_siswa = $pdo->prepare("
                SELECT COUNT(*) FROM siswa s 
                JOIN orang_tua o ON s.orang_tua_id = o.id 
                WHERE o.user_id = :user_id
            ");
            $stmt_check_siswa->execute(['user_id' => $user_id]);
            if ($stmt_check_siswa->fetchColumn() > 0) {
                throw new \Exception('Orang tua tidak dapat dihapus karena masih memiliki data siswa terikat.');
            }
            
            // Ambil nama untuk log
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM users WHERE id = :user_id");
            $stmt_name->execute(['user_id' => $user_id]);
            $name = $stmt_name->fetchColumn();
            
            // Hapus user (cascade ke orang_tua)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            
            logActivity($pdo, $_SESSION['user_id'], "Menghapus akun orang tua/wali: $name");
            
            $pdo->commit();
            $success = 'Data Orang Tua/Wali dan akun terkait berhasil dihapus.';
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    }
}

// Ambil data orang tua
$list_ortu = [];
$list_siswa = [];
$ortu_anak = [];
try {
    $list_ortu = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM orang_tua o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.nama_lengkap ASC
    ")->fetchAll();
    
    // Ambil daftar siswa untuk ditautkan
    $list_siswa = $pdo->query("SELECT id, nisn, nama_lengkap FROM siswa WHERE status_aktif = 'aktif' ORDER BY nama_lengkap ASC")->fetchAll();
    
    // Ambil anak-anak dari tiap orang tua
    $siswa_ortu = $pdo->query("SELECT id, nama_lengkap, orang_tua_id FROM siswa WHERE orang_tua_id IS NOT NULL")->fetchAll();
    foreach ($siswa_ortu as $s) {
        $ortu_anak[$s['orang_tua_id']][] = $s;
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat data: ' . $e->getMessage();
}
?>

<!-- Tombol Tambah & Deskripsi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Kelola data orang tua atau wali murid beserta hak akses portal mereka.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-user-plus"></i> Tambah Orang Tua/Wali
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
        <h2>Daftar Orang Tua / Wali</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Wali</th>
                    <th>Hubungan</th>
                    <th>TTL</th>
                    <th>Siswa / Anak</th>
                    <th>No. HP</th>
                    <th>Alamat</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_ortu)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted);">Belum ada data orang tua/wali.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_ortu as $ortu): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($ortu['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($ortu['hubungan'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($ortu['ttl'] ?? '-'); ?></td>
                            <td>
                                <?php 
                                $anak = $ortu_anak[$ortu['id']] ?? [];
                                if (empty($anak)) {
                                    echo '<span style="color: var(--text-muted); font-style: italic;">Belum ditautkan</span>';
                                } else {
                                    $names = [];
                                    $ids = [];
                                    foreach ($anak as $a) {
                                        $names[] = htmlspecialchars($a['nama_lengkap']);
                                        $ids[] = $a['id'];
                                    }
                                    echo implode(', ', $names);
                                }
                                $anak_ids_str = implode(',', $ids ?? []);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($ortu['no_hp'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($ortu['alamat'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($ortu['email'] ?? '-'); ?></td>
                            <td><span style="font-family: monospace; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($ortu['username']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-secondary btn-sm btn-edit-ortu" 
                                            style="padding: 4px 8px; font-size: 11px;"
                                            data-id="<?php echo $ortu['id']; ?>"
                                            data-user-id="<?php echo $ortu['user_id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($ortu['nama_lengkap'], ENT_QUOTES); ?>"
                                            data-hubungan="<?php echo htmlspecialchars($ortu['hubungan'] ?? '', ENT_QUOTES); ?>"
                                            data-ttl="<?php echo htmlspecialchars($ortu['ttl'] ?? '', ENT_QUOTES); ?>"
                                            data-no-hp="<?php echo htmlspecialchars($ortu['no_hp'] ?? '', ENT_QUOTES); ?>"
                                            data-alamat="<?php echo htmlspecialchars($ortu['alamat'] ?? '', ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($ortu['email'] ?? '', ENT_QUOTES); ?>"
                                            data-anak-ids="<?php echo htmlspecialchars($anak_ids_str, ENT_QUOTES); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    
                                    <form action="orang_tua.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data wali murid ini? Akun portalnya juga akan dihapus.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $ortu['user_id']; ?>">
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
            <h3>Tambah Wali Murid Baru</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="orang_tua.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <h4 style="color: var(--primary-color); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;">Profil Biodata</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap Wali</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="no_hp" class="form-label">Nomor WhatsApp/HP</label>
                        <input type="text" id="no_hp" name="no_hp" class="form-control" style="padding-left: 15px;" placeholder="Contoh: 0857...">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="hubungan" class="form-label">Hubungan Keluarga</label>
                        <select id="hubungan" name="hubungan" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Pilih Hubungan --</option>
                            <option value="Ayah">Ayah</option>
                            <option value="Ibu">Ibu</option>
                            <option value="Wali">Wali</option>
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
                        <!-- Spacer -->
                    </div>
                </div>

                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" style="padding-left: 15px;">
                    </div>
                    <div class="form-group">
                        <label for="alamat" class="form-label">Alamat Lengkap</label>
                        <input type="text" id="alamat" name="alamat" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="form-label">Tautkan ke Siswa / Anak</label>
                    <input type="text" id="search_siswa_add" class="form-control" placeholder="Pencarian nama siswa..." style="margin-bottom: 10px; padding-left: 15px;" onkeyup="filterSiswa('search_siswa_add', 'list_siswa_add')">
                    <div id="list_siswa_add" style="max-height: 120px; overflow-y: auto; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px; background-color: #f8fafc;">
                        <?php if (empty($list_siswa)): ?>
                            <span style="color: var(--text-muted); font-size: 13px;">Belum ada data siswa aktif.</span>
                        <?php else: ?>
                            <?php foreach ($list_siswa as $siswa): ?>
                                <div style="margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="siswa_ids[]" value="<?php echo $siswa['id']; ?>" id="add_siswa_<?php echo $siswa['id']; ?>">
                                    <label for="add_siswa_<?php echo $siswa['id']; ?>" style="font-size: 13px; color: var(--text-main); font-weight: 500; cursor: pointer;">
                                        <?php echo htmlspecialchars($siswa['nama_lengkap']); ?> (<?php echo htmlspecialchars($siswa['nisn']); ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <h4 style="color: var(--primary-color); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-top: 25px; margin-bottom: 15px;">Kredensial Login Akun</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label for="username" class="form-label">Nama Pengguna (Username)</label>
                        <input type="text" id="username" name="username" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">Kata Sandi</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan untuk default: password123" style="padding-left: 15px;">
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
            <h3>Edit Data Orang Tua/Wali</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="orang_tua.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="admin-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nama" class="form-label">Nama Lengkap Wali</label>
                        <input type="text" id="edit_nama" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_no_hp" class="form-label">Nomor WhatsApp/HP</label>
                        <input type="text" id="edit_no_hp" name="no_hp" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_hubungan" class="form-label">Hubungan Keluarga</label>
                        <select id="edit_hubungan" name="hubungan" class="form-control" style="padding-left: 15px;">
                            <option value="">-- Pilih Hubungan --</option>
                            <option value="Ayah">Ayah</option>
                            <option value="Ibu">Ibu</option>
                            <option value="Wali">Wali</option>
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
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" style="padding-left: 15px;">
                    </div>
                    <div class="form-group">
                        <label for="edit_alamat" class="form-label">Alamat Lengkap</label>
                        <input type="text" id="edit_alamat" name="alamat" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <label class="form-label">Tautkan ke Siswa / Anak</label>
                    <input type="text" id="search_siswa_edit" class="form-control" placeholder="Pencarian nama siswa..." style="margin-bottom: 10px; padding-left: 15px;" onkeyup="filterSiswa('search_siswa_edit', 'list_siswa_edit')">
                    <div id="list_siswa_edit" style="max-height: 120px; overflow-y: auto; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px; background-color: #f8fafc;">
                        <?php if (empty($list_siswa)): ?>
                            <span style="color: var(--text-muted); font-size: 13px;">Belum ada data siswa aktif.</span>
                        <?php else: ?>
                            <?php foreach ($list_siswa as $siswa): ?>
                                <div style="margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
                                    <input type="checkbox" name="siswa_ids[]" value="<?php echo $siswa['id']; ?>" class="edit-siswa-checkbox" id="edit_siswa_<?php echo $siswa['id']; ?>">
                                    <label for="edit_siswa_<?php echo $siswa['id']; ?>" style="font-size: 13px; color: var(--text-main); font-weight: 500; cursor: pointer;">
                                        <?php echo htmlspecialchars($siswa['nama_lengkap']); ?> (<?php echo htmlspecialchars($siswa['nisn']); ?>)
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
function showEditModal(id, userId, nama, hubungan, ttl, no_hp, alamat, email, anak_ids_str) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_hubungan').value = hubungan;
    
    // Parse & Normalize TTL
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
    
    document.getElementById('edit_no_hp').value = no_hp;
    document.getElementById('edit_alamat').value = alamat;
    document.getElementById('edit_email').value = email;
    
    // Check checkboxes matching the linked children IDs
    const checkedIds = anak_ids_str ? anak_ids_str.toString().split(',') : [];
    const checkboxes = document.querySelectorAll('.edit-siswa-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkedIds.includes(cb.value);
    });
    
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

function filterSiswa(inputId, listId) {
    let input = document.getElementById(inputId);
    let filter = input.value.toLowerCase();
    let container = document.getElementById(listId);
    let items = container.getElementsByTagName('div');
    
    for (let i = 0; i < items.length; i++) {
        let label = items[i].getElementsByTagName('label')[0];
        if (label) {
            let txtValue = label.textContent || label.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                items[i].style.display = "";
            } else {
                items[i].style.display = "none";
            }
        }       
    }
}

// Delegasi event listener untuk menangani klik Edit secara aman
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-ortu');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const userId = btn.getAttribute('data-user-id');
        const nama = btn.getAttribute('data-nama');
        const hubungan = btn.getAttribute('data-hubungan');
        const ttl = btn.getAttribute('data-ttl');
        const noHp = btn.getAttribute('data-no-hp');
        const alamat = btn.getAttribute('data-alamat');
        const email = btn.getAttribute('data-email');
        const anakIdsStr = btn.getAttribute('data-anak-ids');
        
        showEditModal(id, userId, nama, hubungan, ttl, noHp, alamat, email, anakIdsStr);
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

// Instantiate for parent birth dates
const currentYear = new Date().getFullYear();
initDropdownDatePicker(document.getElementById('tanggal_lahir'), currentYear - 80, currentYear - 15);
initDropdownDatePicker(document.getElementById('edit_tanggal_lahir'), currentYear - 80, currentYear - 15);
</script>

</main>
</div>
</div>
</body>
</html>
