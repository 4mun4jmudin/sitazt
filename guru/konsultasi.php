<?php
// guru/konsultasi.php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi untuk AJAX
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'guru_tahfidz') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Ambil detail profile guru_tahfidz
try {
    $stmt_guru = $pdo->prepare("SELECT id FROM guru_tahfidz WHERE user_id = :user_id");
    $stmt_guru->execute(['user_id' => $user_id]);
    $guru_profile = $stmt_guru->fetch();
    $guru_id = $guru_profile ? $guru_profile['id'] : 0;
} catch (\Exception $e) {
    $guru_id = 0;
}

// AJAX Endpoint Handlers
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $selected_parent_user_id = intval($_GET['parent_user_id'] ?? 0);
    
    if ($_GET['action'] === 'get_messages') {
        if ($selected_parent_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Wali murid tidak valid']);
            exit;
        }
        
        try {
            // Tandai pesan dari parent ke guru ini sebagai dibaca (is_read = 1)
            $stmt_mark = $pdo->prepare("
                UPDATE konsultasi 
                SET is_read = 1 
                WHERE pengirim_id = :parent_id AND penerima_id = :my_id AND is_read = 0
            ");
            $stmt_mark->execute([
                'parent_id' => $selected_parent_user_id,
                'my_id' => $user_id
            ]);
            
            // Ambil semua pesan
            $stmt_msgs = $pdo->prepare("
                SELECT * FROM konsultasi
                WHERE (pengirim_id = :my_id1 AND penerima_id = :their_id1)
                   OR (pengirim_id = :their_id2 AND penerima_id = :my_id2)
                ORDER BY created_at ASC
            ");
            $stmt_msgs->execute([
                'my_id1' => $user_id,
                'their_id1' => $selected_parent_user_id,
                'their_id2' => $selected_parent_user_id,
                'my_id2' => $user_id
            ]);
            $messages = $stmt_msgs->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit;
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    if ($_GET['action'] === 'delete_message') {
        $message_id = intval($_GET['message_id'] ?? 0);
        if ($message_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID pesan tidak valid']);
            exit;
        }
        
        try {
            $stmt_del = $pdo->prepare("
                DELETE FROM konsultasi 
                WHERE id = :id AND (pengirim_id = :my_id OR penerima_id = :my_id)
            ");
            $stmt_del->execute([
                'id' => $message_id,
                'my_id' => $user_id
            ]);
            echo json_encode(['success' => true]);
            exit;
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus pesan: ' . $e->getMessage()]);
            exit;
        }
    }
    
    if ($_GET['action'] === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $pesan = trim($_POST['message'] ?? '');
        if ($pesan === '') {
            echo json_encode(['success' => false, 'message' => 'Pesan kosong']);
            exit;
        }
        if ($selected_parent_user_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Wali murid tidak valid']);
            exit;
        }
        
        try {
            $stmt_send = $pdo->prepare("
                INSERT INTO konsultasi (pengirim_id, penerima_id, pesan, is_read, created_at)
                VALUES (:pengirim_id, :penerima_id, :pesan, 0, NOW())
            ");
            $stmt_send->execute([
                'pengirim_id' => $user_id,
                'penerima_id' => $selected_parent_user_id,
                'pesan' => $pesan
            ]);
            echo json_encode(['success' => true]);
            exit;
        } catch (\PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Gagal mengirim pesan']);
            exit;
        }
    }
}

// Untuk render HTML biasa, silakan load header.php
require_once 'header.php';

$error = '';
$parent_list = [];
$all_parents_list = [];
$selected_parent_user_id = 0;
$selected_parent = null;
$messages = [];

try {
    // 1. Ambil daftar orang tua (yang memiliki anak di kelas guru ini, atau pernah berkirim pesan dengan guru ini)
    $stmt_parents = $pdo->prepare("
        SELECT DISTINCT o.id AS ortu_id, o.user_id AS ortu_user_id, o.nama_lengkap AS nama_ortu, o.no_hp,
               (SELECT CONCAT(s.nama_lengkap, ' (Kelas: ', k.nama_kelas, ')') 
                FROM siswa s 
                JOIN kelas k ON s.kelas_id = k.id 
                WHERE s.orang_tua_id = o.id LIMIT 1) AS info_anak,
               (SELECT COUNT(*) FROM konsultasi WHERE pengirim_id = o.user_id AND penerima_id = :my_user_id3 AND is_read = 0) AS unread_count
        FROM orang_tua o
        LEFT JOIN siswa s ON s.orang_tua_id = o.id
        LEFT JOIN kelas k ON s.kelas_id = k.id
        WHERE k.wali_kelas_id = :guru_id
           OR o.user_id IN (
               SELECT pengirim_id FROM konsultasi WHERE penerima_id = :my_user_id1
               UNION
               SELECT penerima_id FROM konsultasi WHERE pengirim_id = :my_user_id2
           )
        ORDER BY o.nama_lengkap ASC
    ");
    $stmt_parents->execute([
        'guru_id' => $guru_id,
        'my_user_id1' => $user_id,
        'my_user_id2' => $user_id,
        'my_user_id3' => $user_id
    ]);
    $parent_list = $stmt_parents->fetchAll();
    
    // Ambil semua daftar orang tua untuk mulai chat baru
    $stmt_all_p = $pdo->query("SELECT user_id AS ortu_user_id, nama_lengkap AS nama_ortu FROM orang_tua ORDER BY nama_lengkap ASC");
    $all_parents_list = $stmt_all_p->fetchAll();
    
    // 2. Tentukan orang tua terpilih
    $selected_parent_user_id = intval($_GET['parent_user_id'] ?? 0);
    if ($selected_parent_user_id <= 0 && !empty($parent_list)) {
        // Cari parent pertama yang memiliki unread_count > 0, jika ada
        $first_unread = 0;
        foreach ($parent_list as $p) {
            if ($p['unread_count'] > 0) {
                $first_unread = $p['ortu_user_id'];
                break;
            }
        }
        $selected_parent_user_id = $first_unread > 0 ? $first_unread : $parent_list[0]['ortu_user_id'];
    }
    
    if ($selected_parent_user_id > 0) {
        // Ambil data detail orang tua terpilih
        foreach ($parent_list as $p) {
            if ($p['ortu_user_id'] == $selected_parent_user_id) {
                $selected_parent = $p;
                break;
            }
        }
        
        // Fallback: Jika terpilih dari pencarian mulai chat baru dan belum ada di list default
        if (!$selected_parent) {
            $stmt_p = $pdo->prepare("
                SELECT o.id AS ortu_id, o.user_id AS ortu_user_id, o.nama_lengkap AS nama_ortu, o.no_hp,
                       (SELECT CONCAT(s.nama_lengkap, ' (Kelas: ', k.nama_kelas, ')') 
                        FROM siswa s 
                        JOIN kelas k ON s.kelas_id = k.id 
                        WHERE s.orang_tua_id = o.id LIMIT 1) AS info_anak,
                       0 AS unread_count
                FROM orang_tua o
                WHERE o.user_id = :id
            ");
            $stmt_p->execute(['id' => $selected_parent_user_id]);
            $selected_parent = $stmt_p->fetch();
            if ($selected_parent) {
                // Masukkan ke list agar muncul di sidebar
                $parent_list[] = $selected_parent;
            }
        }
        
        // Ambil pesan konsultasi antara guru ini (user_id) dan orang tua terpilih
        if ($selected_parent) {
            // Tandai dibaca saat masuk
            $stmt_mark = $pdo->prepare("
                UPDATE konsultasi 
                SET is_read = 1 
                WHERE pengirim_id = :parent_id AND penerima_id = :my_id AND is_read = 0
            ");
            $stmt_mark->execute([
                'parent_id' => $selected_parent_user_id,
                'my_id' => $user_id
            ]);

            $stmt_msgs = $pdo->prepare("
                SELECT * FROM konsultasi
                WHERE (pengirim_id = :my_id1 AND penerima_id = :their_id1)
                   OR (pengirim_id = :their_id2 AND penerima_id = :my_id2)
                ORDER BY created_at ASC
            ");
            $stmt_msgs->execute([
                'my_id1' => $user_id,
                'their_id1' => $selected_parent_user_id,
                'their_id2' => $selected_parent_user_id,
                'my_id2' => $user_id
            ]);
            $messages = $stmt_msgs->fetchAll();
        }
    }
} catch (\PDOException $e) {
    $error = 'Gagal memuat obrolan konsultasi: ' . $e->getMessage();
}
?>

<style>
.chat-container-box {
    display: flex;
    flex-direction: column;
    height: 520px;
    background-color: #ffffff;
    border: 1px solid rgba(13, 92, 52, 0.1);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
}

.chat-header {
    padding: 16px 24px;
    background-color: #ffffff;
    border-bottom: 1.5px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-messages {
    flex: 1;
    padding: 24px;
    overflow-y: auto;
    background-color: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.chat-message-row {
    display: flex;
    width: 100%;
}

.chat-message-row.sent {
    justify-content: flex-end;
}

.chat-message-row.received {
    justify-content: flex-start;
}

.chat-bubble {
    max-width: 65%;
    padding: 12px 18px;
    font-size: 14px;
    line-height: 1.6;
    position: relative;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.01);
}

.chat-message-row.sent .chat-bubble {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: #ffffff;
    border-radius: 16px 16px 4px 16px;
}

.chat-message-row.received .chat-bubble {
    background-color: #ffffff;
    color: var(--text-main);
    border: 1.5px solid #e2e8f0;
    border-radius: 16px 16px 16px 4px;
}

.chat-time {
    display: block;
    font-size: 10px;
    margin-top: 6px;
    font-weight: 500;
}

.chat-message-row.sent .chat-time {
    color: rgba(255, 255, 255, 0.7);
    text-align: right;
}

.chat-message-row.received .chat-time {
    color: var(--text-muted);
    text-align: left;
}

.chat-footer {
    padding: 16px 24px;
    background-color: #ffffff;
    border-top: 1.5px solid #f1f5f9;
}

.chat-form {
    display: flex;
    gap: 12px;
}

.chat-input {
    flex: 1;
    padding: 12px 16px;
    font-family: var(--font-body);
    font-size: 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    outline: none;
    background-color: #f8fafc;
    transition: all var(--transition-speed);
}

.chat-input:focus {
    border-color: var(--primary-color);
    background-color: #ffffff;
    box-shadow: 0 0 0 3px rgba(13, 92, 52, 0.08);
}

.chat-send-btn {
    width: auto;
    padding: 12px 20px;
    border-radius: 10px;
}
</style>

<div style="margin-bottom: 25px;">
    <p style="font-size: 14px; color: var(--text-muted);">
        Konsultasikan kemajuan, kedisiplinan, maupun hambatan hafalan anak didik Anda secara langsung dengan orang tua wali.
    </p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?php echo htmlspecialchars($error); ?></div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2.5fr; gap: 25px; align-items: start; flex-wrap: wrap;">
    <!-- Bagian Kiri: Daftar Orang Tua Wali -->
    <div>
        <div class="admin-card-table" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1);">
            <div class="admin-card-header" style="padding: 18px 20px;">
                <h2>Daftar Wali Murid</h2>
            </div>
            
            <!-- Tombol & Form Mulai Chat Baru -->
            <div style="padding: 10px 15px 5px 15px;">
                <button onclick="toggleNewChatForm()" class="btn btn-primary btn-sm" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px; border-radius: 8px;">
                    <i class="fa-solid fa-plus"></i> Mulai Chat Baru
                </button>
            </div>
            
            <div id="newChatForm" style="display: none; padding: 10px 15px 5px 15px; border-bottom: 1.5px solid #f1f5f9;">
                <label style="font-size: 11px; font-weight: bold; color: var(--text-muted); display: block; margin-bottom: 5px;">Pilih Orang Tua Wali</label>
                <select onchange="if(this.value) location.href='konsultasi.php?parent_user_id=' + this.value" class="form-control" style="font-size: 13px; padding: 8px; border-radius: 8px; width: 100%;">
                    <option value="">-- Cari & Pilih Ortu --</option>
                    <?php foreach ($all_parents_list as $ap): ?>
                        <option value="<?php echo $ap['ortu_user_id']; ?>">
                            <?php echo htmlspecialchars($ap['nama_ortu']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Pencarian Percakapan Aktif -->
            <div style="padding: 5px 15px 10px 15px; border-bottom: 1.5px solid #f1f5f9;">
                <div style="position: relative;">
                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 12px;"></i>
                    <input type="text" id="searchParentInput" onkeyup="filterParents()" placeholder="Cari percakapan..." class="form-control" style="font-size: 12px; padding: 6px 10px 6px 30px; border-radius: 6px; width: 100%;">
                </div>
            </div>
            
            <div style="padding: 10px; display: flex; flex-direction: column; gap: 5px; max-height: 450px; overflow-y: auto;">
                <?php if (empty($parent_list)): ?>
                    <p style="padding: 15px; font-size: 13px; color: var(--text-muted); text-align: center;">Belum ada kontak wali murid.</p>
                <?php else: ?>
                    <?php foreach ($parent_list as $parent): 
                        $is_active = $parent['ortu_user_id'] == $selected_parent_user_id;
                        $bg_color = $is_active ? 'var(--primary-color)' : '#f8fafc';
                        $text_color = $is_active ? '#ffffff' : 'var(--text-main)';
                        $sub_color = $is_active ? 'rgba(255,255,255,0.75)' : 'var(--text-muted)';
                        $border = $is_active ? 'none' : '1px solid #e2e8f0';
                    ?>
                        <a href="konsultasi.php?parent_user_id=<?php echo $parent['ortu_user_id']; ?>" class="parent-chat-item" style="display: block; padding: 12px 15px; text-decoration: none; border-radius: 10px; background-color: <?php echo $bg_color; ?>; color: <?php echo $text_color; ?>; border: <?php echo $border; ?>; transition: all 0.2s;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <strong class="parent-name" style="font-size: 14px;"><?php echo htmlspecialchars($parent['nama_ortu']); ?></strong>
                                <?php if (isset($parent['unread_count']) && $parent['unread_count'] > 0): ?>
                                    <span style="background-color: var(--error-color); color: white; border-radius: 20px; padding: 1px 6px; font-size: 10px; font-weight: bold;"><?php echo $parent['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <span style="font-size: 11px; color: <?php echo $sub_color; ?>; display: block; margin-top: 3px; line-height: 1.3;">
                                Anak: <?php echo htmlspecialchars($parent['info_anak'] ?? '-'); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bagian Kanan: Ruang Percakapan -->
    <div>
        <?php if (!$selected_parent): ?>
            <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); text-align: center; padding: 60px 20px;">
                <i class="fa-regular fa-comments" style="font-size: 40px; color: var(--primary-color); margin-bottom: 15px;"></i>
                <h3>Ruang Konsultasi Belum Dipilih</h3>
                <p style="font-size: 13px; color: var(--text-muted); max-width: 400px; margin: 0 auto; margin-top: 5px;">
                    Silakan pilih wali murid dari panel di sebelah kiri untuk membuka ruang obrolan bimbingan tahfidz.
                </p>
            </div>
        <?php else: ?>
            <div class="chat-container-box">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 50%; background-color: rgba(13, 92, 52, 0.08); display: flex; justify-content: center; align-items: center; color: var(--primary-color); font-size: 18px;">
                            <i class="fa-solid fa-user-tie"></i>
                        </div>
                        <div>
                            <strong style="color: var(--primary-dark); font-size: 15px; display: block;">
                                <?php echo htmlspecialchars($selected_parent['nama_ortu']); ?>
                            </strong>
                            <span style="font-size: 11px; color: var(--text-muted);">
                                Orang Tua/Wali dari <?php echo htmlspecialchars($selected_parent['info_anak'] ?? '-'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($selected_parent['no_hp']): ?>
                        <div>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $selected_parent['no_hp']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; width: auto; font-family: var(--font-body); font-weight: 500; font-size: 12px; color: #16a34a; background-color: #f0fdf4; border-color: rgba(22, 163, 74, 0.15);">
                                <i class="fa-brands fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Chat Message List -->
                <div class="chat-messages" id="chatContainer">
                    <!-- Data loaded dynamically via JS -->
                </div>

                <!-- Chat Footer Input -->
                <div class="chat-footer">
                    <form id="chatForm" class="chat-form" onsubmit="sendMessage(event)">
                        <input type="text" id="messageInput" name="message" class="chat-input" placeholder="Tulis pesan Anda kepada wali murid..." required autocomplete="off">
                        <button type="submit" class="btn btn-primary chat-send-btn">
                            <span>Kirim</span>
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>

            <script>
            const myUserId = <?php echo json_encode($user_id); ?>;
            const selectedParentUserId = <?php echo json_encode($selected_parent_user_id); ?>;
            const chatContainer = document.getElementById('chatContainer');
            const chatForm = document.getElementById('chatForm');
            const messageInput = document.getElementById('messageInput');
            let lastMessageCount = 0;

            function escapeHtml(text) {
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return text.replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            function formatDate(dateStr) {
                const d = new Date(dateStr);
                const today = new Date();
                const yesterday = new Date();
                yesterday.setDate(today.getDate() - 1);
                
                if (d.toDateString() === today.toDateString()) {
                    return 'Hari Ini';
                } else if (d.toDateString() === yesterday.toDateString()) {
                    return 'Kemarin';
                } else {
                    const options = { day: 'numeric', month: 'long', year: 'numeric' };
                    return d.toLocaleDateString('id-ID', options);
                }
            }

            function loadMessages(shouldScroll = false) {
                if (selectedParentUserId <= 0) return;
                
                fetch('konsultasi.php?action=get_messages&parent_user_id=' + selectedParentUserId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const messages = data.messages;
                            
                            if (messages.length === 0) {
                                chatContainer.innerHTML = `
                                    <div style="margin: auto; text-align: center; color: var(--text-muted); max-width: 350px;">
                                        <i class="fa-regular fa-comment-dots" style="font-size: 36px; margin-bottom: 12px; color: #cbd5e1; display: block;"></i>
                                        <p style="font-size: 14px; font-weight: 600; color: var(--text-main);">Mulai Konsultasi</p>
                                        <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                            Kirimkan pesan konsultasi Anda mengenai hafalan siswa kepada orang tua wali murid di sini.
                                        </p>
                                    </div>
                                `;
                                return;
                            }

                            let html = '';
                            let lastDate = null;

                            messages.forEach(msg => {
                                const msgDate = new Date(msg.created_at).toDateString();
                                if (msgDate !== lastDate) {
                                    lastDate = msgDate;
                                    html += `<div class="chat-date-divider" style="text-align: center; margin: 15px 0; font-size: 11px; font-weight: 600; color: var(--text-muted); align-self: center; background: #e2e8f0; padding: 4px 12px; border-radius: 12px;">${formatDate(msg.created_at)}</div>`;
                                }

                                const isSent = msg.pengirim_id == myUserId;
                                const rowClass = isSent ? 'sent' : 'received';
                                const timeStr = new Date(msg.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }).replace('.', ':');

                                html += `
                                    <div class="chat-message-row ${rowClass}">
                                        <div class="chat-bubble">
                                            <div style="word-break: break-word; white-space: pre-wrap;">${escapeHtml(msg.pesan)}</div>
                                            <div style="display: flex; align-items: center; justify-content: ${isSent ? 'flex-end' : 'flex-start'}; gap: 8px; margin-top: 4px;">
                                                <span class="chat-time" style="margin-top: 0;">${timeStr}</span>
                                                <button class="delete-msg-btn" onclick="deleteMessage(${msg.id})" title="Hapus Pesan" style="background: none; border: none; padding: 0; cursor: pointer; color: ${isSent ? 'rgba(255,255,255,0.6)' : 'var(--error-color)'}; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px;">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            chatContainer.innerHTML = html;

                            // Scroll to bottom if we have new messages or explicitly asked
                            if (shouldScroll || messages.length > lastMessageCount) {
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            }
                            
                            lastMessageCount = messages.length;
                        }
                    })
                    .catch(err => console.error('Error fetching messages:', err));
            }

            function deleteMessage(messageId) {
                if (!confirm('Apakah Anda yakin ingin menghapus pesan ini?')) return;
                
                fetch('konsultasi.php?action=delete_message&message_id=' + messageId + '&parent_user_id=' + selectedParentUserId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadMessages(false);
                        } else {
                            alert('Gagal menghapus pesan: ' + (data.message || 'Error tidak diketahui'));
                        }
                    })
                    .catch(err => console.error('Error deleting message:', err));
            }

            function sendMessage(event) {
                event.preventDefault();
                const message = messageInput.value.trim();
                if (!message || selectedParentUserId <= 0) return;

                const formData = new FormData();
                formData.append('message', message);

                messageInput.value = '';

                fetch('konsultasi.php?action=send_message&parent_user_id=' + selectedParentUserId, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadMessages(true);
                    } else {
                        alert('Gagal mengirim pesan: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error sending message:', err);
                    alert('Gagal mengirim pesan.');
                });
            }

            // Initial Load
            loadMessages(true);

            // Poll messages every 3 seconds
            setInterval(() => loadMessages(false), 3000);

            // Tampilkan/Sembunyikan Form Chat Baru
            function toggleNewChatForm() {
                var form = document.getElementById('newChatForm');
                if (form.style.display === 'none') {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            }

            // Cari Kontak Wali Murid
            function filterParents() {
                var query = document.getElementById('searchParentInput').value.toLowerCase();
                var items = document.querySelectorAll('.parent-chat-item');
                items.forEach(function(item) {
                    var name = item.querySelector('.parent-name').innerText.toLowerCase();
                    if (name.includes(query)) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }
            </script>
        <?php endif; ?>
    </div>
</div>

</main>
</div>
</div>
</body>
</html>
