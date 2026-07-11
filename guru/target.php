<?php
// guru/target.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Migrasi DB: Tambahkan target_ayat jika belum ada
try {
    $pdo->exec("ALTER TABLE target_hafalan ADD COLUMN target_ayat VARCHAR(255) NULL AFTER target_surah");
} catch (\PDOException $e) {
    // Abaikan jika sudah ada
}

// 1. Ambil tahun ajaran aktif
$ta_aktif = null;
try {
    $stmt_ta = $pdo->query("SELECT * FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
    $ta_aktif = $stmt_ta->fetch();
} catch (\PDOException $e) {
    $error = 'Gagal memuat tahun ajaran.';
}

// 2. Ambil daftar kelas bimbingan guru
$kelas_list = [];
if ($ta_aktif) {
    try {
        $stmt_kelas = $pdo->prepare("
            SELECT k.id as kelas_id, k.nama_kelas, COUNT(s.id) as jumlah_siswa
            FROM kelas k
            LEFT JOIN siswa s ON s.kelas_id = k.id AND s.status_aktif = 'aktif'
            WHERE k.wali_kelas_id = :guru_id
            GROUP BY k.id, k.nama_kelas
            ORDER BY k.nama_kelas ASC
        ");
        $stmt_kelas->execute(['guru_id' => $guru_id]);
        $kelas_list = $stmt_kelas->fetchAll();
        
        // Ambil target (asumsikan sama untuk semua siswa di kelas, ambil satu contoh dari tiap kelas)
        foreach ($kelas_list as &$kls) {
            $stmt_tgt = $pdo->prepare("
                SELECT th.target_juz, th.target_surah, th.target_ayat, th.keterangan 
                FROM target_hafalan th
                JOIN siswa s ON th.siswa_id = s.id
                WHERE s.kelas_id = :kelas_id AND th.tahun_ajaran_id = :ta_id
                LIMIT 1
            ");
            $stmt_tgt->execute([
                'kelas_id' => $kls['kelas_id'],
                'ta_id' => $ta_aktif['id']
            ]);
            $tgt = $stmt_tgt->fetch();
            if ($tgt) {
                $kls['target_juz'] = $tgt['target_juz'];
                $kls['target_surah'] = $tgt['target_surah'];
                $kls['target_ayat'] = $tgt['target_ayat'] ?? '';
                $kls['keterangan'] = $tgt['keterangan'];
            } else {
                $kls['target_juz'] = '';
                $kls['target_surah'] = '';
                $kls['target_ayat'] = '';
                $kls['keterangan'] = '';
            }
        }
    } catch (\PDOException $e) {
        $error = 'Gagal memuat data kelas bimbingan: ' . $e->getMessage();
    }
}

// Tentukan kelas terpilih
$selected_kelas_id = intval($_GET['kelas_id'] ?? 0);
if ($selected_kelas_id <= 0 && !empty($kelas_list)) {
    $selected_kelas_id = $kelas_list[0]['kelas_id'];
}

// Ambil detail target kelas terpilih (untuk auto-fill / pre-fill target massal)
$selected_kelas_target = [
    'target_juz' => '',
    'target_surah' => '',
    'target_ayat' => '',
    'keterangan' => ''
];
foreach ($kelas_list as $kls) {
    if ($kls['kelas_id'] == $selected_kelas_id) {
        $selected_kelas_target['target_juz'] = $kls['target_juz'];
        $selected_kelas_target['target_surah'] = $kls['target_surah'];
        $selected_kelas_target['target_ayat'] = $kls['target_ayat'];
        $selected_kelas_target['keterangan'] = $kls['keterangan'];
        break;
    }
}

// 2.5 Ambil daftar siswa untuk kelas terpilih
$siswa_list = [];
if ($selected_kelas_id > 0 && $ta_aktif) {
    try {
        $stmt_siswa = $pdo->prepare("
            SELECT s.id, s.nama_lengkap, s.nisn, k.nama_kelas, 
                   th.target_juz, th.target_surah, th.target_ayat, th.keterangan
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            LEFT JOIN target_hafalan th ON s.id = th.siswa_id AND th.tahun_ajaran_id = :ta_id
            WHERE s.kelas_id = :kelas_id AND s.status_aktif = 'aktif'
            ORDER BY s.nama_lengkap ASC
        ");
        $stmt_siswa->execute([
            'ta_id' => $ta_aktif['id'],
            'kelas_id' => $selected_kelas_id
        ]);
        $siswa_list = $stmt_siswa->fetchAll();
    } catch (\PDOException $e) {
        $error = 'Gagal memuat daftar siswa: ' . $e->getMessage();
    }
}

// Handler Hapus Target Hafalan Siswa
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['siswa_id'])) {
    $del_siswa_id = intval($_GET['siswa_id']);
    $kelas_id = intval($_GET['kelas_id'] ?? 0);
    if ($ta_aktif && $del_siswa_id > 0) {
        try {
            $stmt_del = $pdo->prepare("DELETE FROM target_hafalan WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id");
            $stmt_del->execute(['siswa_id' => $del_siswa_id, 'ta_id' => $ta_aktif['id']]);
            
            // Log aktivitas
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :id");
            $stmt_name->execute(['id' => $del_siswa_id]);
            $nama_siswa = $stmt_name->fetchColumn();
            logActivity($pdo, $user_id, "Menghapus target hafalan untuk $nama_siswa");
            
            header("Location: target.php?kelas_id=" . $kelas_id . "&success=" . urlencode('Target hafalan siswa berhasil dihapus.'));
            exit;
        } catch (\PDOException $e) {
            $error = 'Gagal menghapus target hafalan: ' . $e->getMessage();
        }
    }
}

// 3. Proses simpan/update target hafalan (Massal & Individu)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_target'])) {
    $siswa_id = intval($_POST['siswa_id'] ?? 0);
    $kelas_id = intval($_POST['kelas_id'] ?? 0);
    $target_juz = trim($_POST['target_juz'] ?? '');
    $target_surah = trim($_POST['target_surah'] ?? '');
    $target_ayat = trim($_POST['target_ayat'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    if (!$ta_aktif) {
        $error = 'Tidak dapat menyimpan target karena tidak ada tahun ajaran aktif.';
    } elseif ($siswa_id > 0) {
        // Simpan target individu siswa
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM target_hafalan WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id");
            $stmt_check->execute(['siswa_id' => $siswa_id, 'ta_id' => $ta_aktif['id']]);
            $existing = $stmt_check->fetch();
            
            if ($existing) {
                // Update
                $stmt_up = $pdo->prepare("UPDATE target_hafalan SET target_juz = :juz, target_surah = :surah, target_ayat = :ayat, keterangan = :ket WHERE id = :id");
                $stmt_up->execute([
                    'juz' => $target_juz, 
                    'surah' => $target_surah, 
                    'ayat' => $target_ayat, 
                    'ket' => $keterangan, 
                    'id' => $existing['id']
                ]);
            } else {
                // Insert new
                $stmt_in = $pdo->prepare("INSERT INTO target_hafalan (siswa_id, tahun_ajaran_id, target_juz, target_surah, target_ayat, keterangan) VALUES (:siswa_id, :ta_id, :juz, :surah, :ayat, :ket)");
                $stmt_in->execute([
                    'siswa_id' => $siswa_id, 
                    'ta_id' => $ta_aktif['id'], 
                    'juz' => $target_juz, 
                    'surah' => $target_surah, 
                    'ayat' => $target_ayat, 
                    'ket' => $keterangan
                ]);
            }
            
            // Log aktivitas
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :id");
            $stmt_name->execute(['id' => $siswa_id]);
            $nama_siswa = $stmt_name->fetchColumn();
            logActivity($pdo, $user_id, "Mengatur target hafalan untuk $nama_siswa: Juz $target_juz, Surah $target_surah, Ayat $target_ayat");
            
            header("Location: target.php?kelas_id=" . $kelas_id . "&success=" . urlencode('Target hafalan siswa berhasil disimpan.'));
            exit;
        } catch (\PDOException $e) {
            $error = 'Gagal menyimpan target hafalan siswa: ' . $e->getMessage();
        }
    } elseif ($kelas_id > 0) {
        // Simpan target massal kelas
        try {
            $pdo->beginTransaction();
            
            // Ambil semua siswa aktif di kelas tersebut
            $stmt_siswa = $pdo->prepare("SELECT id FROM siswa WHERE kelas_id = :kelas_id AND status_aktif = 'aktif'");
            $stmt_siswa->execute(['kelas_id' => $kelas_id]);
            $siswas = $stmt_siswa->fetchAll();
            
            if (empty($siswas)) {
                $error = 'Tidak ada siswa aktif di kelas ini.';
                $pdo->rollBack();
            } else {
                foreach ($siswas as $s) {
                    $s_id = $s['id'];
                    $stmt_check = $pdo->prepare("SELECT id FROM target_hafalan WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id");
                    $stmt_check->execute(['siswa_id' => $s_id, 'ta_id' => $ta_aktif['id']]);
                    $existing = $stmt_check->fetch();
                    
                    if ($existing) {
                        $stmt_up = $pdo->prepare("UPDATE target_hafalan SET target_juz = :juz, target_surah = :surah, target_ayat = :ayat, keterangan = :ket WHERE id = :id");
                        $stmt_up->execute([
                            'juz' => $target_juz, 
                            'surah' => $target_surah, 
                            'ayat' => $target_ayat, 
                            'ket' => $keterangan, 
                            'id' => $existing['id']
                        ]);
                    } else {
                        $stmt_in = $pdo->prepare("INSERT INTO target_hafalan (siswa_id, tahun_ajaran_id, target_juz, target_surah, target_ayat, keterangan) VALUES (:siswa_id, :ta_id, :juz, :surah, :ayat, :ket)");
                        $stmt_in->execute([
                            'siswa_id' => $s_id, 
                            'ta_id' => $ta_aktif['id'], 
                            'juz' => $target_juz, 
                            'surah' => $target_surah, 
                            'ayat' => $target_ayat, 
                            'ket' => $keterangan
                        ]);
                    }
                }
                
                // Log aktivitas
                $stmt_name = $pdo->prepare("SELECT nama_kelas FROM kelas WHERE id = :id");
                $stmt_name->execute(['id' => $kelas_id]);
                $nama_kelas = $stmt_name->fetchColumn();
                logActivity($pdo, $user_id, "Mengatur target hafalan massal untuk Kelas $nama_kelas: Juz $target_juz, Surah $target_surah, Ayat $target_ayat");
                
                $pdo->commit();
                header("Location: target.php?kelas_id=" . $kelas_id . "&success=" . urlencode('Target hafalan kelas berhasil diterapkan ke semua siswa.'));
                exit;
            }
        } catch (\PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = 'Gagal menyimpan target hafalan kelas: ' . $e->getMessage();
        }
    } else {
        $error = 'Parameter tidak valid.';
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}
?>

<style>
/* Modal simple styling override */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 20px;
}
.modal-overlay.show {
    display: flex;
}
.modal-card {
    background-color: #ffffff;
    border-radius: 20px;
    width: 100%;
    max-width: 500px;
    border: 1px solid rgba(13, 92, 52, 0.15);
    box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
    overflow: hidden;
    animation: slideInDown 0.3s ease-out;
}
.modal-header {
    padding: 20px 24px;
    background-color: var(--primary-color);
    color: #ffffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-body {
    padding: 24px;
}
.modal-footer {
    padding: 15px 24px;
    background-color: #f8fafc;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>

<div style="margin-bottom: 25px;">
    <p style="font-size: 14px; color: var(--text-muted);">
        Kelola target pencapaian hafalan Al-Qur'an siswa bimbingan Anda untuk semester aktif saat ini.
    </p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i>
        <div><?php echo htmlspecialchars($success); ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<?php if (!$ta_aktif): ?>
    <div class="card" style="box-shadow: none; border: 1.5px solid rgba(239, 68, 68, 0.2); background-color: rgba(239, 68, 68, 0.03); width: 100%; max-width: 100%; text-align: center; padding: 50px 20px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; color: var(--error-color); margin-bottom: 15px;"></i>
        <h3 style="color: #991b1b; font-family: var(--font-heading); margin-bottom: 8px;">Tahun Ajaran Aktif Belum Diatur</h3>
        <p style="font-size: 14px; color: var(--text-muted); max-width: 450px; margin: 0 auto;">
            Administrator sistem belum mengaktifkan tahun ajaran atau semester. Kelola target hanya bisa dilakukan saat terdapat tahun ajaran yang berstatus aktif.
        </p>
    </div>
<?php else: ?>
    <!-- 1. Pilihan Kelas Dropdown -->
    <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
        <div style="flex: 1; min-width: 250px;">
            <label for="classSelect" style="font-weight: 700; color: var(--primary-dark); font-size: 14px; display: block; margin-bottom: 8px;">
                <i class="fa-solid fa-graduation-cap"></i> Pilih Kelas Bimbingan
            </label>
            <select id="classSelect" onchange="location.href='target.php?kelas_id=' + this.value" class="form-control" style="max-width: 350px; padding: 8px 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; font-family: var(--font-body);">
                <?php foreach ($kelas_list as $kls): ?>
                    <option value="<?php echo $kls['kelas_id']; ?>" <?php echo $kls['kelas_id'] == $selected_kelas_id ? 'selected' : ''; ?>>
                        Kelas <?php echo htmlspecialchars($kls['nama_kelas']); ?> (<?php echo $kls['jumlah_siswa']; ?> Siswa)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div style="background-color: #e0f2fe; color: #0369a1; font-weight: 600; padding: 8px 16px; border-radius: 8px; font-size: 13px;">
            Tahun Ajaran: <?php echo htmlspecialchars($ta_aktif['tahun']); ?> | Semester: <?php echo htmlspecialchars($ta_aktif['semester']); ?>
        </div>
    </div>

    <!-- 2. Form Target Massal untuk Kelas (Otomatis Disediakan) -->
    <?php 
    // Dapatkan nama kelas terpilih
    $selected_kelas_nama = '';
    foreach ($kelas_list as $kls) {
        if ($kls['kelas_id'] == $selected_kelas_id) {
            $selected_kelas_nama = $kls['nama_kelas'];
            break;
        }
    }
    ?>
    <div class="card" style="box-shadow: none; border: 1.5px solid rgba(13, 92, 52, 0.12); padding: 25px; margin-bottom: 25px; background-color: #fcfdfd; border-radius: 16px;">
        <h3 style="font-family: var(--font-heading); color: var(--primary-color); font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-bullseye"></i> Atur Target Massal - Kelas <?php echo htmlspecialchars($selected_kelas_nama); ?>
        </h3>
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px; line-height: 1.5;">
            Gunakan form ini untuk menyetel target hafalan secara serentak ke seluruh siswa di kelas ini. Nilai input di bawah ini otomatis terisi dengan target kelas saat ini yang sudah tersedia di database. Anda dapat menyesuaikannya lalu klik tombol Terapkan.
        </p>
        
        <form action="target.php?kelas_id=<?php echo $selected_kelas_id; ?>" method="POST">
            <input type="hidden" name="kelas_id" value="<?php echo $selected_kelas_id; ?>">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Juz</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-book-open input-icon"></i>
                        <input type="text" name="target_juz" value="<?php echo htmlspecialchars($selected_kelas_target['target_juz']); ?>" class="form-control" placeholder="Contoh: Juz 30">
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Surah</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-bookmark input-icon"></i>
                        <input type="text" name="target_surah" value="<?php echo htmlspecialchars($selected_kelas_target['target_surah']); ?>" class="form-control" placeholder="Contoh: An-Naba s/d An-Nas">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Ayat</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-list-ol input-icon"></i>
                        <input type="text" name="target_ayat" value="<?php echo htmlspecialchars($selected_kelas_target['target_ayat'] ?? ''); ?>" class="form-control" placeholder="Contoh: Ayat 1-40">
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label" style="font-size: 13px; font-weight: 600;">Catatan / Keterangan Target</label>
                <textarea name="keterangan" class="form-control" style="padding-left: 16px; height: 70px; resize: none;" placeholder="Catatan untuk seluruh siswa kelas ini..."><?php echo htmlspecialchars($selected_kelas_target['keterangan']); ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" name="save_target" class="btn btn-primary" style="width: auto; padding: 10px 24px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                    <i class="fa-solid fa-circle-check"></i> Terapkan Target Kelas
                </button>
            </div>
        </form>
    </div>

    <!-- 3. Tabel Daftar Target Hafalan Siswa -->
    <div class="admin-card-table">
        <div class="admin-card-header">
            <div>
                <h2>Daftar Target Hafalan Siswa - Kelas <?php echo htmlspecialchars($selected_kelas_nama); ?></h2>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                    Kelola target secara individual jika ada siswa yang memiliki target berbeda dari target kelas di atas.
                </p>
            </div>
        </div>
        
        <div class="table-responsive">
            <?php if (empty($siswa_list)): ?>
                <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                    <i class="fa-solid fa-users-slash" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                    Belum ada siswa aktif di kelas bimbingan ini.
                </div>
            <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th>Nama Lengkap</th>
                            <th style="text-align: center; width: 100px;">Target Juz</th>
                            <th>Target Surah</th>
                            <th>Target Ayat</th>
                            <th>Catatan Target</th>
                            <th style="text-align: center; width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($siswa_list as $row): 
                            $has_target = !empty($row['target_juz']) || !empty($row['target_surah']) || !empty($row['target_ayat']);
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                                <td>
                                    <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($row['nama_lengkap']); ?></strong>
                                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">NISN: <?php echo htmlspecialchars($row['nisn']); ?></div>
                                </td>
                                <td style="text-align: center;">
                                    <?php if (!empty($row['target_juz'])): ?>
                                        <span class="badge-status" style="background-color: #e0f2fe; color: #0369a1; font-weight: bold; font-size: 12px;">
                                            <?php echo htmlspecialchars($row['target_juz']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['target_surah'])): ?>
                                        <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($row['target_surah']); ?></strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($row['target_ayat'])): ?>
                                        <span style="font-size: 13px; font-weight: 500;"><?php echo htmlspecialchars($row['target_ayat']); ?></span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 13px;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 250px; font-size: 13px; color: var(--text-muted); line-height: 1.4;">
                                    <?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?>
                                </td>
                                <td style="text-align: center;">
                                    <div style="display: flex; gap: 8px; justify-content: center;">
                                        <button onclick="openTargetModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['nama_lengkap']); ?>', '<?php echo htmlspecialchars($row['target_juz'] ?? ''); ?>', '<?php echo htmlspecialchars($row['target_surah'] ?? ''); ?>', '<?php echo htmlspecialchars($row['target_ayat'] ?? ''); ?>', '<?php echo htmlspecialchars($row['keterangan'] ?? ''); ?>')" class="btn btn-secondary btn-sm" style="width: auto; display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; font-size: 12px;">
                                            <i class="fa-solid fa-pen-to-square"></i> Kelola
                                        </button>
                                        <?php if ($has_target): ?>
                                            <a href="target.php?action=delete&siswa_id=<?php echo $row['id']; ?>&kelas_id=<?php echo $selected_kelas_id; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus target hafalan siswa ini?')" class="btn btn-danger btn-sm" style="width: auto; display: inline-flex; align-items: center; gap: 5px; padding: 6px 12px; font-size: 12px; background-color: #ef4444; color: white; border: none; border-radius: 6px; text-decoration: none;">
                                                <i class="fa-solid fa-trash"></i> Hapus
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Target Modal -->
<div class="modal-overlay" id="targetModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700;" id="modalTitle">Atur Target Siswa</h3>
            <button onclick="closeTargetModal()" style="background: none; border: none; color: #ffffff; font-size: 20px; cursor: pointer;">&times;</button>
        </div>
        <form action="target.php?kelas_id=<?php echo $selected_kelas_id; ?>" method="POST">
            <input type="hidden" name="siswa_id" id="modalSiswaId">
            <input type="hidden" name="kelas_id" value="<?php echo $selected_kelas_id; ?>">
            <div class="modal-body">
                <div style="background-color: #f0fdf4; border: 1px solid rgba(13, 92, 52, 0.1); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Siswa</span>
                    <strong style="color: var(--primary-dark); font-size: 15px;" id="modalSiswaName">Muhammad Al-Fatih</strong>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="target_juz">Target Juz</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-book-open input-icon"></i>
                        <input type="text" name="target_juz" id="modalTargetJuz" class="form-control" placeholder="Contoh: Juz 30, Juz 29, atau 2 Juz">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="target_surah">Target Surah</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-bookmark input-icon"></i>
                        <input type="text" name="target_surah" id="modalTargetSurah" class="form-control" placeholder="Contoh: An-Naba s/d An-Nas">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="target_ayat">Target Ayat</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-list-ol input-icon"></i>
                        <input type="text" name="target_ayat" id="modalTargetAyat" class="form-control" placeholder="Contoh: Ayat 1-40">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="keterangan">Catatan / Keterangan</label>
                    <textarea name="keterangan" id="modalKeterangan" class="form-control" style="padding-left: 16px; height: 100px; resize: none;" placeholder="Catatan tambahan target untuk siswa..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" onclick="closeTargetModal()" class="btn btn-secondary btn-sm" style="width: auto; padding: 10px 20px;">Batal</button>
                <button type="submit" name="save_target" class="btn btn-primary btn-sm" style="width: auto; padding: 10px 20px;">Simpan Target</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTargetModal(siswaId, siswaName, targetJuz, targetSurah, targetAyat, keterangan) {
    document.getElementById('modalSiswaId').value = siswaId;
    document.getElementById('modalSiswaName').innerText = siswaName;
    document.getElementById('modalTargetJuz').value = targetJuz;
    document.getElementById('modalTargetSurah').value = targetSurah;
    document.getElementById('modalTargetAyat').value = targetAyat;
    document.getElementById('modalKeterangan').value = keterangan;
    document.getElementById('targetModal').classList.add('show');
}

function closeTargetModal() {
    document.getElementById('targetModal').classList.remove('show');
}
</script>

</main>
</div>
</div>
</body>
</html>
