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

$quran_surahs = [
    ["no" => 1, "name" => "Al-Fatihah", "verses" => 7],
    ["no" => 2, "name" => "Al-Baqarah", "verses" => 286],
    ["no" => 3, "name" => "Ali 'Imran", "verses" => 200],
    ["no" => 4, "name" => "An-Nisa'", "verses" => 176],
    ["no" => 5, "name" => "Al-Ma'idah", "verses" => 120],
    ["no" => 6, "name" => "Al-An'am", "verses" => 165],
    ["no" => 7, "name" => "Al-A'raf", "verses" => 206],
    ["no" => 8, "name" => "Al-Anfal", "verses" => 75],
    ["no" => 9, "name" => "At-Taubah", "verses" => 129],
    ["no" => 10, "name" => "Yunus", "verses" => 109],
    ["no" => 11, "name" => "Hud", "verses" => 123],
    ["no" => 12, "name" => "Yusuf", "verses" => 111],
    ["no" => 13, "name" => "Ar-Ra'd", "verses" => 43],
    ["no" => 14, "name" => "Ibrahim", "verses" => 52],
    ["no" => 15, "name" => "Al-Hijr", "verses" => 99],
    ["no" => 16, "name" => "An-Nahl", "verses" => 128],
    ["no" => 17, "name" => "Al-Isra'", "verses" => 111],
    ["no" => 18, "name" => "Al-Kahf", "verses" => 110],
    ["no" => 19, "name" => "Maryam", "verses" => 98],
    ["no" => 20, "name" => "Ta-Ha", "verses" => 135],
    ["no" => 21, "name" => "Al-Anbiya'", "verses" => 112],
    ["no" => 22, "name" => "Al-Hajj", "verses" => 78],
    ["no" => 23, "name" => "Al-Mu'minun", "verses" => 118],
    ["no" => 24, "name" => "An-Nur", "verses" => 64],
    ["no" => 25, "name" => "Al-Furqan", "verses" => 77],
    ["no" => 26, "name" => "Asy-Syu'ara'", "verses" => 227],
    ["no" => 27, "name" => "An-Naml", "verses" => 93],
    ["no" => 28, "name" => "Al-Qasas", "verses" => 88],
    ["no" => 29, "name" => "Al-'Ankabut", "verses" => 69],
    ["no" => 30, "name" => "Ar-Rum", "verses" => 60],
    ["no" => 31, "name" => "Luqman", "verses" => 34],
    ["no" => 32, "name" => "As-Sajdah", "verses" => 30],
    ["no" => 33, "name" => "Al-Ahzab", "verses" => 73],
    ["no" => 34, "name" => "Saba'", "verses" => 54],
    ["no" => 35, "name" => "Fatir", "verses" => 45],
    ["no" => 36, "name" => "Ya-Sin", "verses" => 83],
    ["no" => 37, "name" => "As-Saffat", "verses" => 182],
    ["no" => 38, "name" => "Sad", "verses" => 88],
    ["no" => 39, "name" => "Az-Zumar", "verses" => 75],
    ["no" => 40, "name" => "Ghafir", "verses" => 85],
    ["no" => 41, "name" => "Fussilat", "verses" => 54],
    ["no" => 42, "name" => "Asy-Syura", "verses" => 53],
    ["no" => 43, "name" => "Az-Zukhruf", "verses" => 89],
    ["no" => 44, "name" => "Ad-Dukhan", "verses" => 59],
    ["no" => 45, "name" => "Al-Jasiyah", "verses" => 37],
    ["no" => 46, "name" => "Al-Ahqaf", "verses" => 35],
    ["no" => 47, "name" => "Muhammad", "verses" => 38],
    ["no" => 48, "name" => "Al-Fath", "verses" => 29],
    ["no" => 49, "name" => "Al-Hujurat", "verses" => 18],
    ["no" => 50, "name" => "Qaf", "verses" => 45],
    ["no" => 51, "name" => "Az-Zariyat", "verses" => 60],
    ["no" => 52, "name" => "At-Tur", "verses" => 49],
    ["no" => 53, "name" => "An-Najm", "verses" => 62],
    ["no" => 54, "name" => "Al-Qamar", "verses" => 55],
    ["no" => 55, "name" => "Ar-Rahman", "verses" => 78],
    ["no" => 56, "name" => "Al-Waqi'ah", "verses" => 96],
    ["no" => 57, "name" => "Al-Hadid", "verses" => 29],
    ["no" => 58, "name" => "Al-Mujadilah", "verses" => 22],
    ["no" => 59, "name" => "Al-Hasyr", "verses" => 24],
    ["no" => 60, "name" => "Al-Mumtahanah", "verses" => 13],
    ["no" => 61, "name" => "As-Saff", "verses" => 14],
    ["no" => 62, "name" => "Al-Jumu'ah", "verses" => 11],
    ["no" => 63, "name" => "Al-Munafiqun", "verses" => 11],
    ["no" => 64, "name" => "At-Taghabun", "verses" => 18],
    ["no" => 65, "name" => "At-Talaq", "verses" => 12],
    ["no" => 66, "name" => "At-Tahrim", "verses" => 12],
    ["no" => 67, "name" => "Al-Mulk", "verses" => 30],
    ["no" => 68, "name" => "Al-Qalam", "verses" => 52],
    ["no" => 69, "name" => "Al-Haqqah", "verses" => 52],
    ["no" => 70, "name" => "Al-Ma'arij", "verses" => 44],
    ["no" => 71, "name" => "Nuh", "verses" => 28],
    ["no" => 72, "name" => "Al-Jinn", "verses" => 28],
    ["no" => 73, "name" => "Al-Muzzammil", "verses" => 20],
    ["no" => 74, "name" => "Al-Muddassir", "verses" => 56],
    ["no" => 75, "name" => "Al-Qiyamah", "verses" => 40],
    ["no" => 76, "name" => "Al-Insan", "verses" => 31],
    ["no" => 77, "name" => "Al-Mursalat", "verses" => 50],
    ["no" => 78, "name" => "An-Naba'", "verses" => 40],
    ["no" => 79, "name" => "An-Nazi'at", "verses" => 46],
    ["no" => 80, "name" => "'Abasa", "verses" => 42],
    ["no" => 81, "name" => "At-Takwir", "verses" => 29],
    ["no" => 82, "name" => "Al-Infitar", "verses" => 19],
    ["no" => 83, "name" => "Al-Mutaffifin", "verses" => 36],
    ["no" => 84, "name" => "Al-Insyiqaq", "verses" => 25],
    ["no" => 85, "name" => "Al-Buruj", "verses" => 22],
    ["no" => 86, "name" => "At-Tariq", "verses" => 17],
    ["no" => 87, "name" => "Al-A'la", "verses" => 19],
    ["no" => 88, "name" => "Al-Ghasyiyah", "verses" => 26],
    ["no" => 89, "name" => "Al-Fajr", "verses" => 30],
    ["no" => 90, "name" => "Al-Balad", "verses" => 20],
    ["no" => 91, "name" => "Asy-Syams", "verses" => 15],
    ["no" => 92, "name" => "Al-Lail", "verses" => 21],
    ["no" => 93, "name" => "Ad-Duha", "verses" => 11],
    ["no" => 94, "name" => "Asy-Syarh", "verses" => 8],
    ["no" => 95, "name" => "At-Tin", "verses" => 8],
    ["no" => 96, "name" => "Al-'Alaq", "verses" => 19],
    ["no" => 97, "name" => "Al-Qadr", "verses" => 5],
    ["no" => 98, "name" => "Al-Bayyinah", "verses" => 8],
    ["no" => 99, "name" => "Az-Zalzalah", "verses" => 8],
    ["no" => 100, "name" => "Al-'Adiyat", "verses" => 11],
    ["no" => 101, "name" => "Al-Qari'ah", "verses" => 11],
    ["no" => 102, "name" => "At-Takasur", "verses" => 8],
    ["no" => 103, "name" => "Al-'Asr", "verses" => 3],
    ["no" => 104, "name" => "Al-Humazah", "verses" => 9],
    ["no" => 105, "name" => "Al-Fil", "verses" => 5],
    ["no" => 106, "name" => "Quraisy", "verses" => 4],
    ["no" => 107, "name" => "Al-Ma'un", "verses" => 7],
    ["no" => 108, "name" => "Al-Kausar", "verses" => 3],
    ["no" => 109, "name" => "Al-Kafirun", "verses" => 6],
    ["no" => 110, "name" => "An-Nasr", "verses" => 3],
    ["no" => 111, "name" => "Al-Masad", "verses" => 5],
    ["no" => 112, "name" => "Al-Ikhlas", "verses" => 4],
    ["no" => 113, "name" => "Al-Falaq", "verses" => 5],
    ["no" => 114, "name" => "An-Nas", "verses" => 6]
];

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
                        <select name="target_juz" id="massTargetJuz" class="form-control" onchange="onMassJuzChange()">
                            <option value="">Pilih Juz</option>
                            <?php for ($i = 1; $i <= 30; $i++): ?>
                                <option value="Juz <?php echo $i; ?>" <?php echo ($selected_kelas_target['target_juz'] == "Juz $i" || $selected_kelas_target['target_juz'] == $i) ? 'selected' : ''; ?>>Juz <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Surah</label>
                    <div class="input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                        <i class="fa-solid fa-bookmark input-icon"></i>
                        <select id="massTargetSurahMulai" class="form-control" onchange="onMassSurahMulaiChange()">
                            <option value="">Pilih Surah Mulai</option>
                        </select>
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">s/d</span>
                        <select id="massTargetSurahSelesai" class="form-control" style="padding-left: 16px;" onchange="onMassSurahSelesaiChange()">
                            <option value="">Pilih Surah Selesai</option>
                        </select>
                        <input type="hidden" name="target_surah" id="massTargetSurah" value="<?php echo htmlspecialchars($selected_kelas_target['target_surah'] ?? ''); ?>">
                        <input type="hidden" id="massTargetSurahHiddenVal" value="<?php echo htmlspecialchars($selected_kelas_target['target_surah'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 0;" id="massAyatGroup">
                    <label class="form-label" style="font-size: 13px; font-weight: 600;">Target Ayat</label>
                    <div class="input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                        <input type="number" id="massAyatMulai" class="form-control" placeholder="Mulai" style="width: 80px; padding-left: 10px;" min="1" oninput="validateMassAyatRange()">
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">s/d</span>
                        <input type="number" id="massAyatSelesai" class="form-control" placeholder="Selesai" style="width: 80px; padding-left: 10px;" min="1" oninput="validateMassAyatRange()">
                        <input type="hidden" name="target_ayat" id="massTargetAyat" value="<?php echo htmlspecialchars($selected_kelas_target['target_ayat'] ?? ''); ?>">
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
                        <select name="target_juz" id="modalTargetJuz" class="form-control" onchange="onModalJuzChange()">
                            <option value="">Pilih Juz</option>
                            <?php for ($i = 1; $i <= 30; $i++): ?>
                                <option value="Juz <?php echo $i; ?>">Juz <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="target_surah">Target Surah</label>
                    <div class="input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                        <i class="fa-solid fa-bookmark input-icon"></i>
                        <select id="modalTargetSurahMulai" class="form-control" onchange="onModalSurahMulaiChange()">
                            <option value="">Pilih Surah Mulai</option>
                        </select>
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">s/d</span>
                        <select id="modalTargetSurahSelesai" class="form-control" style="padding-left: 16px;" onchange="onModalSurahSelesaiChange()">
                            <option value="">Pilih Surah Selesai</option>
                        </select>
                        <input type="hidden" name="target_surah" id="modalTargetSurah">
                    </div>
                </div>

                <div class="form-group" id="modalAyatGroup">
                    <label class="form-label" for="target_ayat">Target Ayat</label>
                    <div class="input-wrapper" style="display: flex; gap: 8px; align-items: center;">
                        <input type="number" id="modalAyatMulai" class="form-control" placeholder="Mulai" style="width: 80px; padding-left: 10px;" min="1" oninput="validateModalAyatRange()">
                        <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">s/d</span>
                        <input type="number" id="modalAyatSelesai" class="form-control" placeholder="Selesai" style="width: 80px; padding-left: 10px;" min="1" oninput="validateModalAyatRange()">
                        <input type="hidden" name="target_ayat" id="modalTargetAyat">
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
const quranSurahs = <?php echo json_encode($quran_surahs); ?>;

const juzToSurahs = {
    1: [1, 2],
    2: [2],
    3: [2, 3],
    4: [3, 4],
    5: [4],
    6: [4, 5],
    7: [5, 6],
    8: [6, 7],
    9: [7, 8],
    10: [8, 9],
    11: [9, 10, 11],
    12: [11, 12],
    13: [12, 13, 14],
    14: [15, 16],
    15: [17, 18],
    16: [18, 19, 20],
    17: [21, 22],
    18: [23, 24, 25],
    19: [25, 26, 27],
    20: [27, 28, 29],
    21: [29, 30, 31, 32, 33],
    22: [33, 34, 35, 36],
    23: [36, 37, 38, 39],
    24: [39, 40, 41],
    25: [41, 42, 43, 44, 45],
    26: [46, 47, 48, 49, 50, 51],
    27: [51, 52, 53, 54, 55, 56, 57],
    28: [58, 59, 60, 61, 62, 63, 64, 65, 66],
    29: [67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77],
    30: [78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114]
};

function parseSurahRange(surahStr) {
    if (!surahStr) return { mulai: '', selesai: '' };
    const parts = surahStr.split(/\s+s\/d\s+/i);
    if (parts.length === 2) {
        return { mulai: parts[0].trim(), selesai: parts[1].trim() };
    } else {
        return { mulai: surahStr.trim(), selesai: surahStr.trim() };
    }
}

function parseAyatRange(ayatStr) {
    if (!ayatStr) return { mulai: '', selesai: '' };
    ayatStr = ayatStr.replace(/ayat/i, '').trim();
    const parts = ayatStr.split('-');
    if (parts.length === 2) {
        return { mulai: parseInt(parts[0]) || '', selesai: parseInt(parts[1]) || '' };
    } else if (parts.length === 1 && parts[0]) {
        const val = parseInt(parts[0]);
        return { mulai: val || '', selesai: val || '' };
    }
    return { mulai: '', selesai: '' };
}

function populateSurahDropdowns(prefix, selectedJuz, currentSelectedMulai, currentSelectedSelesai) {
    const selectMulai = document.getElementById(prefix + 'SurahMulai');
    const selectSelesai = document.getElementById(prefix + 'SurahSelesai');
    if (!selectMulai || !selectSelesai) return;
    
    const valMulaiToKeep = currentSelectedMulai || selectMulai.value;
    const valSelesaiToKeep = currentSelectedSelesai || selectSelesai.value;
    
    selectMulai.innerHTML = '<option value="">Pilih Surah Mulai</option>';
    selectSelesai.innerHTML = '<option value="">Pilih Surah Selesai</option>';
    
    let allowedNos = null;
    if (selectedJuz) {
        const juzNum = parseInt(selectedJuz.replace(/juz/i, '').trim());
        allowedNos = juzToSurahs[juzNum] || null;
    }
    
    quranSurahs.forEach(surah => {
        if (!allowedNos || allowedNos.includes(surah.no)) {
            const opt = document.createElement('option');
            opt.value = surah.name;
            opt.setAttribute('data-no', surah.no);
            opt.setAttribute('data-verses', surah.verses);
            opt.textContent = surah.no + '. ' + surah.name + ' (' + surah.verses + ' Ayat)';
            if (surah.name === valMulaiToKeep) {
                opt.selected = true;
            }
            selectMulai.appendChild(opt);
        }
    });
    
    updateSelesaiDropdown(prefix, selectedJuz, valSelesaiToKeep);
}

function updateSelesaiDropdown(prefix, selectedJuz, valSelesaiToKeep) {
    const selectMulai = document.getElementById(prefix + 'SurahMulai');
    const selectSelesai = document.getElementById(prefix + 'SurahSelesai');
    if (!selectMulai || !selectSelesai) return;
    
    const selectedMulaiOption = selectMulai.options[selectMulai.selectedIndex];
    const startSurahNum = selectedMulaiOption ? parseInt(selectedMulaiOption.getAttribute('data-no')) || 0 : 0;
    
    const currentValSelesai = valSelesaiToKeep || selectSelesai.value;
    selectSelesai.innerHTML = '<option value="">Pilih Surah Selesai</option>';
    
    let allowedNos = null;
    if (selectedJuz) {
        const juzNum = parseInt(selectedJuz.replace(/juz/i, '').trim());
        allowedNos = juzToSurahs[juzNum] || null;
    }
    
    quranSurahs.forEach(surah => {
        const matchJuz = !allowedNos || allowedNos.includes(surah.no);
        const matchStart = !startSurahNum || surah.no >= startSurahNum;
        
        if (matchJuz && matchStart) {
            const opt = document.createElement('option');
            opt.value = surah.name;
            opt.setAttribute('data-no', surah.no);
            opt.setAttribute('data-verses', surah.verses);
            opt.textContent = surah.no + '. ' + surah.name + ' (' + surah.verses + ' Ayat)';
            if (surah.name === currentValSelesai) {
                opt.selected = true;
            }
            selectSelesai.appendChild(opt);
        }
    });
}

function openTargetModal(siswaId, siswaName, targetJuz, targetSurah, targetAyat, keterangan) {
    document.getElementById('modalSiswaId').value = siswaId;
    document.getElementById('modalSiswaName').innerText = siswaName;
    
    let normalizedJuz = '';
    if (targetJuz) {
        if (targetJuz.toLowerCase().includes('juz')) {
            normalizedJuz = targetJuz.charAt(0).toUpperCase() + targetJuz.slice(1).toLowerCase();
        } else {
            normalizedJuz = 'Juz ' + targetJuz.trim();
        }
    }
    document.getElementById('modalTargetJuz').value = normalizedJuz;
    
    // Parse range of Surahs
    const surahRange = parseSurahRange(targetSurah);
    populateSurahDropdowns('modalTarget', normalizedJuz, surahRange.mulai, surahRange.selesai);
    
    document.getElementById('modalTargetSurah').value = targetSurah;
    document.getElementById('modalKeterangan').value = keterangan;
    
    const range = parseAyatRange(targetAyat);
    document.getElementById('modalAyatMulai').value = range.mulai;
    document.getElementById('modalAyatSelesai').value = range.selesai;
    document.getElementById('modalTargetAyat').value = targetAyat;
    
    updateModalAyatLimit();
    document.getElementById('targetModal').classList.add('show');
}

function closeTargetModal() {
    document.getElementById('targetModal').classList.remove('show');
}

function onModalJuzChange() {
    const juzVal = document.getElementById('modalTargetJuz').value;
    populateSurahDropdowns('modalTarget', juzVal);
    updateModalSurahHidden();
    updateModalAyatLimit();
}

function onModalSurahMulaiChange() {
    const selectMulai = document.getElementById('modalTargetSurahMulai');
    const juzVal = document.getElementById('modalTargetJuz').value;
    
    updateSelesaiDropdown('modalTarget', juzVal);
    
    const selectSelesai = document.getElementById('modalTargetSurahSelesai');
    if (!selectSelesai.value) {
        selectSelesai.value = selectMulai.value;
    }
    
    updateModalSurahHidden();
    updateModalAyatLimit();
}

function onModalSurahSelesaiChange() {
    updateModalSurahHidden();
    updateModalAyatLimit();
}

function updateModalSurahHidden() {
    const mulai = document.getElementById('modalTargetSurahMulai').value;
    const selesai = document.getElementById('modalTargetSurahSelesai').value;
    const hidden = document.getElementById('modalTargetSurah');
    
    if (mulai && selesai) {
        hidden.value = mulai === selesai ? mulai : mulai + ' s/d ' + selesai;
    } else if (mulai) {
        hidden.value = mulai;
    } else {
        hidden.value = '';
    }
}

// Modal fields logic
function updateModalAyatLimit() {
    const selectMulai = document.getElementById('modalTargetSurahMulai').value;
    const selectSelesai = document.getElementById('modalTargetSurahSelesai').value;
    const group = document.getElementById('modalAyatGroup');
    const hidden = document.getElementById('modalTargetAyat');
    
    if (selectMulai && selectSelesai && selectMulai !== selectSelesai) {
        // Hide target ayat group if multiple surahs are selected
        group.style.display = 'none';
        hidden.value = '';
        document.getElementById('modalAyatMulai').value = '';
        document.getElementById('modalAyatSelesai').value = '';
        return;
    }
    
    // Show target ayat group if single surah is selected (or none)
    group.style.display = 'block';
    
    const select = document.getElementById('modalTargetSurahMulai');
    const selectedOption = select.options[select.selectedIndex];
    const maxVerses = selectedOption ? selectedOption.getAttribute('data-verses') : null;
    
    const mulaiInput = document.getElementById('modalAyatMulai');
    const selesaiInput = document.getElementById('modalAyatSelesai');
    
    if (maxVerses) {
        mulaiInput.max = maxVerses;
        selesaiInput.max = maxVerses;
        
        if (!mulaiInput.value) mulaiInput.value = 1;
        if (!selesaiInput.value) selesaiInput.value = maxVerses;
        
        if (parseInt(mulaiInput.value) > parseInt(maxVerses)) mulaiInput.value = maxVerses;
        if (parseInt(selesaiInput.value) > parseInt(maxVerses)) selesaiInput.value = maxVerses;
    } else {
        mulaiInput.removeAttribute('max');
        selesaiInput.removeAttribute('max');
    }
    updateModalTargetAyatHidden();
}

function validateModalAyatRange() {
    const select = document.getElementById('modalTargetSurahMulai');
    const selectedOption = select.options[select.selectedIndex];
    const maxVerses = selectedOption ? (parseInt(selectedOption.getAttribute('data-verses')) || 0) : 0;
    
    const mulaiInput = document.getElementById('modalAyatMulai');
    const selesaiInput = document.getElementById('modalAyatSelesai');
    
    let mulai = parseInt(mulaiInput.value) || 0;
    let selesai = parseInt(selesaiInput.value) || 0;
    
    if (maxVerses > 0) {
        if (mulai > maxVerses) {
            mulaiInput.value = maxVerses;
            mulai = maxVerses;
        }
        if (selesai > maxVerses) {
            selesaiInput.value = maxVerses;
            selesai = maxVerses;
        }
    }
    
    if (mulai > 0 && selesai > 0 && mulai > selesai) {
        selesaiInput.value = mulai;
    }
    
    updateModalTargetAyatHidden();
}

function updateModalTargetAyatHidden() {
    const mulai = document.getElementById('modalAyatMulai').value;
    const selesai = document.getElementById('modalAyatSelesai').value;
    const hidden = document.getElementById('modalTargetAyat');
    
    if (mulai && selesai) {
        hidden.value = mulai === selesai ? mulai : mulai + '-' + selesai;
    } else if (mulai) {
        hidden.value = mulai;
    } else {
        hidden.value = '';
    }
}

function onMassJuzChange() {
    const juzVal = document.getElementById('massTargetJuz').value;
    populateSurahDropdowns('massTarget', juzVal);
    updateMassSurahHidden();
    updateMassAyatLimit();
}

function onMassSurahMulaiChange() {
    const selectMulai = document.getElementById('massTargetSurahMulai');
    const juzVal = document.getElementById('massTargetJuz').value;
    
    updateSelesaiDropdown('massTarget', juzVal);
    
    const selectSelesai = document.getElementById('massTargetSurahSelesai');
    if (!selectSelesai.value) {
        selectSelesai.value = selectMulai.value;
    }
    
    updateMassSurahHidden();
    updateMassAyatLimit();
}

function onMassSurahSelesaiChange() {
    updateMassSurahHidden();
    updateMassAyatLimit();
}

function updateMassSurahHidden() {
    const mulai = document.getElementById('massTargetSurahMulai').value;
    const selesai = document.getElementById('massTargetSurahSelesai').value;
    const hidden = document.getElementById('massTargetSurah');
    
    if (mulai && selesai) {
        hidden.value = mulai === selesai ? mulai : mulai + ' s/d ' + selesai;
    } else if (mulai) {
        hidden.value = mulai;
    } else {
        hidden.value = '';
    }
}

// Mass fields logic
function updateMassAyatLimit() {
    const selectMulai = document.getElementById('massTargetSurahMulai').value;
    const selectSelesai = document.getElementById('massTargetSurahSelesai').value;
    const group = document.getElementById('massAyatGroup');
    const hidden = document.getElementById('massTargetAyat');
    
    if (selectMulai && selectSelesai && selectMulai !== selectSelesai) {
        // Hide target ayat group if multiple surahs are selected
        group.style.display = 'none';
        hidden.value = '';
        document.getElementById('massAyatMulai').value = '';
        document.getElementById('massAyatSelesai').value = '';
        return;
    }
    
    // Show target ayat group if single surah is selected (or none)
    group.style.display = 'block';
    
    const select = document.getElementById('massTargetSurahMulai');
    const selectedOption = select.options[select.selectedIndex];
    const maxVerses = selectedOption ? selectedOption.getAttribute('data-verses') : null;
    
    const mulaiInput = document.getElementById('massAyatMulai');
    const selesaiInput = document.getElementById('massAyatSelesai');
    
    if (maxVerses) {
        mulaiInput.max = maxVerses;
        selesaiInput.max = maxVerses;
        
        if (!mulaiInput.value) mulaiInput.value = 1;
        if (!selesaiInput.value) selesaiInput.value = maxVerses;
        
        if (parseInt(mulaiInput.value) > parseInt(maxVerses)) mulaiInput.value = maxVerses;
        if (parseInt(selesaiInput.value) > parseInt(maxVerses)) selesaiInput.value = maxVerses;
    } else {
        mulaiInput.removeAttribute('max');
        selesaiInput.removeAttribute('max');
    }
    updateMassTargetAyatHidden();
}

function validateMassAyatRange() {
    const select = document.getElementById('massTargetSurahMulai');
    const selectedOption = select.options[select.selectedIndex];
    const maxVerses = selectedOption ? (parseInt(selectedOption.getAttribute('data-verses')) || 0) : 0;
    
    const mulaiInput = document.getElementById('massAyatMulai');
    const selesaiInput = document.getElementById('massAyatSelesai');
    
    let mulai = parseInt(mulaiInput.value) || 0;
    let selesai = parseInt(selesaiInput.value) || 0;
    
    if (maxVerses > 0) {
        if (mulai > maxVerses) {
            mulaiInput.value = maxVerses;
            mulai = maxVerses;
        }
        if (selesai > maxVerses) {
            selesaiInput.value = maxVerses;
            selesai = maxVerses;
        }
    }
    
    if (mulai > 0 && selesai > 0 && mulai > selesai) {
        selesaiInput.value = mulai;
    }
    
    updateMassTargetAyatHidden();
}

function updateMassTargetAyatHidden() {
    const mulai = document.getElementById('massAyatMulai').value;
    const selesai = document.getElementById('massAyatSelesai').value;
    const hidden = document.getElementById('massTargetAyat');
    
    if (mulai && selesai) {
        hidden.value = mulai === selesai ? mulai : mulai + '-' + selesai;
    } else if (mulai) {
        hidden.value = mulai;
    } else {
        hidden.value = '';
    }
}

document.addEventListener("DOMContentLoaded", function() {
    const juzVal = document.getElementById('massTargetJuz').value;
    const initialSurahVal = document.getElementById('massTargetSurahHiddenVal').value;
    
    const surahRange = parseSurahRange(initialSurahVal);
    populateSurahDropdowns('massTarget', juzVal, surahRange.mulai, surahRange.selesai);
    
    updateMassAyatLimit();
});
</script>

</main>
</div>
</div>
</body>
</html>
