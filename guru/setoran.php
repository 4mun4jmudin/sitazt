<?php
// guru/setoran.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Migrasi DB: Tambahkan nilai_angka jika belum ada
try {
    $pdo->exec("ALTER TABLE setoran_tahfidz ADD COLUMN nilai_angka INT NULL AFTER nilai");
} catch (\PDOException $e) {
    // Abaikan jika sudah ada
}

// Array 114 Surah Al-Qur'an
$quran_surahs = [
    "Al-Fatihah", "Al-Baqarah", "Ali 'Imran", "An-Nisa'", "Al-Ma'idah", "Al-An'am", "Al-A'raf", "Al-Anfal", "At-Taubah", "Yunus",
    "Hud", "Yusuf", "Ar-Ra'd", "Ibrahim", "Al-Hijr", "An-Nahl", "Al-Isra'", "Al-Kahf", "Maryam", "Ta-Ha",
    "Al-Anbiya'", "Al-Hajj", "Al-Mu'minun", "An-Nur", "Al-Furqan", "Asy-Syu'ara'", "An-Naml", "Al-Qasas", "Al-'Ankabut", "Ar-Rum",
    "Luqman", "As-Sajdah", "Al-Ahzab", "Saba'", "Fatir", "Ya-Sin", "As-Saffat", "Sad", "Az-Zumar", "Ghafir",
    "Fussilat", "Asy-Syura", "Az-Zukhruf", "Ad-Dukhan", "Al-Jasiyah", "Al-Ahqaf", "Muhammad", "Al-Fath", "Al-Hujurat", "Qaf",
    "Az-Zariyat", "At-Tur", "An-Najm", "Al-Qamar", "Ar-Rahman", "Al-Waqi'ah", "Al-Hadid", "Al-Mujadilah", "Al-Hasyr", "Al-Mumtahanah",
    "As-Saff", "Al-Jumu'ah", "Al-Munafiqun", "At-Taghabun", "At-Talaq", "At-Tahrim", "Al-Mulk", "Al-Qalam", "Al-Haqqah", "Al-Ma'arij",
    "Nuh", "Al-Jinn", "Al-Muzzammil", "Al-Muddassir", "Al-Qiyamah", "Al-Insan", "Al-Mursalat", "An-Naba'", "An-Nazi'at", "'Abasa",
    "At-Takwir", "Al-Infitar", "Al-Mutaffifin", "Al-Insyiqaq", "Al-Buruj", "At-Tariq", "Al-A'la", "Al-Ghasyiyah", "Al-Fajr", "Al-Balad",
    "Asy-Syams", "Al-Lail", "Ad-Duha", "Asy-Syarh", "At-Tin", "Al-'Alaq", "Al-Qadr", "Al-Bayyinah", "Az-Zalzalah", "Al-'Adiyat",
    "Al-Qari'ah", "At-Takasur", "Al-'Asr", "Al-Humazah", "Al-Fil", "Quraisy", "Al-Ma'un", "Al-Kausar", "Al-Kafirun", "An-Nasr",
    "Al-Lahab", "Al-Ikhlas", "Al-Falaq", "An-Nas"
];

// 1. Ambil daftar kelas bimbingan guru
$kelas_ids = [];
$siswa_list = [];
$kelas_data = [];
try {
    $stmt_kelas = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = :guru_id ORDER BY nama_kelas ASC");
    $stmt_kelas->execute(['guru_id' => $guru_id]);
    $kelas_data = $stmt_kelas->fetchAll();
    $kelas_ids = array_column($kelas_data, 'id');
    
    if (!empty($kelas_ids)) {
        $in_clause = implode(',', array_fill(0, count($kelas_ids), '?'));
        $stmt_siswa = $pdo->prepare("
            SELECT id, nama_lengkap, kelas_id 
            FROM siswa 
            WHERE kelas_id IN ($in_clause) AND status_aktif = 'aktif'
            ORDER BY nama_lengkap ASC
        ");
        $stmt_siswa->execute($kelas_ids);
        $siswa_list = $stmt_siswa->fetchAll();
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat daftar siswa bimbingan.';
}

// 2. Ambil siswa terpilih jika ada di URL
$preselected_siswa_id = intval($_GET['siswa_id'] ?? 0);

// 3. Simpan setoran baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_setoran'])) {
    $siswa_id = intval($_POST['siswa_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? date('Y-m-d');
    $jenis = $_POST['jenis'] ?? 'ziadah';
    $surah = $_POST['surah'] ?? '';
    $ayat_mulai = intval($_POST['ayat_mulai'] ?? 1);
    $ayat_selesai = intval($_POST['ayat_selesai'] ?? 1);
    $nilai = trim($_POST['nilai'] ?? 'A');
    $nilai_angka = isset($_POST['nilai_angka']) ? intval($_POST['nilai_angka']) : null;
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($siswa_id <= 0) {
        $error = 'Silakan pilih siswa.';
    } elseif ($surah === '') {
        $error = 'Silakan pilih atau isi nama surah.';
    } elseif ($ayat_mulai <= 0 || $ayat_selesai < $ayat_mulai) {
        $error = 'Ayat mulai dan ayat selesai tidak valid.';
    } else {
        try {
            $stmt_in = $pdo->prepare("
                INSERT INTO setoran_tahfidz (siswa_id, guru_id, tanggal, surah, ayat_mulai, ayat_selesai, jenis, nilai, nilai_angka, catatan)
                VALUES (:siswa_id, :guru_id, :tanggal, :surah, :ayat_mulai, :ayat_selesai, :jenis, :nilai, :nilai_angka, :catatan)
            ");
            $stmt_in->execute([
                'siswa_id' => $siswa_id,
                'guru_id' => $guru_id,
                'tanggal' => $tanggal,
                'surah' => $surah,
                'ayat_mulai' => $ayat_mulai,
                'ayat_selesai' => $ayat_selesai,
                'jenis' => $jenis,
                'nilai' => $nilai,
                'nilai_angka' => $nilai_angka,
                'catatan' => $catatan
            ]);
            
            // Get student name for log
            $stmt_name = $pdo->prepare("SELECT nama_lengkap FROM siswa WHERE id = :id");
            $stmt_name->execute(['id' => $siswa_id]);
            $nama_siswa = $stmt_name->fetchColumn();
            
            logActivity($pdo, $user_id, "Menginput setoran $jenis $surah (Ayat $ayat_mulai - $ayat_selesai) untuk $nama_siswa dengan nilai $nilai ($nilai_angka)");
            
            $success = "Setoran hafalan ananda $nama_siswa berhasil disimpan.";
            
            header("Location: setoran.php?success=" . urlencode($success) . ($preselected_siswa_id ? "&siswa_id=$preselected_siswa_id" : ""));
            exit;
        } catch (\PDOException $e) {
            $error = 'Gagal menyimpan setoran: ' . $e->getMessage();
        }
    }
}

// 4. Proses Hapus Setoran
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        // Verifikasi kepemilikan data setoran (harus diinput oleh guru ini)
        $stmt_check = $pdo->prepare("
            SELECT st.*, s.nama_lengkap AS nama_siswa 
            FROM setoran_tahfidz st 
            JOIN siswa s ON st.siswa_id = s.id
            WHERE st.id = :id AND st.guru_id = :guru_id
        ");
        $stmt_check->execute(['id' => $delete_id, 'guru_id' => $guru_id]);
        $setoran_data = $stmt_check->fetch();
        
        if ($setoran_data) {
            $stmt_del = $pdo->prepare("DELETE FROM setoran_tahfidz WHERE id = :id");
            $stmt_del->execute(['id' => $delete_id]);
            
            logActivity($pdo, $user_id, "Menghapus riwayat setoran {$setoran_data['jenis']} {$setoran_data['surah']} siswa {$setoran_data['nama_siswa']}");
            $success = "Riwayat setoran hafalan berhasil dihapus.";
            
            header("Location: setoran.php?success=" . urlencode($success));
            exit;
        } else {
            $error = 'Data setoran tidak ditemukan atau Anda tidak berwenang menghapusnya.';
        }
    } catch (\PDOException $e) {
        $error = 'Gagal menghapus setoran: ' . $e->getMessage();
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// 5. Ambil riwayat setoran terbaru yang dikelola guru ini
$riwayat_setoran = [];
try {
    $stmt_history = $pdo->prepare("
        SELECT st.*, s.nama_lengkap AS nama_siswa, k.nama_kelas 
        FROM setoran_tahfidz st 
        JOIN siswa s ON st.siswa_id = s.id 
        JOIN kelas k ON s.kelas_id = k.id
        WHERE st.guru_id = :guru_id
        ORDER BY st.tanggal DESC, st.id DESC 
        LIMIT 50
    ");
    $stmt_history->execute(['guru_id' => $guru_id]);
    $riwayat_setoran = $stmt_history->fetchAll();
} catch (\PDOException $e) {
    $error = 'Gagal memuat riwayat setoran.';
}
?>

<div style="margin-bottom: 25px;">
    <p style="font-size: 14px; color: var(--text-muted);">
        Catat setoran hafalan baru (Ziadah / Murajaah) dari siswa halaqah dan pantau log riwayat setoran terakhir yang Anda masukkan.
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

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; align-items: start; flex-wrap: wrap;">
    <!-- Bagian Kiri: Form Input Setoran -->
    <div>
        <div class="admin-card-table" style="padding: 24px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <h2 style="margin-bottom: 20px; font-family: var(--font-heading); font-size: 16px; color: var(--primary-dark);">
                <i class="fa-solid fa-file-pen" style="margin-right: 5px; color: var(--primary-color);"></i> Form Input Setoran
            </h2>
            
            <form action="setoran.php" method="POST">
                <!-- Pilih Kelas Bimbingan -->
                <div class="form-group">
                    <label class="form-label" for="select_kelas">Pilih Kelas</label>
                    <select id="select_kelas" class="form-control form-control-select" style="padding-left: 16px;" onchange="filterSiswaByKelas(this.value)">
                        <option value="">-- Semua Kelas --</option>
                        <?php foreach ($kelas_data as $kls): ?>
                            <option value="<?php echo $kls['id']; ?>">
                                Kelas <?php echo htmlspecialchars($kls['nama_kelas']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Cari & Pilih Siswa -->
                <div class="form-group">
                    <label class="form-label" for="siswa_id">Cari / Pilih Siswa</label>
                    <div style="margin-bottom: 8px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass input-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                        <input type="text" id="search_siswa_input" onkeyup="filterSiswaSearch()" placeholder="Ketik nama siswa untuk mencari..." class="form-control" style="font-size: 13px; padding: 8px 12px 8px 35px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;">
                    </div>
                    <select name="siswa_id" id="siswa_select" class="form-control form-control-select" style="padding-left: 16px;" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php foreach ($siswa_list as $siswa): ?>
                            <option value="<?php echo $siswa['id']; ?>" data-kelas="<?php echo $siswa['kelas_id']; ?>" <?php echo ($preselected_siswa_id === $siswa['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" for="jenis">Kategori</label>
                        <select name="jenis" class="form-control form-control-select" style="padding-left: 16px;" required>
                            <option value="ziadah">Ziadah (Baru)</option>
                            <option value="murajaah">Murajaah (Ulang)</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="tanggal">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" style="padding-left: 16px;" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="surah">Nama Surah</label>
                    <select name="surah" id="surah_select" class="form-control form-control-select" style="padding-left: 16px;" onchange="loadQuranVerses()" required>
                        <option value="">-- Pilih Surah --</option>
                        <?php foreach ($quran_surahs as $idx => $s_name): ?>
                            <option value="<?php echo htmlspecialchars($s_name); ?>" data-no="<?php echo ($idx + 1); ?>">
                                <?php echo ($idx + 1) . '. ' . htmlspecialchars($s_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" for="ayat_mulai">Ayat Mulai</label>
                        <input type="number" name="ayat_mulai" id="ayat_mulai_input" class="form-control" style="padding-left: 16px;" min="1" value="1" required>
                    </div>
                    <div>
                        <label class="form-label" for="ayat_selesai">Ayat Selesai</label>
                        <input type="number" name="ayat_selesai" id="ayat_selesai_input" class="form-control" style="padding-left: 16px;" min="1" value="1" required>
                    </div>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 10px; margin-bottom: 20px;">
                    <div>
                        <label class="form-label" for="nilai_angka">Skor Angka (0-100)</label>
                        <input type="number" id="nilai_angka_input" name="nilai_angka" class="form-control" style="padding-left: 16px;" min="0" max="100" placeholder="Skor: 0-100" oninput="updateCalculatedGrade()" required>
                    </div>
                    <div>
                        <label class="form-label" for="nilai_display">Predikat Kelancaran</label>
                        <input type="text" id="nilai_display" class="form-control" style="padding-left: 16px; font-weight: bold; background-color: #f1f5f9; color: var(--primary-color);" readonly placeholder="-">
                        <input type="hidden" name="nilai" id="nilai_letter_input">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="catatan">Catatan / Evaluasi Pengajar</label>
                    <textarea name="catatan" class="form-control" style="padding-left: 16px; height: 80px; resize: none;" placeholder="Catatan perbaikan tajwid, kelancaran, makhraj..."></textarea>
                </div>
                
                <button type="submit" name="submit_setoran" class="btn btn-primary" style="margin-top: 10px;">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Setoran
                </button>
            </form>
        </div>
    </div>
    
    <!-- Bagian Kanan: Tampilan Ayat Al-Qur'an -->
    <div>
        <div id="quranPreviewCard" class="admin-card-table" style="padding: 24px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); border-radius: 12px; background-color: #ffffff; min-height: 480px;">
            <h2 style="margin-bottom: 15px; font-family: var(--font-heading); font-size: 16px; color: var(--primary-dark); display: flex; align-items: center; justify-content: space-between;">
                <span><i class="fa-solid fa-book-quran" style="margin-right: 5px; color: var(--primary-color);"></i> Tampilan Ayat Al-Qur'an</span>
                <span id="quranSurahTitle" style="font-size: 13px; font-weight: 600; color: var(--primary-color); font-style: italic;"></span>
            </h2>
            
            <div id="quranVersesPlaceholder" style="text-align: center; padding: 100px 20px; color: var(--text-muted);">
                <i class="fa-solid fa-book-open" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                <p style="font-size: 14px; font-weight: 600; color: var(--text-main); margin: 0 0 5px 0;">Belum Ada Surah Yang Dipilih</p>
                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Silakan pilih nama surah dan masukkan nomor ayat pada form di sebelah kiri untuk melihat ayat Al-Qur'an.</p>
            </div>
            
            <div id="quranVersesLoading" style="text-align: center; padding: 100px 20px; display: none;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 32px; color: var(--primary-color); margin-bottom: 12px;"></i>
                <p style="font-size: 13px; color: var(--text-muted); margin: 0;">Mengambil data ayat dari API e-Quran...</p>
            </div>
            
            <div id="quranVersesContent" style="max-height: 550px; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 18px; display: none;">
                <!-- Verses will be inserted here -->
            </div>
        </div>
    </div>
</div>

<style>
.quran-verse-item {
    border-bottom: 1.5px solid #f1f5f9;
    padding-bottom: 12px;
}
.quran-verse-arabic {
    font-size: 22px;
    line-height: 2.2;
    text-align: right;
    direction: rtl;
    font-family: 'Scheherazade', 'Amiri', 'Traditional Arabic', serif;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 500;
}
.quran-verse-meta {
    font-size: 11px;
    color: var(--primary-color);
    font-weight: 700;
    margin-left: 10px;
    display: inline-block;
    vertical-align: middle;
}
.quran-verse-translation {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.5;
    margin-top: 4px;
}
</style>

<script>
// Filter Siswa berdasarkan Kelas
function filterSiswaByKelas(kelasId) {
    var select = document.getElementById('siswa_select');
    var options = select.options;
    var searchInput = document.getElementById('search_siswa_input');
    searchInput.value = ''; // Reset keyword pencarian
    
    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (opt.value === "") {
            opt.style.display = "";
            continue;
        }
        var optKelas = opt.getAttribute('data-kelas');
        if (kelasId === "" || optKelas === kelasId) {
            opt.style.display = "";
        } else {
            opt.style.display = "none";
        }
    }
    select.value = ""; // Reset pilihan siswa
}

// Cari Siswa secara instan
function filterSiswaSearch() {
    var searchVal = document.getElementById('search_siswa_input').value.toLowerCase();
    var kelasId = document.getElementById('select_kelas').value;
    var select = document.getElementById('siswa_select');
    var options = select.options;
    
    for (var i = 0; i < options.length; i++) {
        var opt = options[i];
        if (opt.value === "") {
            opt.style.display = "";
            continue;
        }
        
        var optKelas = opt.getAttribute('data-kelas');
        var name = opt.text.toLowerCase();
        
        var matchesKelas = (kelasId === "" || optKelas === kelasId);
        var matchesSearch = name.includes(searchVal);
        
        if (matchesKelas && matchesSearch) {
            opt.style.display = "";
        } else {
            opt.style.display = "none";
        }
    }
}

// Penampil Ayat Al-Qur'an Dinamis
let currentSurahData = null;

async function loadQuranVerses() {
    const surahSelect = document.getElementById('surah_select');
    const selectedOption = surahSelect.options[surahSelect.selectedIndex];
    const surahNo = selectedOption.getAttribute('data-no');
    const surahName = selectedOption.value;
    
    const placeholder = document.getElementById('quranVersesPlaceholder');
    const loading = document.getElementById('quranVersesLoading');
    const content = document.getElementById('quranVersesContent');
    const title = document.getElementById('quranSurahTitle');
    
    if (!surahNo || surahName === '') {
        placeholder.style.display = 'block';
        loading.style.display = 'none';
        content.style.display = 'none';
        title.innerText = '';
        currentSurahData = null;
        return;
    }
    
    placeholder.style.display = 'none';
    loading.style.display = 'block';
    content.style.display = 'none';
    title.innerText = surahName;
    
    try {
        const response = await fetch(`https://equran.id/api/v2/surat/${surahNo}`);
        if (!response.ok) throw new Error('Gagal mengambil data ayat');
        const json = await response.json();
        
        if (json.code === 200 && json.data) {
            currentSurahData = json.data;
            displayVerses();
        } else {
            throw new Error(json.message || 'Respons API tidak valid');
        }
    } catch (err) {
        placeholder.style.display = 'none';
        loading.style.display = 'none';
        content.style.display = 'block';
        content.innerHTML = `<p style="font-size: 13px; color: #ef4444; text-align: center; margin: 50px 0;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; display: block; margin-bottom: 10px;"></i> Gagal memuat ayat Al-Qur'an: ${err.message}.
        </p>`;
    }
}

function displayVerses() {
    const placeholder = document.getElementById('quranVersesPlaceholder');
    const loading = document.getElementById('quranVersesLoading');
    const content = document.getElementById('quranVersesContent');
    
    if (!currentSurahData) return;
    
    const ayatMulai = parseInt(document.getElementById('ayat_mulai_input').value) || 1;
    const ayatSelesai = parseInt(document.getElementById('ayat_selesai_input').value) || 1;
    
    placeholder.style.display = 'none';
    loading.style.display = 'none';
    content.style.display = 'flex';
    content.innerHTML = '';
    
    // Saring ayat berdasarkan rentang pilihan
    const verses = currentSurahData.ayat.filter(v => v.nomorAyat >= ayatMulai && v.nomorAyat <= ayatSelesai);
    
    if (verses.length === 0) {
        content.innerHTML = `<p style="font-size: 13px; color: var(--text-muted); text-align: center; margin: 50px 0;">Tidak ada ayat pada rentang terpilih. Jumlah total ayat surat ini: ${currentSurahData.jumlahAyat}.</p>`;
        return;
    }
    
    verses.forEach(v => {
        const item = document.createElement('div');
        item.className = 'quran-verse-item';
        
        item.innerHTML = `
            <div class="quran-verse-arabic">
                <span class="quran-verse-meta">(${v.nomorAyat})</span> ${v.teksArab}
            </div>
            <div class="quran-verse-translation" style="font-style: italic; color: #64748b; margin-bottom: 2px;">
                ${v.teksLatin}
            </div>
            <div class="quran-verse-translation">
                ${v.teksIndonesia}
            </div>
        `;
        content.appendChild(item);
    });
}

// Kalkulasi Predikat Kelancaran berdasarkan Skor Angka
function updateCalculatedGrade() {
    const scoreInput = document.getElementById('nilai_angka_input');
    const score = parseInt(scoreInput.value);
    const letterInput = document.getElementById('nilai_letter_input');
    const displayInput = document.getElementById('nilai_display');
    
    if (isNaN(score) || scoreInput.value === "") {
        letterInput.value = '';
        displayInput.value = '';
        return;
    }
    
    let letter = '';
    let text = '';
    
    if (score >= 90) {
        letter = 'A+';
        text = 'Sangat Lancar';
    } else if (score >= 85) {
        letter = 'A';
        text = 'Lancar Terbata-bata';
    } else if (score >= 80) {
        letter = 'A-';
        text = 'Cukup Lancar';
    } else if (score >= 75) {
        letter = 'B+';
        text = 'Lancar Dengan Sedikit Bantuan';
    } else if (score >= 70) {
        letter = 'B';
        text = 'Lancar Dengan Bantuan';
    } else if (score >= 65) {
        letter = 'B-';
        text = 'Lancar Dengan Banyak Bantuan';
    } else if (score >= 60) {
        letter = 'C';
        text = 'Tidak Lancar';
    } else {
        letter = 'C-';
        text = 'Ulangi';
    }
    
    letterInput.value = letter;
    displayInput.value = `${letter} (${text})`;
}

// Hubungkan input ayat dengan preview loader
document.getElementById('ayat_mulai_input').addEventListener('input', displayVerses);
document.getElementById('ayat_selesai_input').addEventListener('input', displayVerses);
</script>

</main>
</div>
</div>
</body>
</html>
