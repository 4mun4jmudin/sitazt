<?php
// orang_tua/catatan.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$catatan_list = [];

if ($anak_aktif) {
    try {
        // Fetch all setoran that have a non-empty comment, joined with guru_tahfidz
        $stmt = $pdo->prepare("
            SELECT st.*, gt.nama_lengkap AS nama_guru 
            FROM setoran_tahfidz st
            JOIN guru_tahfidz gt ON st.guru_id = gt.id
            WHERE st.siswa_id = :siswa_id AND st.catatan IS NOT NULL AND TRIM(st.catatan) != ''
            ORDER BY st.tanggal DESC, st.id DESC
        ");
        $stmt->execute(['siswa_id' => $anak_aktif['id']]);
        $catatan_list = $stmt->fetchAll();
    } catch (\PDOException $e) {
        $error = 'Gagal memuat catatan evaluasi.';
    }
}
?>

<?php if (empty($daftar_anak)): ?>
    <div class="card" style="margin-top: 20px; box-shadow: none; border: 1.5px solid rgba(239, 68, 68, 0.2); background-color: rgba(239, 68, 68, 0.03); width: 100%; max-width: 100%;">
        <div style="display: flex; gap: 15px; align-items: flex-start;">
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 30px; color: var(--error-color);"></i>
            <div>
                <h3 style="color: #991b1b; font-family: var(--font-heading); margin-bottom: 8px;">Data Anak Belum Terhubung</h3>
                <p style="font-size: 14px; color: var(--text-muted); line-height: 1.6;">
                    Akun Anda belum terhubung dengan data siswa mana pun.
                </p>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Konten Utama Catatan & Evaluasi -->
    <div style="margin-bottom: 25px;">
        <h2 style="font-size: 20px; font-family: var(--font-heading); color: var(--primary-dark); margin-bottom: 6px;">
            Catatan Perkembangan & Evaluasi Hafalan
        </h2>
        <p style="font-size: 14px; color: var(--text-muted);">
            Evaluasi bimbingan, nasehat, dan motivasi dari guru pembimbing tahfidz untuk ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <?php if (empty($catatan_list)): ?>
        <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%; text-align: center; padding: 60px 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.05); display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--primary-color); font-size: 28px;">
                <i class="fa-regular fa-comment-dots"></i>
            </div>
            <h3 style="font-family: var(--font-heading); color: var(--primary-dark); font-size: 16px; margin-bottom: 8px;">Belum Ada Catatan Evaluasi</h3>
            <p style="font-size: 13px; color: var(--text-muted); max-width: 400px; margin: 0 auto; line-height: 1.5;">
                Ustadz atau ustadzah belum menuliskan catatan/evaluasi khusus pada riwayat setoran hafalan ananda saat ini.
            </p>
        </div>
    <?php else: ?>
        <!-- Timeline Evaluasi -->
        <div style="max-width: 850px; margin: 30px auto 0;">
            <div style="position: relative; padding-left: 35px; border-left: 2px dashed rgba(13, 92, 52, 0.2); margin-left: 15px;">
                <?php foreach ($catatan_list as $catatan): 
                    $jenis_label = $catatan['jenis'] === 'ziadah' ? 'Ziadah' : 'Murajaah';
                    $jenis_color = $catatan['jenis'] === 'ziadah' ? 'var(--primary-color)' : '#1d4ed8';
                    $jenis_bg = $catatan['jenis'] === 'ziadah' ? 'rgba(13, 92, 52, 0.08)' : 'rgba(59, 130, 246, 0.08)';
                ?>
                    <!-- Timeline Item -->
                    <div style="position: relative; margin-bottom: 40px;">
                        <!-- Timeline Bullet -->
                        <div style="position: absolute; left: -46px; top: 0; width: 20px; height: 20px; border-radius: 50%; background-color: #ffffff; border: 4px solid var(--primary-color); box-shadow: 0 0 0 4px rgba(13, 92, 52, 0.1); z-index: 2;"></div>
                        
                        <!-- Card wrapper -->
                        <div style="background-color: #ffffff; border: 1.5px solid rgba(13, 92, 52, 0.08); border-radius: 16px; padding: 25px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02); transition: all 0.3s;" onmouseover="this.style.borderColor='rgba(13, 92, 52, 0.2)';" onmouseout="this.style.borderColor='rgba(13, 92, 52, 0.08)';">
                            
                            <!-- Card Header -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 15px; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 12px;">
                                <div>
                                    <div style="font-size: 12px; color: var(--text-muted); font-weight: 500; display: flex; align-items: center; gap: 5px;">
                                        <i class="fa-regular fa-calendar"></i>
                                        <span><?php echo date('d F Y', strtotime($catatan['tanggal'])); ?></span>
                                    </div>
                                    <h3 style="font-family: var(--font-heading); color: var(--primary-dark); font-size: 16px; margin-top: 6px; font-weight: 700;">
                                        <?php echo htmlspecialchars($catatan['surah']); ?>
                                        <span style="font-family: var(--font-body); font-size: 13px; font-weight: 500; color: var(--text-muted); margin-left: 5px;">
                                            (Ayat <?php echo $catatan['ayat_mulai']; ?> - <?php echo $catatan['ayat_selesai']; ?>)
                                        </span>
                                    </h3>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span class="badge-status" style="background-color: <?php echo $jenis_bg; ?>; color: <?php echo $jenis_color; ?>; font-size: 11px; padding: 3px 10px; font-weight: 600; border-radius: 20px;">
                                        <?php echo $jenis_label; ?>
                                    </span>
                                    <span class="badge-status" style="background-color: #f8fafc; border: 1px solid #e2e8f0; color: var(--primary-color); font-weight: 800; font-size: 11px; padding: 3px 10px; border-radius: 20px;">
                                        Nilai: <?php echo htmlspecialchars($catatan['nilai']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Comment Content -->
                            <div style="background-color: #f8fafc; border-left: 4px solid var(--primary-color); padding: 15px 20px; border-radius: 4px 12px 12px 4px; font-style: italic; color: var(--text-main); line-height: 1.6; font-size: 14px; margin-bottom: 15px; position: relative;">
                                <i class="fa-solid fa-quote-left" style="position: absolute; right: 20px; top: 15px; font-size: 24px; color: rgba(13, 92, 52, 0.05);"></i>
                                "<?php echo htmlspecialchars($catatan['catatan']); ?>"
                            </div>
                            
                            <!-- Guru Info Footer -->
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 30px; height: 30px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.08); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 12px;">
                                        <i class="fa-solid fa-user-tie"></i>
                                    </div>
                                    <div>
                                        <span style="font-size: 10px; color: var(--text-muted); display: block; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Oleh Ustadz/Ustadzah</span>
                                        <strong style="color: var(--text-main); font-size: 13px;"><?php echo htmlspecialchars($catatan['nama_guru']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
