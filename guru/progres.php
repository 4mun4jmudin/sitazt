<?php
// guru/progres.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$siswa_list = [];
$selected_siswa_id = 0;
$selected_siswa = null;
$riwayat_setoran = [];
$hafalan_summary = [];
$stats = [
    'ziadah' => 0,
    'murajaah' => 0,
    'last_setoran' => 'Belum ada setoran'
];
$target_info = null;

// Filter variables
$filter_tgl_mulai = trim($_GET['tgl_mulai'] ?? '');
$filter_tgl_selesai = trim($_GET['tgl_selesai'] ?? '');
$filter_surah = trim($_GET['surah'] ?? '');

try {
    // 1. Ambil daftar kelas bimbingan guru
    $stmt_kelas = $pdo->prepare("SELECT id, nama_kelas FROM kelas WHERE wali_kelas_id = :guru_id ORDER BY nama_kelas ASC");
    $stmt_kelas->execute(['guru_id' => $guru_id]);
    $kelas_list = $stmt_kelas->fetchAll();
    $kelas_ids = array_column($kelas_list, 'id');
    
    // Tentukan kelas terpilih
    $selected_kelas_id = intval($_GET['kelas_id'] ?? 0);
    if ($selected_kelas_id <= 0 && !empty($kelas_list)) {
        $selected_kelas_id = $kelas_list[0]['id'];
    }
    
    $siswa_list = [];
    if ($selected_kelas_id > 0) {
        $stmt_siswa = $pdo->prepare("
            SELECT s.*, k.nama_kelas 
            FROM siswa s 
            JOIN kelas k ON s.kelas_id = k.id
            WHERE s.kelas_id = :kelas_id AND s.status_aktif = 'aktif'
            ORDER BY s.nama_lengkap ASC
        ");
        $stmt_siswa->execute(['kelas_id' => $selected_kelas_id]);
        $siswa_list = $stmt_siswa->fetchAll();
    }
    
    // 2. Tentukan siswa terpilih
    $selected_siswa_id = intval($_GET['siswa_id'] ?? 0);
    if ($selected_siswa_id <= 0 && !empty($siswa_list)) {
        $selected_siswa_id = $siswa_list[0]['id'];
    }
    
    if ($selected_siswa_id > 0) {
        // Ambil data detail siswa
        foreach ($siswa_list as $s) {
            if ($s['id'] === $selected_siswa_id) {
                $selected_siswa = $s;
                break;
            }
        }
        
        // Jika tidak ditemukan di list (misal akses lintas kelas), fetch direct untuk keamanan jika ada relasi
        if (!$selected_siswa) {
            $stmt_s = $pdo->prepare("
                SELECT s.*, k.nama_kelas 
                FROM siswa s 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.id = :id AND k.wali_kelas_id = :guru_id AND s.status_aktif = 'aktif'
            ");
            $stmt_s->execute(['id' => $selected_siswa_id, 'guru_id' => $guru_id]);
            $selected_siswa = $stmt_s->fetch();
        }
        
        if ($selected_siswa) {
            // 3. Ambil Target Hafalan untuk tahun ajaran aktif
            $stmt_ta = $pdo->query("SELECT id, tahun, semester FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
            $ta_aktif = $stmt_ta->fetch();
            if ($ta_aktif) {
                $stmt_target = $pdo->prepare("
                    SELECT * FROM target_hafalan 
                    WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id
                ");
                $stmt_target->execute([
                    'siswa_id' => $selected_siswa_id,
                    'ta_id' => $ta_aktif['id']
                ]);
                $target_info = $stmt_target->fetch();
            }
            
            // 4. Hitung statistik setoran
            $stmt_z = $pdo->prepare("SELECT COUNT(*) FROM setoran_tahfidz WHERE siswa_id = :id AND jenis = 'ziadah'");
            $stmt_z->execute(['id' => $selected_siswa_id]);
            $stats['ziadah'] = $stmt_z->fetchColumn();
            
            $stmt_m = $pdo->prepare("SELECT COUNT(*) FROM setoran_tahfidz WHERE siswa_id = :id AND jenis = 'murajaah'");
            $stmt_m->execute(['id' => $selected_siswa_id]);
            $stats['murajaah'] = $stmt_m->fetchColumn();
            
            // Setoran terakhir
            $stmt_last = $pdo->prepare("
                SELECT surah, ayat_selesai, tanggal 
                FROM setoran_tahfidz 
                WHERE siswa_id = :id 
                ORDER BY tanggal DESC, id DESC 
                LIMIT 1
            ");
            $stmt_last->execute(['id' => $selected_siswa_id]);
            $last = $stmt_last->fetch();
            if ($last) {
                $stats['last_setoran'] = htmlspecialchars($last['surah']) . " (Ayat " . $last['ayat_selesai'] . ") - " . date('d/m/Y', strtotime($last['tanggal']));
            }
            
            // 5. Rekap Hafalan (Surah-surah yang sudah dihafal/ziadah)
            $stmt_summary = $pdo->prepare("
                SELECT surah, MIN(ayat_mulai) as ayat_min, MAX(ayat_selesai) as ayat_max, COUNT(*) as kali_setoran, MAX(tanggal) as tgl_terakhir
                FROM setoran_tahfidz 
                WHERE siswa_id = :siswa_id AND jenis = 'ziadah'
                GROUP BY surah 
                ORDER BY tgl_terakhir DESC
            ");
            $stmt_summary->execute(['siswa_id' => $selected_siswa_id]);
            $hafalan_summary = $stmt_summary->fetchAll();
            
            // 6. Riwayat progres setoran lengkap dengan filter (Periode & Surah)
            $query_hist = "
                SELECT st.*, gt.nama_lengkap AS nama_guru 
                FROM setoran_tahfidz st
                JOIN guru_tahfidz gt ON st.guru_id = gt.id
                WHERE st.siswa_id = :siswa_id
            ";
            $params_hist = ['siswa_id' => $selected_siswa_id];
            
            if ($filter_tgl_mulai !== '') {
                $query_hist .= " AND st.tanggal >= :tgl_mulai";
                $params_hist['tgl_mulai'] = $filter_tgl_mulai;
            }
            
            if ($filter_tgl_selesai !== '') {
                $query_hist .= " AND st.tanggal <= :tgl_selesai";
                $params_hist['tgl_selesai'] = $filter_tgl_selesai;
            }
            
            if ($filter_surah !== '') {
                $query_hist .= " AND st.surah LIKE :surah";
                $params_hist['surah'] = '%' . $filter_surah . '%';
            }
            
            $query_hist .= " ORDER BY st.tanggal DESC, st.id DESC";
            
            $stmt_hist = $pdo->prepare($query_hist);
            $stmt_hist->execute($params_hist);
            $riwayat_setoran = $stmt_hist->fetchAll();
        }
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat progres hafalan: ' . $e->getMessage();
}
?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 3fr; gap: 25px; align-items: start; flex-wrap: wrap;">
    <!-- Sidebar: Daftar Siswa -->
    <div>
        <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <div class="admin-card-header" style="padding: 18px 20px;">
                <h2>Pilih Kelas & Siswa</h2>
            </div>
            
            <!-- Pilihan Kelas -->
            <div style="padding: 10px 15px 5px 15px;">
                <label style="font-size: 11px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 5px;">Pilih Kelas</label>
                <select id="select_kelas" class="form-control form-control-select" style="font-size: 13px; padding: 8px; border-radius: 8px; width: 100%;" onchange="location.href='progres.php?kelas_id=' + this.value">
                    <?php foreach ($kelas_list as $kls): ?>
                        <option value="<?php echo $kls['id']; ?>" <?php echo ($selected_kelas_id === $kls['id']) ? 'selected' : ''; ?>>
                            Kelas <?php echo htmlspecialchars($kls['nama_kelas']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Pencarian Santri (Filter Santri) -->
            <div style="padding: 5px 15px 5px 15px;">
                <div class="input-wrapper" style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass input-icon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px;"></i>
                    <input type="text" id="searchSantri" onkeyup="filterSantri()" placeholder="Cari Nama Santri..." class="form-control" style="font-size: 13px; padding: 8px 12px 8px 35px; border-radius: 8px; border: 1.5px solid #e2e8f0; width: 100%;">
                </div>
            </div>

            <div style="padding: 10px; display: flex; flex-direction: column; gap: 5px; max-height: 500px; overflow-y: auto;">
                <?php if (empty($siswa_list)): ?>
                    <p style="padding: 15px; font-size: 13px; color: var(--text-muted); text-align: center;">Belum ada siswa pada kelas terpilih.</p>
                <?php else: ?>
                    <?php foreach ($siswa_list as $s): 
                        $is_active = $s['id'] === $selected_siswa_id;
                        $bg_color = $is_active ? 'var(--primary-color)' : '#f8fafc';
                        $text_color = $is_active ? '#ffffff' : 'var(--text-main)';
                        $sub_color = $is_active ? 'rgba(255,255,255,0.7)' : 'var(--text-muted)';
                        $border = $is_active ? 'none' : '1px solid #e2e8f0';
                    ?>
                        <a href="progres.php?kelas_id=<?php echo $selected_kelas_id; ?>&siswa_id=<?php echo $s['id']; ?>" class="santri-item" style="display: block; padding: 12px 15px; text-decoration: none; border-radius: 10px; background-color: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; border: <?php echo $border; ?>; transition: all 0.2s;">
                            <strong class="santri-name" style="display: block; font-size: 14px;"><?php echo htmlspecialchars($s['nama_lengkap']); ?></strong>
                            <span style="font-size: 11px; color: <?php echo $sub_color; ?>;">NISN: <?php echo htmlspecialchars($s['nisn']); ?> | <?php echo htmlspecialchars($s['nama_kelas']); ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Area Utama Progres -->
    <div>
        <?php if (!$selected_siswa): ?>
            <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); text-align: center; padding: 60px 20px;">
                <i class="fa-solid fa-chart-pie" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3>Pilih Siswa Bimbingan</h3>
                <p style="font-size: 13px; color: var(--text-muted); max-width: 400px; margin: 0 auto; margin-top: 5px;">
                    Silakan pilih salah satu siswa dari daftar di sebelah kiri untuk melihat rekapitulasi progres hafalan secara rinci.
                </p>
            </div>
        <?php else: ?>
            <!-- Header Biodata Anak -->
            <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 25px; margin-bottom: 25px; width: 100%; max-width: 100%;">
                <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <div style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.05); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 24px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>
                    <div>
                        <h2 style="font-family: var(--font-heading); color: var(--primary-color); font-size: 20px; margin-bottom: 4px;">
                            <?php echo htmlspecialchars($selected_siswa['nama_lengkap']); ?>
                        </h2>
                        <p style="font-size: 13px; color: var(--text-muted);">
                            NISN: <strong><?php echo htmlspecialchars($selected_siswa['nisn']); ?></strong> 
                            | Kelas: <strong><?php echo htmlspecialchars($selected_siswa['nama_kelas']); ?></strong>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Statistik Ringkas Siswa -->
            <div class="stat-grid" style="margin-bottom: 25px;">
                <div class="stat-card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 16px 20px;">
                    <div class="stat-info">
                        <h3 style="font-size: 11px;">Setoran Ziadah</h3>
                        <p style="font-size: 20px;"><?php echo $stats['ziadah']; ?></p>
                    </div>
                    <div class="stat-icon-box stat-icon-green" style="width: 36px; height: 36px; font-size: 16px;">
                        <i class="fa-solid fa-book-medical"></i>
                    </div>
                </div>
                <div class="stat-card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 16px 20px;">
                    <div class="stat-info">
                        <h3 style="font-size: 11px;">Setoran Murajaah</h3>
                        <p style="font-size: 20px;"><?php echo $stats['murajaah']; ?></p>
                    </div>
                    <div class="stat-icon-box stat-icon-blue" style="width: 36px; height: 36px; font-size: 16px;">
                        <i class="fa-solid fa-rotate-left"></i>
                    </div>
                </div>
                <div class="stat-card" style="grid-column: span 2; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 16px 20px;">
                    <div class="stat-info">
                        <h3 style="font-size: 11px;">Hafalan Terakhir</h3>
                        <p style="font-size: 14px; margin-top: 5px; color: var(--primary-dark); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo $stats['last_setoran']; ?>
                        </p>
                    </div>
                    <div class="stat-icon-box stat-icon-purple" style="width: 36px; height: 36px; font-size: 16px;">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
            </div>
            
            <!-- Target Semester & Summary Hafalan -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; flex-wrap: wrap; align-items: start;">
                <!-- Target Box -->
                <div class="admin-card-table" style="padding: 20px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); height: 100%;">
                    <h3 style="font-family: var(--font-heading); color: var(--primary-dark); font-size: 14px; font-weight: 700; margin-bottom: 12px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 8px;">
                        Target Hafalan Aktif
                    </h3>
                    <?php if ($target_info): ?>
                        <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 10px; text-transform: uppercase;">Target Juz</span>
                                <strong><?php echo htmlspecialchars($target_info['target_juz'] ?? '-'); ?></strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 10px; text-transform: uppercase;">Target Surah</span>
                                <strong><?php echo htmlspecialchars($target_info['target_surah'] ?? '-'); ?></strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block; font-size: 10px; text-transform: uppercase;">Catatan</span>
                                <span style="color: var(--text-main); font-style: italic;"><?php echo htmlspecialchars($target_info['keterangan'] ?? '-'); ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 13px; color: var(--text-muted); line-height: 1.5;">
                            Belum ada target yang diatur untuk tahun ajaran / semester ini. 
                            <a href="target.php?siswa_id=<?php echo $selected_siswa_id; ?>" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Atur Sekarang <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i></a>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Hafalan Box (Hafalan Terdata) -->
                <div class="admin-card-table" style="padding: 20px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); height: 100%;">
                    <h3 style="font-family: var(--font-heading); color: var(--primary-dark); font-size: 14px; font-weight: 700; margin-bottom: 12px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 8px;">
                        Daftar Surah Terdata (Ziadah)
                    </h3>
                    <div style="max-height: 150px; overflow-y: auto; padding-right: 5px;">
                        <?php if (empty($hafalan_summary)): ?>
                            <p style="font-size: 13px; color: var(--text-muted); text-align: center; padding-top: 20px;">Belum ada hafalan disetorkan.</p>
                        <?php else: ?>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <?php foreach ($hafalan_summary as $summary): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; padding-bottom: 6px; border-bottom: 1px solid #f8fafc;">
                                        <strong>Surah <?php echo htmlspecialchars($summary['surah']); ?></strong>
                                        <span style="font-family: monospace; background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: var(--primary-dark); font-weight: 600;">
                                            Ayat <?php echo $summary['ayat_min']; ?> - <?php echo $summary['ayat_max']; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            

            
            <!-- Tabel Riwayat Progres Lengkap -->
            <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
                <div class="admin-card-header">
                    <h2>Garis Waktu / Log Progres Hafalan Siswa</h2>
                </div>
                
                <div class="table-responsive">
                    <?php if (empty($riwayat_setoran)): ?>
                        <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                            Belum ada riwayat setoran hafalan siswa ini.
                        </div>
                    <?php else: ?>
                        <table class="table-admin" style="font-size: 13px;">
                            <thead>
                                <tr>
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th>Tanggal</th>
                                    <th>Kategori</th>
                                    <th>Surah & Ayat</th>
                                    <th style="text-align: center;">Skor Angka</th>
                                    <th style="text-align: center;">Predikat</th>
                                    <th>Catatan Guru</th>
                                    <th>Guru Penguji</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($riwayat_setoran as $setoran): 
                                    $jenis_label = $setoran['jenis'] === 'ziadah' ? 'Ziadah' : 'Murajaah';
                                    $jenis_style = $setoran['jenis'] === 'ziadah' 
                                        ? 'background-color: rgba(13, 92, 52, 0.06); color: var(--primary-color);' 
                                        : 'background-color: rgba(59, 130, 246, 0.06); color: #1d4ed8;';
                                    
                                    $nilai = trim($setoran['nilai']);
                                    if (strcasecmp($nilai, 'Sangat Lancar') === 0) {
                                        $nilai_class = 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
                                    } elseif (strcasecmp($nilai, 'Lancar Terbata-Bata') === 0) {
                                        $nilai_class = 'background-color: #fefce8; color: #854d0e; border: 1px solid #fef08a;';
                                    } elseif (strcasecmp($nilai, 'Lancar dengan Bantuan') === 0) {
                                        $nilai_class = 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;';
                                    } else {
                                        $nilai_class = 'background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;';
                                    }
                                ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                                        <td><?php echo date('d-m-Y', strtotime($setoran['tanggal'])); ?></td>
                                        <td>
                                            <span class="badge-status" style="<?php echo $jenis_style; ?> font-size: 10px; padding: 2px 6px;">
                                                <?php echo $jenis_label; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($setoran['surah']); ?></strong>
                                            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                                Ayat <?php echo $setoran['ayat_mulai']; ?> - <?php echo $setoran['ayat_selesai']; ?>
                                            </div>
                                        </td>
                                        <td style="text-align: center; font-weight: bold; color: var(--primary-color);">
                                            <?php echo htmlspecialchars($setoran['nilai_angka'] ?? '-'); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <span style="display: inline-block; padding: 4px 8px; border-radius: 6px; font-weight: bold; font-size: 11px; line-height: normal; <?php echo $nilai_class; ?>">
                                                <?php echo htmlspecialchars($setoran['nilai']); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 250px; line-height: 1.4; color: var(--text-muted);">
                                            <?php echo htmlspecialchars($setoran['catatan'] ?? '-'); ?>
                                        </td>
                                        <td>
                                            <span style="font-size: 12px;"><?php echo htmlspecialchars($setoran['nama_guru']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterSantri() {
    var input = document.getElementById('searchSantri');
    var filter = input.value.toLowerCase();
    var items = document.querySelectorAll('.santri-item');
    
    items.forEach(function(item) {
        var name = item.querySelector('.santri-name').innerText.toLowerCase();
        if (name.includes(filter)) {
            item.style.display = "";
        } else {
            item.style.display = "none";
        }
    });
}
</script>

</main>
</div>
</div>
</body>
</html>
