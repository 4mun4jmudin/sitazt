<?php
// orang_tua/target.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$target_hafalan = null;
$ta_aktif = null;

if ($anak_aktif) {
    try {
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
    } catch (\PDOException $e) {
        $error = 'Gagal memuat target hafalan.';
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
    <!-- Header Page with Back Button -->
    <div style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="font-size: 20px; font-family: var(--font-heading); color: var(--primary-dark); margin-bottom: 6px;">
                Target Setoran Anak
            </h2>
            <p style="font-size: 14px; color: var(--text-muted);">
                Target hafalan aktif yang diatur oleh guru pembimbing untuk ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
            </p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm" style="width: auto; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-family: var(--font-body); font-weight: 500;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <!-- Target Card -->
    <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); padding: 30px; width: 100%; max-width: 100%;">
        <?php if (!$ta_aktif): ?>
            <div style="text-align: center; padding: 30px 10px;">
                <i class="fa-solid fa-calendar-xmark" style="font-size: 40px; color: var(--error-color); margin-bottom: 15px; display: block;"></i>
                <h3 style="color: #991b1b; font-family: var(--font-heading); margin-bottom: 5px;">Tahun Ajaran Aktif Belum Diatur</h3>
                <p style="font-size: 13px; color: var(--text-muted); max-width: 400px; margin: 0 auto;">
                    Sistem belum mendeteksi adanya Tahun Ajaran yang berstatus aktif saat ini. Target hafalan tidak dapat ditampilkan.
                </p>
            </div>
        <?php elseif (!$target_hafalan): ?>
            <div style="text-align: center; padding: 40px 10px;">
                <div style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(245, 158, 11, 0.05); display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: #d97706; font-size: 28px;">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <h3 style="font-family: var(--font-heading); color: var(--primary-dark); font-size: 16px; margin-bottom: 8px;">Target Belum Diatur</h3>
                <p style="font-size: 13px; color: var(--text-muted); max-width: 450px; margin: 0 auto; line-height: 1.5;">
                    Target hafalan untuk ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong> pada tahun ajaran <strong><?php echo htmlspecialchars($ta_aktif['tahun']); ?> (<?php echo htmlspecialchars($ta_aktif['semester']); ?>)</strong> belum diatur oleh ustadz/ustadzah pembimbing.
                </p>
            </div>
        <?php else: ?>
            <div style="display: flex; gap: 20px; align-items: center; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.08); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 22px;">
                    <i class="fa-solid fa-bullseye"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-family: var(--font-heading); color: var(--primary-dark); font-size: 18px; font-weight: 700;">
                        Target Semester Aktif
                    </h3>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 500;">
                        Tahun Ajaran: <?php echo htmlspecialchars($ta_aktif['tahun']); ?> | Semester: <?php echo htmlspecialchars($ta_aktif['semester']); ?>
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600; letter-spacing: 0.5px;">Target Juz</span>
                    <strong style="font-size: 18px; color: var(--primary-color); display: block; margin-top: 5px;">
                        <?php echo htmlspecialchars($target_hafalan['target_juz'] ? $target_hafalan['target_juz'] : '-'); ?>
                    </strong>
                </div>
                <div style="background-color: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                    <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600; letter-spacing: 0.5px;">Target Surah</span>
                    <strong style="font-size: 18px; color: var(--primary-dark); display: block; margin-top: 5px;">
                        <?php echo htmlspecialchars($target_hafalan['target_surah'] ? $target_hafalan['target_surah'] : '-'); ?>
                    </strong>
                </div>
            </div>

            <div>
                <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 5px;">Catatan Tambahan Target</span>
                <div style="background-color: #f0fdf4; border-left: 4px solid var(--primary-color); padding: 15px 20px; border-radius: 4px 12px 12px 4px; font-style: italic; color: var(--text-main); line-height: 1.6; font-size: 14px;">
                    "<?php echo htmlspecialchars($target_hafalan['keterangan'] ? $target_hafalan['keterangan'] : 'Tidak ada catatan tambahan untuk target ini.'); ?>"
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
