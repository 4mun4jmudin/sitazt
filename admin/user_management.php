<?php
// admin/user_management.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Proses Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'admin';
        
        if ($username === '' || $password === '' || $nama_lengkap === '') {
            $error = 'Nama Lengkap, Username, dan Sandi wajib diisi.';
        } else {
            try {
                // Periksa duplikasi username
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt_check->execute(['username' => $username]);
                if ($stmt_check->fetchColumn() > 0) {
                    $error = 'Nama pengguna (username) sudah terdaftar.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, nama_lengkap, email, role, security_question, security_answer) 
                        VALUES (:username, :password, :nama_lengkap, :email, :role, 'Nama sekolah dasar Anda?', 'mi al-adzkiya')
                    ");
                    $stmt->execute([
                        'username' => $username,
                        'password' => $hashed_password,
                        'nama_lengkap' => $nama_lengkap,
                        'email' => $email,
                        'role' => $role
                    ]);
                    
                    $new_id = $pdo->lastInsertId();
                    
                    // Jika rolenya guru_tahfidz atau orang_tua, buat record profil kosongnya agar terelasi
                    if ($role === 'guru_tahfidz') {
                        $stmt_guru = $pdo->prepare("INSERT INTO guru_tahfidz (user_id, nama_lengkap) VALUES (:user_id, :nama)");
                        $stmt_guru->execute(['user_id' => $new_id, 'nama' => $nama_lengkap]);
                    } elseif ($role === 'orang_tua') {
                        $stmt_ortu = $pdo->prepare("INSERT INTO orang_tua (user_id, nama_lengkap) VALUES (:user_id, :nama)");
                        $stmt_ortu->execute(['user_id' => $new_id, 'nama' => $nama_lengkap]);
                    }
                    
                    logActivity($pdo, $_SESSION['user_id'], "Membuat akun baru: $username ($role)");
                    $success = 'Pengguna baru berhasil ditambahkan.';
                }
            } catch (\PDOException $e) {
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_password') {
        $id = intval($_POST['id'] ?? 0);
        $new_password = $_POST['new_password'] ?? '';
        
        if ($new_password === '') {
            $error = 'Kata sandi baru tidak boleh kosong.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Kata sandi baru minimal harus 6 karakter.';
        } else {
            try {
                // Ambil username untuk log
                $stmt_uname = $pdo->prepare("SELECT username FROM users WHERE id = :id");
                $stmt_uname->execute(['id' => $id]);
                $username = $stmt_uname->fetchColumn();
                
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $stmt->execute([
                    'password' => $hashed_password,
                    'id' => $id
                ]);
                
                logActivity($pdo, $_SESSION['user_id'], "Mereset kata sandi pengguna: $username");
                $success = "Kata sandi untuk pengguna '$username' berhasil direset.";
            } catch (\PDOException $e) {
                $error = 'Gagal mereset kata sandi: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($nama_lengkap === '') {
            $error = 'Nama Lengkap tidak boleh kosong.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // 1. Update di users
                $stmt = $pdo->prepare("UPDATE users SET nama_lengkap = :nama_lengkap, email = :email WHERE id = :id");
                $stmt->execute([
                    'nama_lengkap' => $nama_lengkap,
                    'email' => $email,
                    'id' => $id
                ]);
                
                // 2. Sinkronkan nama ke profil terkait
                $stmt_check = $pdo->prepare("SELECT role FROM users WHERE id = :id");
                $stmt_check->execute(['id' => $id]);
                $role = $stmt_check->fetchColumn();
                
                if ($role === 'guru_tahfidz') {
                    $stmt_guru = $pdo->prepare("UPDATE guru_tahfidz SET nama_lengkap = :nama_lengkap WHERE user_id = :id");
                    $stmt_guru->execute(['nama_lengkap' => $nama_lengkap, 'id' => $id]);
                } elseif ($role === 'orang_tua') {
                    $stmt_ortu = $pdo->prepare("UPDATE orang_tua SET nama_lengkap = :nama_lengkap WHERE user_id = :id");
                    $stmt_ortu->execute(['nama_lengkap' => $nama_lengkap, 'id' => $id]);
                }
                
                logActivity($pdo, $_SESSION['user_id'], "Mengubah profil login user ID $id: $nama_lengkap");
                
                $pdo->commit();
                $success = 'Profil pengguna berhasil diperbarui.';
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal memperbarui profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        if ($id === intval($_SESSION['user_id'])) {
            $error = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Ambil info role & nama
                $stmt_info = $pdo->prepare("SELECT username, role FROM users WHERE id = :id");
                $stmt_info->execute(['id' => $id]);
                $info = $stmt_info->fetch();
                
                if ($info) {
                    // Cek jika guru/orang tua terikat data penting
                    if ($info['role'] === 'guru_tahfidz') {
                        $stmt_check_wali = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE wali_kelas_id = (SELECT id FROM guru_tahfidz WHERE user_id = :id)");
                        $stmt_check_wali->execute(['id' => $id]);
                        if ($stmt_check_wali->fetchColumn() > 0) {
                            throw new \Exception('Guru ini merupakan wali kelas suatu kelas. Hapus kelas atau ganti wali kelas dahulu.');
                        }
                    } elseif ($info['role'] === 'orang_tua') {
                        $stmt_check_siswa = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE orang_tua_id = (SELECT id FROM orang_tua WHERE user_id = :id)");
                        $stmt_check_siswa->execute(['id' => $id]);
                        if ($stmt_check_siswa->fetchColumn() > 0) {
                            throw new \Exception('Orang tua ini terikat dengan data siswa. Hapus data siswa terkait dahulu.');
                        }
                    }
                    
                    // Hapus user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    
                    logActivity($pdo, $_SESSION['user_id'], "Menghapus akun pengguna: " . $info['username']);
                    $success = 'Pengguna berhasil dihapus dari sistem.';
                }
                
                $pdo->commit();
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal menghapus pengguna: ' . $e->getMessage();
            }
        }
    }
}

// Ambil semua pengguna
$list_users = [];
try {
    $list_users = $pdo->query("SELECT id, username, nama_lengkap, email, role, created_at FROM users ORDER BY role ASC, username ASC")->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat daftar pengguna.';
}
?>

<!-- Tombol Tambah & Deskripsi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Kelola seluruh akun pengguna sistem, reset kata sandi, dan buat akun Administrator baru.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-user-plus"></i> Tambah User/Admin
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
        <h2>Daftar Akun Pengguna</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Lengkap</th>
                    <th>Nama Pengguna</th>
                    <th>Email</th>
                    <th>Peran (Role)</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($list_users as $u): ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($u['nama_lengkap']); ?></td>
                        <td><span style="font-family: monospace; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($u['username']); ?></span></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge-status" style="background-color: #fee2e2; color: #991b1b;">Admin</span>
                            <?php elseif ($u['role'] === 'guru_tahfidz'): ?>
                                <span class="badge-status badge-active">Guru Tahfidz</span>
                            <?php else: ?>
                                <span class="badge-status" style="background-color: #eff6ff; color: #1e40af;">Orang Tua</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-sm btn-reset-sandi" 
                                        style="background-color: #ca8a04; padding: 4px 8px; font-size: 11px;"
                                        data-id="<?php echo $u['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($u['username'], ENT_QUOTES); ?>">
                                    <i class="fa-solid fa-key"></i> Reset Sandi
                                </button>
                                
                                <button class="btn btn-secondary btn-sm btn-edit-user" 
                                        style="padding: 4px 8px; font-size: 11px;"
                                        data-id="<?php echo $u['id']; ?>"
                                        data-nama="<?php echo htmlspecialchars($u['nama_lengkap'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($u['email'] ?? '', ENT_QUOTES); ?>">
                                    <i class="fa-solid fa-user-pen"></i> Edit
                                </button>
                                
                                <?php if ($u['id'] !== intval($_SESSION['user_id'])): ?>
                                    <form action="user_management.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini? Semua data profil terkait akan terhapus.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--error-color); border-color: rgba(239, 68, 68, 0.2); padding: 4px 8px; font-size: 11px;">
                                            <i class="fa-solid fa-trash-can"></i> Hapus
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal-overlay" id="addModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Tambah Akun Pengguna</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="user_management.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="email" class="form-label">Email (Opsional)</label>
                    <input type="email" id="email" name="email" class="form-control" style="padding-left: 15px;">
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="role" class="form-label">Peran (Role)</label>
                    <select id="role" name="role" class="form-control" style="padding-left: 15px;" required>
                        <option value="admin">Admin / Staff</option>
                        <option value="guru_tahfidz">Guru Tahfidz</option>
                        <option value="orang_tua">Orang Tua / Wali</option>
                    </select>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="username" class="form-label">Nama Pengguna (Username)</label>
                    <input type="text" id="username" name="username" class="form-control" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 12px;">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 6 karakter" style="padding-left: 15px;" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="closeAddModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px;">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset Sandi -->
<div class="modal-overlay" id="resetModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Reset Sandi Akun</h3>
            <button class="modal-close" onclick="closeResetModal()">&times;</button>
        </div>
        <form action="user_management.php" method="POST">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="reset_id">
            <div class="admin-modal-body">
                <div style="background-color: #fefce8; border: 1px solid rgba(202, 138, 4, 0.2); border-radius: 12px; padding: 15px; margin-bottom: 20px; font-size: 13px; color: #854d0e;">
                    <strong>Akun yang direset:</strong> <span id="reset_uname_text"></span>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">Kata Sandi Baru</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Kata sandi baru (min. 6 karakter)" style="padding-left: 15px;" required>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="button" onclick="closeResetModal()" class="btn btn-secondary" style="flex: 1; padding: 10px;">Batal</button>
                    <button type="submit" class="btn btn-primary" style="flex: 1; padding: 10px; background-color: #ca8a04;">Reset Sandi</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="editModal">
    <div class="admin-modal">
        <div class="admin-modal-header">
            <h3>Edit Akun Login</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="user_management.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="admin-modal-body">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_nama" class="form-label">Nama Lengkap</label>
                    <input type="text" id="edit_nama" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 15px;">
                    <label for="edit_email" class="form-label">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-control" style="padding-left: 15px;">
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 25px;">
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
function showResetModal(id, username) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_uname_text').innerText = username;
    document.getElementById('resetModal').classList.add('show');
}
function closeResetModal() {
    document.getElementById('resetModal').classList.remove('show');
}
function showEditModal(id, nama, email) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_email').value = email;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Delegasi event listener untuk menangani klik Reset secara aman
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-reset-sandi');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const username = btn.getAttribute('data-username');
        showResetModal(id, username);
    }
});

// Delegasi event listener untuk menangani klik Edit secara aman
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-user');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const nama = btn.getAttribute('data-nama');
        const email = btn.getAttribute('data-email');
        showEditModal(id, nama, email);
    }
});
</script>

</main>
</div>
</div>
</body>
</html>
