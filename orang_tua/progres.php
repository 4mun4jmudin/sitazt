<?php
// orang_tua/progres.php
require_once '../config/database.php';
require_once 'header.php';

$error = '';
$setoran_list = [];

if ($anak_aktif) {
    try {
        // Fetch all setoran for the active child, joined with guru_tahfidz to display teacher's name
        $stmt = $pdo->prepare("
            SELECT st.*, gt.nama_lengkap AS nama_guru 
            FROM setoran_tahfidz st
            JOIN guru_tahfidz gt ON st.guru_id = gt.id
            WHERE st.siswa_id = :siswa_id
            ORDER BY st.tanggal DESC, st.id DESC
        ");
        $stmt->execute(['siswa_id' => $anak_aktif['id']]);
        $setoran_list = $stmt->fetchAll();
    } catch (\PDOException $e) {
        $error = 'Gagal memuat riwayat setoran hafalan.';
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
                Setoran Anak Saya
            </h2>
            <p style="font-size: 14px; color: var(--text-muted);">
                Daftar setoran hafalan lengkap untuk ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
            </p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm" style="width: auto; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-family: var(--font-body); font-weight: 500;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </div>

    <!-- Riwayat Setoran Card -->
    <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%;">
        <div class="admin-card-header" style="padding: 24px;">
            <div>
                <h2 style="font-size: 18px; font-family: var(--font-heading); color: var(--primary-color);">
                    Riwayat Progres Setoran Hafalan
                </h2>
            </div>
            <div style="display: flex; gap: 10px; font-size: 12px; font-weight: 500;">
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background-color: rgba(13, 92, 52, 0.08); color: var(--primary-color); border-radius: 20px; border: 1px solid rgba(13, 92, 52, 0.15);">
                    <span style="width: 6px; height: 6px; background-color: var(--primary-color); border-radius: 50%;"></span> Ziadah (Hafalan Baru)
                </span>
                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background-color: rgba(59, 130, 246, 0.08); color: #1d4ed8; border-radius: 20px; border: 1px solid rgba(59, 130, 246, 0.15);">
                    <span style="width: 6px; height: 6px; background-color: #3b82f6; border-radius: 50%;"></span> Murajaah (Ulang Hafalan)
                </span>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin: 20px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <?php if (empty($setoran_list)): ?>
                <div style="padding: 50px 24px; text-align: center; color: var(--text-muted);">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; margin: 0 auto 15px; color: #94a3b8; font-size: 24px;">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <p style="font-weight: 500; font-size: 15px; color: var(--text-main);">Belum ada riwayat setoran hafalan</p>
                    <p style="font-size: 13px; color: var(--text-muted); margin-top: 5px;">
                        Catatan setoran akan muncul di sini setelah ustadz/ustadzah pembimbing menginput data setoran.
                    </p>
                </div>
            <?php else: ?>
                <table class="table-admin">
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Surah</th>
                            <th style="text-align: center;">Ayat</th>
                            <th>Guru Pembimbing</th>
                            <th style="text-align: center;">Nilai</th>
                            <th style="text-align: center; width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($setoran_list as $setoran): 
                            $jenis_label = $setoran['jenis'] === 'ziadah' ? 'Ziadah' : 'Murajaah';
                            $jenis_style = $setoran['jenis'] === 'ziadah' 
                                ? 'background-color: rgba(13, 92, 52, 0.08); color: var(--primary-color); border: 1px solid rgba(13, 92, 52, 0.15);' 
                                : 'background-color: rgba(59, 130, 246, 0.08); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.15);';
                        ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: var(--text-muted);"><?php echo $no++; ?></td>
                                <td style="white-space: nowrap; font-weight: 500;">
                                    <i class="fa-regular fa-calendar" style="margin-right: 5px; color: var(--text-muted);"></i>
                                    <?php echo date('d M Y', strtotime($setoran['tanggal'])); ?>
                                </td>
                                <td>
                                    <span class="badge-status" style="<?php echo $jenis_style; ?> font-size: 11px; padding: 4px 10px; font-weight: 600;">
                                        <?php echo $jenis_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($setoran['surah']); ?></strong>
                                </td>
                                <td style="text-align: center; font-family: monospace; font-size: 14px; font-weight: 600; color: var(--text-main);">
                                    <?php echo $setoran['ayat_mulai']; ?> - <?php echo $setoran['ayat_selesai']; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #f1f5f9; display: flex; justify-content: center; align-items: center; color: var(--text-muted); font-size: 11px;">
                                            <i class="fa-solid fa-user-tie"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($setoran['nama_guru']); ?></span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span style="display: inline-block; width: 32px; height: 32px; line-height: 30px; text-align: center; border-radius: 6px; font-weight: 800; font-size: 14px; background-color: #f8fafc; border: 1px solid #e2e8f0; color: var(--primary-color);">
                                        <?php echo htmlspecialchars($setoran['nilai']); ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn btn-secondary btn-sm" onclick="showDetail(<?php echo htmlspecialchars(json_encode($setoran)); ?>)" style="padding: 4px 10px; font-size: 11px; width: auto; display: inline-flex; align-items: center; gap: 4px; font-family: var(--font-body);">
                                        <i class="fa-solid fa-eye"></i> Detail
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Detail Setoran -->
    <div class="modal-overlay" id="detailModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; margin: 0;">Detail Setoran Hafalan</h3>
                <button onclick="closeDetailModal()" style="background: none; border: none; color: #ffffff; font-size: 20px; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="font-size: 14px;">
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <div style="background-color: #f0fdf4; border: 1px solid rgba(13, 92, 52, 0.1); padding: 12px 16px; border-radius: 8px;">
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Siswa</span>
                        <strong style="color: var(--primary-dark); font-size: 15px;"><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Tanggal</span>
                            <strong id="detTanggal"></strong>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Kategori</span>
                            <span id="detJenis" class="badge-status" style="font-weight: 600; padding: 2px 8px; font-size: 11px; display: inline-block; margin-top: 2px;"></span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Surah</span>
                            <strong id="detSurah" style="color: var(--primary-color); font-size: 15px;"></strong>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Ayat</span>
                            <strong id="detAyat"></strong>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Nilai Kelancaran</span>
                            <div style="margin-top: 2px;">
                                <span id="detNilai" style="display: inline-block; padding: 2px 8px; font-weight: bold; border-radius: 4px; font-size: 12px;"></span>
                            </div>
                        </div>
                        <div>
                            <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Guru Pembimbing</span>
                            <strong id="detGuru"></strong>
                        </div>
                    </div>

                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block;">Catatan / Evaluasi Guru</span>
                        <div id="detCatatan" style="background-color: #f8fafc; border-left: 3px solid var(--primary-color); padding: 10px 15px; border-radius: 4px; font-style: italic; color: var(--text-main); line-height: 1.5; margin-top: 5px;"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeDetailModal()" class="btn btn-secondary btn-sm" style="width: auto; padding: 8px 16px; font-family: var(--font-body);">Tutup</button>
            </div>
        </div>
    </div>

    <style>
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

    <script>
    function showDetail(data) {
        document.getElementById('detTanggal').innerText = data.tanggal;
        document.getElementById('detSurah').innerText = data.surah;
        document.getElementById('detAyat').innerText = data.ayat_mulai + ' - ' + data.ayat_selesai;
        document.getElementById('detGuru').innerText = data.nama_guru;
        document.getElementById('detCatatan').innerText = data.catatan ? data.catatan : '-';
        
        // Jenis badge
        var jenis = data.jenis === 'ziadah' ? 'Ziadah' : 'Murajaah';
        var jenisStyle = data.jenis === 'ziadah'
            ? 'background-color: rgba(13, 92, 52, 0.08); color: var(--primary-color); border: 1px solid rgba(13, 92, 52, 0.15);'
            : 'background-color: rgba(59, 130, 246, 0.08); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.15);';
        document.getElementById('detJenis').innerText = jenis;
        document.getElementById('detJenis').style = jenisStyle + " font-weight: 600; padding: 2px 8px; font-size: 11px; display: inline-block; margin-top: 2px;";

        // Nilai style
        var nilai = data.nilai.toUpperCase().trim();
        var nilaiStyle = '';
        if (nilai === 'A' || nilai === 'A+') {
            nilaiStyle = 'background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0;';
        } else if (nilai === 'A-') {
            nilaiStyle = 'background-color: #dcfce7; color: #166534; border: 1px solid #bbf7d0;';
        } else if (nilai === 'B+' || nilai === 'B') {
            nilaiStyle = 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;';
        } else if (nilai === 'B-') {
            nilaiStyle = 'background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe;';
        } else if (nilai === 'C+' || nilai === 'C') {
            nilaiStyle = 'background-color: #fefce8; color: #854d0e; border: 1px solid #fef08a;';
        } else {
            nilaiStyle = 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;';
        }
        document.getElementById('detNilai').innerText = nilai;
        document.getElementById('detNilai').style = nilaiStyle + " display: inline-block; padding: 4px 10px; font-weight: bold; border-radius: 4px; font-size: 12px; margin-top: 2px;";
        
        document.getElementById('detailModal').classList.add('show');
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.remove('show');
    }
    </script>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
