<?php
// admin/guru.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Proses Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $email = trim($_POST['email'] ?? '');
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
                
                // 1. Periksa apakah username sudah terdaftar di users
                $stmt_check_user = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
                $stmt_check_user->execute(['username' => $username]);
                if ($stmt_check_user->fetchColumn() > 0) {
                    throw new \Exception('Nama pengguna (username) sudah terdaftar.');
                }
                
                // 2. Periksa apakah NIP sudah terdaftar di guru_tahfidz jika diisi
                if ($nip !== '') {
                    $stmt_check_nip = $pdo->prepare("SELECT COUNT(*) FROM guru_tahfidz WHERE nip = :nip");
                    $stmt_check_nip->execute(['nip' => $nip]);
                    if ($stmt_check_nip->fetchColumn() > 0) {
                        throw new \Exception('NIP guru sudah terdaftar.');
                    }
                }
                
                // 3. Masukkan data ke tabel users terlebih dahulu
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt_user = $pdo->prepare("
                    INSERT INTO users (username, password, nama_lengkap, email, role, security_question, security_answer) 
                    VALUES (:username, :password, :nama_lengkap, :email, 'guru_tahfidz', 'Nama sekolah dasar Anda?', 'mi al-adzkiya')
                ");
                $stmt_user->execute([
                    'username' => $username,
                    'password' => $hashed_password,
                    'nama_lengkap' => $nama_lengkap,
                    'email' => $email
                ]);
                
                $user_id = $pdo->lastInsertId();
                
                // 4. Masukkan data ke tabel guru_tahfidz
                $stmt_guru = $pdo->prepare("
                    INSERT INTO guru_tahfidz (user_id, nip, nama_lengkap, no_hp) 
                    VALUES (:user_id, :nip, :nama_lengkap, :no_hp)
                ");
                $stmt_guru->execute([
                    'user_id' => $user_id,
                    'nip' => $nip !== '' ? $nip : null,
                    'nama_lengkap' => $nama_lengkap,
                    'no_hp' => $no_hp
                ]);
                
                logActivity($pdo, $_SESSION['user_id'], "Menambahkan guru tahfidz baru: $nama_lengkap (User ID: $user_id)");
                
                $pdo->commit();
                $success = 'Data Guru Tahfidz dan akun login berhasil ditambahkan.';
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal menyimpan data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $user_id = intval($_POST['user_id'] ?? 0);
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $nip = trim($_POST['nip'] ?? '');
        $no_hp = trim($_POST['no_hp'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        if ($nama_lengkap === '') {
            $error = 'Nama Lengkap wajib diisi.';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Periksa duplikasi NIP
                if ($nip !== '') {
                    $stmt_check_nip = $pdo->prepare("SELECT COUNT(*) FROM guru_tahfidz WHERE nip = :nip AND id != :id");
                    $stmt_check_nip->execute(['nip' => $nip, 'id' => $id]);
                    if ($stmt_check_nip->fetchColumn() > 0) {
                        throw new \Exception('NIP sudah digunakan oleh guru lain.');
                    }
                }
                
                // 1. Update data di tabel users
                $stmt_user = $pdo->prepare("UPDATE users SET nama_lengkap = :nama_lengkap, email = :email WHERE id = :user_id");
                $stmt_user->execute([
                    'nama_lengkap' => $nama_lengkap,
                    'email' => $email,
                    'user_id' => $user_id
                ]);
                
                // Ambil foto profil lama jika ada
                $stmt_old = $pdo->prepare("SELECT foto_profil FROM guru_tahfidz WHERE id = :id");
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
                        if (!file_exists('../uploads/guru/')) {
                            mkdir('../uploads/guru/', 0777, true);
                        }
                        $new_filename = 'guru_' . ($nip !== '' ? $nip : $id) . '_' . time() . '.' . $ext;
                        $dest_path = '../uploads/guru/' . $new_filename;
                        if (move_uploaded_file($file_tmp, $dest_path)) {
                            $foto_profil = $new_filename;
                            $update_photo = true;
                            // Hapus foto lama
                            if ($old_photo && file_exists('../uploads/guru/' . $old_photo)) {
                                @unlink('../uploads/guru/' . $old_photo);
                            }
                        }
                    } else {
                        throw new \Exception('Format foto harus JPG, JPEG, atau PNG.');
                    }
                }

                // 2. Update data di tabel guru_tahfidz
                if ($update_photo) {
                    $stmt_guru = $pdo->prepare("UPDATE guru_tahfidz SET nip = :nip, nama_lengkap = :nama_lengkap, no_hp = :no_hp, foto_profil = :foto_profil WHERE id = :id");
                    $stmt_guru->execute([
                        'nip' => $nip !== '' ? $nip : null,
                        'nama_lengkap' => $nama_lengkap,
                        'no_hp' => $no_hp,
                        'foto_profil' => $foto_profil,
                        'id' => $id
                    ]);
                } else {
                    $stmt_guru = $pdo->prepare("UPDATE guru_tahfidz SET nip = :nip, nama_lengkap = :nama_lengkap, no_hp = :no_hp WHERE id = :id");
                    $stmt_guru->execute([
                        'nip' => $nip !== '' ? $nip : null,
                        'nama_lengkap' => $nama_lengkap,
                        'no_hp' => $no_hp,
                        'id' => $id
                    ]);
                }
                
                logActivity($pdo, $_SESSION['user_id'], "Mengubah data guru tahfidz ID $id: $nama_lengkap");
                
                $pdo->commit();
                $success = 'Data Guru Tahfidz berhasil diperbarui.';
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = 'Gagal memperbarui data: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $user_id = intval($_POST['user_id'] ?? 0); // Kita hapus dari tabel users, foreign key CASCADE akan otomatis menghapus di guru_tahfidz
        
        try {
            $pdo->beginTransaction();
            
            // Cek apakah guru ini merupakan wali kelas di suatu kelas
            $stmt_check_wali = $pdo->prepare("
                SELECT COUNT(*) FROM kelas k 
                JOIN guru_tahfidz g ON k.wali_kelas_id = g.id 
                WHERE g.user_id = :user_id
            ");
            $stmt_check_wali->execute(['user_id' => $user_id]);
            if ($stmt_check_wali->fetchColumn() > 0) {
                throw new \Exception('Guru tidak dapat dihapus karena masih bertugas sebagai Wali Kelas.');
            }
            
            // Ambil foto profil lama untuk dihapus
            $stmt_photo = $pdo->prepare("SELECT foto_profil FROM guru_tahfidz WHERE user_id = :user_id");
            $stmt_photo->execute(['user_id' => $user_id]);
            $old_photo = $stmt_photo->fetchColumn();
            if ($old_photo && file_exists('../uploads/guru/' . $old_photo)) {
                @unlink('../uploads/guru/' . $old_photo);
            }

            // Ambil nama lengkap untuk log sebelum dihapus
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM users WHERE id = :user_id");
            $stmt_name->execute(['user_id' => $user_id]);
            $name = $stmt_name->fetchColumn();
            
            // Hapus user (cascade akan menghapus guru_tahfidz)
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            
            logActivity($pdo, $_SESSION['user_id'], "Menghapus akun guru tahfidz: $name");
            
            $pdo->commit();
            $success = 'Data Guru dan akun login terkait berhasil dihapus.';
        } catch (\Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal menghapus data: ' . $e->getMessage();
        }
    }
}

// Ambil data guru
$list_guru = [];
try {
    $list_guru = $pdo->query("
        SELECT g.*, u.username, u.email 
        FROM guru_tahfidz g 
        JOIN users u ON g.user_id = u.id 
        ORDER BY g.nama_lengkap ASC
    ")->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat data: ' . $e->getMessage();
}
?>

<!-- Tombol Tambah & Deskripsi -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <p style="font-size: 14px; color: var(--text-muted);">Manajemen data staf pengajar/guru tahfidz beserta akun masuk sistem.</p>
    </div>
    <button onclick="showAddModal()" class="btn btn-primary btn-sm" style="width: auto;">
        <i class="fa-solid fa-user-plus"></i> Tambah Guru
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
        <h2>Daftar Guru Tahfidz</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table-admin">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Foto</th>
                    <th>Nama Lengkap</th>
                    <th>NIP</th>
                    <th>No. HP</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_guru)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted);">Belum ada data guru tahfidz.</td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($list_guru as $guru): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td>
                                <?php if ($guru['foto_profil'] && file_exists('../uploads/guru/' . $guru['foto_profil'])): ?>
                                    <img src="../uploads/guru/<?php echo htmlspecialchars($guru['foto_profil']); ?>" alt="Foto <?php echo htmlspecialchars($guru['nama_lengkap']); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; display: block; border: 1.5px solid var(--primary-color);">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #f1f5f9; border: 1.5px solid #e2e8f0; display: flex; justify-content: center; align-items: center; color: var(--text-muted); font-size: 14px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 600; color: var(--primary-dark);"><?php echo htmlspecialchars($guru['nama_lengkap']); ?></td>
                            <td><?php echo htmlspecialchars($guru['nip'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['no_hp'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($guru['email'] ?? '-'); ?></td>
                            <td><span style="font-family: monospace; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo htmlspecialchars($guru['username']); ?></span></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-secondary btn-sm btn-edit-guru" 
                                            style="padding: 4px 8px; font-size: 11px;"
                                            data-id="<?php echo $guru['id']; ?>"
                                            data-user-id="<?php echo $guru['user_id']; ?>"
                                            data-nama="<?php echo htmlspecialchars($guru['nama_lengkap'], ENT_QUOTES); ?>"
                                            data-nip="<?php echo htmlspecialchars($guru['nip'] ?? '', ENT_QUOTES); ?>"
                                            data-no-hp="<?php echo htmlspecialchars($guru['no_hp'] ?? '', ENT_QUOTES); ?>"
                                            data-email="<?php echo htmlspecialchars($guru['email'] ?? '', ENT_QUOTES); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>
                                    
                                    <form action="guru.php" method="POST" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data guru ini? Akun login guru ini juga akan terhapus.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $guru['user_id']; ?>">
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

<!-- Modal Tambah Guru -->
<div class="modal-overlay" id="addModal">
    <div class="admin-modal admin-modal-large">
        <div class="admin-modal-header">
            <h3>Tambah Guru Tahfidz Baru</h3>
            <button class="modal-close" onclick="closeAddModal()">&times;</button>
        </div>
        <form action="guru.php" method="POST">
            <input type="hidden" name="action" value="add">
            <div class="admin-modal-body">
                <h4 style="color: var(--primary-color); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;">Profil Biodata</h4>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="nip" class="form-label">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" id="nip" name="nip" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="no_hp" class="form-label">Nomor WhatsApp/HP</label>
                        <input type="text" id="no_hp" name="no_hp" class="form-control" style="padding-left: 15px;" placeholder="Contoh: 0812...">
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control" style="padding-left: 15px;">
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

<!-- Modal Edit Guru -->
<div class="modal-overlay" id="editModal">
    <div class="admin-modal admin-modal-large">
        <div class="admin-modal-header">
            <h3>Edit Data Guru Tahfidz</h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form action="guru.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="user_id" id="edit_user_id">
            <div class="admin-modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_nama" class="form-label">Nama Lengkap</label>
                        <input type="text" id="edit_nama" name="nama_lengkap" class="form-control" style="padding-left: 15px;" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_nip" class="form-label">NIP</label>
                        <input type="text" id="edit_nip" name="nip" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>
                
                <div class="form-row" style="margin-top: 15px;">
                    <div class="form-group">
                        <label for="edit_no_hp" class="form-label">Nomor WhatsApp/HP</label>
                        <input type="text" id="edit_no_hp" name="no_hp" class="form-control" style="padding-left: 15px;">
                    </div>
                    <div class="form-group">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" id="edit_email" name="email" class="form-control" style="padding-left: 15px;">
                    </div>
                </div>

                <div class="form-row" style="margin-top: 15px;">
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
function showEditModal(id, userId, nama, nip, no_hp, email) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_nip').value = nip;
    document.getElementById('edit_no_hp').value = no_hp;
    document.getElementById('edit_email').value = email;
    document.getElementById('editModal').classList.add('show');
}
function closeEditModal() {
    document.getElementById('editModal').classList.remove('show');
}

// Delegasi event listener untuk menangani klik Edit secara aman
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-edit-guru');
    if (btn) {
        const id = btn.getAttribute('data-id');
        const userId = btn.getAttribute('data-user-id');
        const nama = btn.getAttribute('data-nama');
        const nip = btn.getAttribute('data-nip');
        const noHp = btn.getAttribute('data-no-hp');
        const email = btn.getAttribute('data-email');
        
        showEditModal(id, userId, nama, nip, noHp, email);
    }
});
</script>

</main>
</div>
</div>
</body>
</html>
