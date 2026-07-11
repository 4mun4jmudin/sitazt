<?php
// orang_tua/index.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$success = '';

// Ambil info anak terpilih
$siswa = null;
$kelas_info = null;
$wali_kelas = null;
$stats = [
    'ziadah' => 0,
    'murajaah' => 0,
    'last_setoran' => 'Belum ada setoran'
];

if ($anak_aktif) {
    try {
        // Ambil data detail siswa
        $stmt_siswa = $pdo->prepare("
            SELECT s.*, k.nama_kelas, k.wali_kelas_id 
            FROM siswa s 
            LEFT JOIN kelas k ON s.kelas_id = k.id 
            WHERE s.id = :id
        ");
        $stmt_siswa->execute(['id' => $anak_aktif['id']]);
        $siswa = $stmt_siswa->fetch();
        
        if ($siswa && $siswa['wali_kelas_id']) {
            // Ambil data wali kelas (Guru Tahfidz)
            $stmt_wali = $pdo->prepare("SELECT nama_lengkap, no_hp FROM guru_tahfidz WHERE id = :id");
            $stmt_wali->execute(['id' => $siswa['wali_kelas_id']]);
            $wali_kelas = $stmt_wali->fetch();
        }
        
        // Hitung statistik setoran
        $stmt_ziadah = $pdo->prepare("SELECT COUNT(*) FROM setoran_tahfidz WHERE siswa_id = :id AND jenis = 'ziadah'");
        $stmt_ziadah->execute(['id' => $anak_aktif['id']]);
        $stats['ziadah'] = $stmt_ziadah->fetchColumn();
        
        $stmt_murajaah = $pdo->prepare("SELECT COUNT(*) FROM setoran_tahfidz WHERE siswa_id = :id AND jenis = 'murajaah'");
        $stmt_murajaah->execute(['id' => $anak_aktif['id']]);
        $stats['murajaah'] = $stmt_murajaah->fetchColumn();
        
        // Ambil setoran terakhir
        $stmt_last = $pdo->prepare("
            SELECT surah, ayat_selesai, jenis, tanggal 
            FROM setoran_tahfidz 
            WHERE siswa_id = :id 
            ORDER BY tanggal DESC, id DESC 
            LIMIT 1
        ");
        $stmt_last->execute(['id' => $anak_aktif['id']]);
        $last = $stmt_last->fetch();
        
        if ($last) {
            $stats['last_setoran'] = htmlspecialchars($last['surah']) . ' (Ayat ' . $last['ayat_selesai'] . ') - ' . date('d/m/Y', strtotime($last['tanggal']));
        }
        
        // Ambil target hafalan aktif
        $target_hafalan = null;
        $stmt_ta = $pdo->query("SELECT id, tahun, semester FROM tahun_ajaran WHERE status = 'aktif' LIMIT 1");
        $ta_aktif = $stmt_ta->fetch();
        if ($ta_aktif) {
            $stmt_target = $pdo->prepare("
                SELECT * FROM target_hafalan 
                WHERE siswa_id = :siswa_id AND tahun_ajaran_id = :ta_id
            ");
            $stmt_target->execute([
                'siswa_id' => $anak_aktif['id'],
                'ta_id' => $ta_aktif['id']
            ]);
            $target_hafalan = $stmt_target->fetch();
        }
        
        // Ambil catatan guru pembimbing terbaru
        $latest_note = null;
        $stmt_note = $pdo->prepare("
            SELECT st.*, gt.nama_lengkap AS nama_guru 
            FROM setoran_tahfidz st
            JOIN guru_tahfidz gt ON st.guru_id = gt.id
            WHERE st.siswa_id = :siswa_id AND st.catatan IS NOT NULL AND TRIM(st.catatan) != ''
            ORDER BY st.tanggal DESC, st.id DESC
            LIMIT 1
        ");
        $stmt_note->execute(['siswa_id' => $anak_aktif['id']]);
        $latest_note = $stmt_note->fetch();
        
        // Hitung jumlah pesan belum dibaca dari guru
        $unread_msg_count = 0;
        $stmt_unread_dash = $pdo->prepare("SELECT COUNT(*) FROM konsultasi WHERE penerima_id = :my_user_id AND is_read = 0");
        $stmt_unread_dash->execute(['my_user_id' => $user_id]);
        $unread_msg_count = (int) $stmt_unread_dash->fetchColumn();
    } catch (\PDOException $e) {
        $error = 'Gagal memuat detail data anak.';
    }
}
?>

<?php if (empty($daftar_anak)): ?>
    <!-- Kasus belum ada anak terhubung -->
    <div class="card" style="margin-top: 20px; box-shadow: none; border: 1.5px solid rgba(239, 68, 68, 0.2); background-color: rgba(239, 68, 68, 0.03); width: 100%; max-width: 100%;">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 30px; color: var(--error-color);"></i>
            <div>
                <h3 style="color: #991b1b; font-family: var(--font-heading); margin-bottom: 8px;">Data Anak Belum Terhubung</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Akun Orang Tua / Wali Anda belum terhubung dengan data siswa di sistem MI Al-Adzkiya.
                </p>
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 10px; font-weight: 500;">
                    Silakan hubungi Administrator sekolah di bagian tata usaha untuk menautkan NISN anak Anda dengan akun pengguna Anda.
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Info Profil Anak -->
    <div class="card" style="margin-bottom: 30px; padding: 30px; box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%;">
        <div style="display: flex; gap: 30px; align-items: center; flex-wrap: wrap;">
            <!-- Foto Profil -->
            <div style="text-align: center;">
                <?php if ($siswa['foto_profil'] && file_exists('../uploads/siswa/' . $siswa['foto_profil'])): ?>
                    <img src="../uploads/siswa/<?php echo htmlspecialchars($siswa['foto_profil']); ?>" alt="Foto Anak" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 3px solid var(--primary-color); box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <?php else: ?>
                    <div style="width: 120px; height: 120px; border-radius: 50%; background-color: #f1f5f9; border: 3px solid #e2e8f0; display: flex; justify-content: center; align-items: center; color: var(--text-muted); font-size: 40px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <i class="fa-solid fa-child"></i>
                    </div>
                <?php endif; ?>
                <span style="display: block; margin-top: 10px; font-size: 10px; color: var(--text-muted); max-width: 140px; line-height: 1.3;">
                    *Foto profil dikelola oleh Administrator
                </span>
            </div>
            
            <!-- Biodata & Wali Kelas -->
            <div style="flex: 1; min-width: 250px;">
                <h2 style="font-family: var(--font-heading); color: var(--primary-color); font-size: 24px; margin-bottom: 5px;">
                    <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                </h2>
                <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 15px;">
                    NISN: <strong style="font-family: monospace; font-size: 15px; color: var(--text-main);"><?php echo htmlspecialchars($siswa['nisn']); ?></strong> 
                    | Kelas: <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($siswa['nama_kelas'] ?? 'Belum Diplot'); ?></strong>
                </p>
                
                <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                    <!-- Wali Kelas Box -->
                    <div style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 15px; display: inline-flex; align-items: center; gap: 12px; font-size: 13px; flex: 1; min-width: 250px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.08); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 16px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Guru Pembimbing Tahfidz</div>
                            <strong><?php echo htmlspecialchars($wali_kelas['nama_lengkap'] ?? 'Belum Diplot'); ?></strong>
                            <?php if ($wali_kelas && $wali_kelas['no_hp']): ?>
                                <span style="margin-left: 10px; color: var(--text-muted);">
                                    (<i class="fa-solid fa-phone" style="font-size: 10px;"></i> <?php echo htmlspecialchars($wali_kelas['no_hp']); ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Target Hafalan Box -->
                    <div style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 15px; display: inline-flex; align-items: center; gap: 12px; font-size: 13px; flex: 1; min-width: 250px;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; background-color: rgba(245, 158, 11, 0.08); display: flex; justify-content: center; align-items: center; color: #d97706; font-size: 16px;">
                            <i class="fa-solid fa-bullseye"></i>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;">Target Hafalan Semester</div>
                            <?php if ($target_hafalan): ?>
                                <strong>Juz: <?php echo htmlspecialchars($target_hafalan['target_juz'] ?? '-'); ?> | Surah: <?php echo htmlspecialchars($target_hafalan['target_surah'] ?? '-'); ?></strong>
                                <?php if ($target_hafalan['keterangan']): ?>
                                    <div style="font-size: 11px; color: var(--text-muted); font-style: italic; margin-top: 2px;">
                                        "<?php echo htmlspecialchars($target_hafalan['keterangan']); ?>"
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <strong style="color: var(--text-muted);">Belum Diatur Guru</strong>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Statistik -->
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h3>Setoran Ziadah</h3>
                <p><?php echo $stats['ziadah']; ?></p>
            </div>
            <div class="stat-icon-box stat-icon-green">
                <i class="fa-solid fa-book-medical"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h3>Setoran Murajaah</h3>
                <p><?php echo $stats['murajaah']; ?></p>
            </div>
            <div class="stat-icon-box stat-icon-blue">
                <i class="fa-solid fa-rotate-left"></i>
            </div>
        </div>
        <div class="stat-card" style="grid-column: span 2;">
            <div class="stat-info">
                <h3>Hafalan Terakhir</h3>
                <p style="font-size: 16px; margin-top: 10px; font-weight: 700; color: var(--primary-dark);">
                    <?php echo $stats['last_setoran']; ?>
                </p>
            </div>
            <div class="stat-icon-box stat-icon-purple">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- Notifikasi Pesan Konsultasi Baru -->
    <?php if ($unread_msg_count > 0): ?>
        <div class="card" style="margin-top: 20px; margin-bottom: 20px; border-left: 5px solid var(--error-color); box-shadow: none; border-top: 1px solid rgba(239, 68, 68, 0.1); border-right: 1px solid rgba(239, 68, 68, 0.1); border-bottom: 1px solid rgba(239, 68, 68, 0.1); padding: 20px; width: 100%; max-width: 100%; background-color: rgba(239, 68, 68, 0.01);">
            <div style="display: flex; gap: 15px; align-items: flex-start;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.08); display: flex; justify-content: center; align-items: center; color: var(--error-color); font-size: 20px; flex-shrink: 0;">
                    <i class="fa-solid fa-comments"></i>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 5px;">
                        <h4 style="margin: 0; font-family: var(--font-heading); color: #991b1b; font-size: 15px; font-weight: 700;">
                            Pesan Konsultasi Baru
                        </h4>
                        <span style="background-color: var(--error-color); color: #ffffff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">
                            <?php echo $unread_msg_count; ?> Pesan Baru
                        </span>
                    </div>
                    <p style="margin: 8px 0 0; color: var(--text-muted); font-size: 13px; line-height: 1.5;">
                        Ada pesan baru dari Ustadz/Ustadzah pembimbing tahfidz yang belum Anda baca. Silakan buka Ruang Konsultasi untuk membaca dan membalas pesan.
                    </p>
                    <div style="margin-top: 12px; text-align: right;">
                        <a href="konsultasi.php" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; width: auto; font-family: var(--font-body); font-weight: 600; font-size: 12px; padding: 8px 16px; border-radius: 8px; background-color: var(--error-color); border-color: var(--error-color);">
                            Buka Konsultasi <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notifikasi Catatan Guru -->
    <?php if (!empty($latest_note)): ?>
        <div class="card" style="margin-top: 30px; margin-bottom: 10px; border-left: 5px solid var(--primary-color); box-shadow: none; border-top: 1px solid rgba(13, 92, 52, 0.1); border-right: 1px solid rgba(13, 92, 52, 0.1); border-bottom: 1px solid rgba(13, 92, 52, 0.1); padding: 20px; width: 100%; max-width: 100%;">
            <div style="display: flex; gap: 15px; align-items: flex-start;">
                <div style="width: 42px; height: 42px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.08); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 20px; flex-shrink: 0;">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div style="flex: 1;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 5px;">
                        <h4 style="margin: 0; font-family: var(--font-heading); color: var(--primary-dark); font-size: 15px; font-weight: 700;">
                            Catatan &amp; Evaluasi Terbaru dari Guru
                        </h4>
                        <span style="font-size: 11px; color: var(--text-muted); font-weight: 500;">
                            <?php echo date('d-m-Y', strtotime($latest_note['tanggal'])); ?>
                        </span>
                    </div>
                    <p style="margin: 10px 0 0; font-style: italic; color: var(--text-main); font-size: 13.5px; line-height: 1.5; background-color: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1.5px solid #f1f5f9;">
                        "<?php echo htmlspecialchars($latest_note['catatan']); ?>"
                    </p>
                    <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <span style="font-size: 11px; color: var(--text-muted);">
                            Surah: <strong><?php echo htmlspecialchars($latest_note['surah']); ?></strong> (Ayat <?php echo $latest_note['ayat_mulai']; ?> - <?php echo $latest_note['ayat_selesai']; ?>) | Nilai: <strong style="color: var(--primary-color);"><?php echo htmlspecialchars($latest_note['nilai']); ?></strong> | Oleh: <strong><?php echo htmlspecialchars($latest_note['nama_guru']); ?></strong>
                        </span>
                        <a href="catatan.php" style="font-size: 12px; font-weight: 600; color: var(--primary-color); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            Lihat Semua Catatan <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
