<?php
// orang_tua/konsultasi.php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi untuk AJAX
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'orang_tua') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// AJAX Endpoint Handlers
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // Tentukan anak aktif terlebih dahulu untuk mencari guru
    $selected_child_id = $_SESSION['selected_child_id'] ?? null;
    if (!$selected_child_id) {
        try {
            $stmt_ortu = $pdo->prepare("SELECT id FROM orang_tua WHERE user_id = :user_id");
            $stmt_ortu->execute(['user_id' => $user_id]);
            $ortu = $stmt_ortu->fetch();
            if ($ortu) {
                $stmt_anak = $pdo->prepare("SELECT id FROM siswa WHERE orang_tua_id = :ortu_id LIMIT 1");
                $stmt_anak->execute(['ortu_id' => $ortu['id']]);
                $selected_child_id = $stmt_anak->fetchColumn();
            }
        } catch (\Exception $e) {}
    }
    
    $teacher_user_id = null;
    if ($selected_child_id) {
        try {
            $stmt_teacher = $pdo->prepare("
                SELECT gt.user_id AS teacher_user_id
                FROM siswa s
                JOIN kelas k ON s.kelas_id = k.id
                JOIN guru_tahfidz gt ON k.wali_kelas_id = gt.id
                WHERE s.id = :siswa_id
            ");
            $stmt_teacher->execute(['siswa_id' => $selected_child_id]);
            $teacher_user_id = $stmt_teacher->fetchColumn();
        } catch (\Exception $e) {}
    }
    
    if ($_GET['action'] === 'get_messages') {
        if (!$teacher_user_id) {
            echo json_encode(['success' => false, 'message' => 'Teacher not found']);
            exit;
        }
        
        try {
            // Tandai pesan dari guru ke parent ini sebagai dibaca (is_read = 1)
            $stmt_mark = $pdo->prepare("
                UPDATE konsultasi 
                SET is_read = 1 
                WHERE pengirim_id = :teacher_id AND penerima_id = :my_id AND is_read = 0
            ");
            $stmt_mark->execute([
                'teacher_id' => $teacher_user_id,
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
                'their_id1' => $teacher_user_id,
                'their_id2' => $teacher_user_id,
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
    
    if ($_GET['action'] === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $pesan = trim($_POST['message'] ?? '');
        if ($pesan === '') {
            echo json_encode(['success' => false, 'message' => 'Pesan kosong']);
            exit;
        }
        if (!$teacher_user_id) {
            echo json_encode(['success' => false, 'message' => 'Guru pembimbing tidak ditemukan']);
            exit;
        }
        
        try {
            $stmt_send = $pdo->prepare("
                INSERT INTO konsultasi (pengirim_id, penerima_id, pesan, is_read, created_at)
                VALUES (:pengirim_id, :penerima_id, :pesan, 0, NOW())
            ");
            $stmt_send->execute([
                'pengirim_id' => $user_id,
                'penerima_id' => $teacher_user_id,
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

// Render normal page
require_once 'header.php';

$error = '';
$teacher = null;
$messages = [];

if ($anak_aktif) {
    try {
        // Ambil kelas anak aktif dan wali kelas (Guru Tahfidz) nya
        $stmt_teacher = $pdo->prepare("
            SELECT gt.id AS guru_id, gt.user_id AS teacher_user_id, gt.nama_lengkap AS nama_guru, gt.no_hp, k.nama_kelas
            FROM siswa s
            JOIN kelas k ON s.kelas_id = k.id
            JOIN guru_tahfidz gt ON k.wali_kelas_id = gt.id
            WHERE s.id = :siswa_id
        ");
        $stmt_teacher->execute(['siswa_id' => $anak_aktif['id']]);
        $teacher = $stmt_teacher->fetch();
        
        if ($teacher) {
            // Tandai pesan dari guru ke parent ini sebagai dibaca (is_read = 1) saat masuk halaman
            $stmt_mark = $pdo->prepare("
                UPDATE konsultasi 
                SET is_read = 1 
                WHERE pengirim_id = :teacher_id AND penerima_id = :my_id AND is_read = 0
            ");
            $stmt_mark->execute([
                'teacher_id' => $teacher['teacher_user_id'],
                'my_id' => $user_id
            ]);

            // Ambil pesan konsultasi antara orang tua saat ini dan guru tahfidz tersebut
            $stmt_msgs = $pdo->prepare("
                SELECT * FROM konsultasi
                WHERE (pengirim_id = :my_id1 AND penerima_id = :their_id1)
                   OR (pengirim_id = :their_id2 AND penerima_id = :my_id2)
                ORDER BY created_at ASC
            ");
            $stmt_msgs->execute([
                'my_id1' => $user_id,
                'their_id1' => $teacher['teacher_user_id'],
                'their_id2' => $teacher['teacher_user_id'],
                'my_id2' => $user_id
            ]);
            $messages = $stmt_msgs->fetchAll();
        }
    } catch (\PDOException $e) {
        $error = 'Gagal memuat data konsultasi: ' . $e->getMessage();
    }
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
    <!-- Ruang Konsultasi -->
    <div style="margin-bottom: 25px;">
        <h2 style="font-size: 20px; font-family: var(--font-heading); color: var(--primary-dark); margin-bottom: 6px;">
            Konsultasi Wali Murid
        </h2>
        <p style="font-size: 14px; color: var(--text-muted);">
            Hubungi ustadz/ustadzah pembimbing tahfidz untuk konsultasi perkembangan hafalan ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 25px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <?php if (!$teacher): ?>
        <div class="card" style="box-shadow: none; border: 1px solid rgba(13, 92, 52, 0.1); width: 100%; max-width: 100%; text-align: center; padding: 60px 20px;">
            <div style="width: 70px; height: 70px; border-radius: 50%; background-color: rgba(239, 68, 68, 0.05); display: flex; justify-content: center; align-items: center; margin: 0 auto 20px; color: var(--error-color); font-size: 28px;">
                <i class="fa-solid fa-user-slash"></i>
            </div>
            <h3 style="font-family: var(--font-heading); color: #991b1b; font-size: 16px; margin-bottom: 8px;">Guru Pembimbing Belum Diplot</h3>
            <p style="font-size: 13px; color: var(--text-muted); max-width: 450px; margin: 0 auto; line-height: 1.5;">
                Ananda <strong><?php echo htmlspecialchars($anak_aktif['nama_lengkap']); ?></strong> belum memiliki guru pembimbing tahfidz atau kelas tahfidz yang ditunjuk. Silakan hubungi Administrator sekolah untuk memplot guru pembimbing tahfidz.
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
                        <strong style="color: var(--primary-dark); font-size: 15px; display: block;"><?php echo htmlspecialchars($teacher['nama_guru']); ?></strong>
                        <span style="font-size: 11px; color: var(--text-muted);">Guru Pembimbing Kelas <?php echo htmlspecialchars($teacher['nama_kelas']); ?></span>
                    </div>
                </div>
                
                <?php if ($teacher['no_hp']): ?>
                    <div>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $teacher['no_hp']); ?>" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; width: auto; font-family: var(--font-body); font-weight: 500; font-size: 12px; color: #16a34a; background-color: #f0fdf4; border-color: rgba(22, 163, 74, 0.15);">
                            <i class="fa-brands fa-whatsapp"></i> Hubungi via WhatsApp
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
                    <input type="text" id="messageInput" name="message" class="chat-input" placeholder="Tulis pesan konsultasi Anda di sini..." required autocomplete="off">
                    <button type="submit" class="btn btn-primary chat-send-btn">
                        <span>Kirim</span>
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>

        <script>
        const myUserId = <?php echo json_encode($user_id); ?>;
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
            fetch('konsultasi.php?action=get_messages')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const messages = data.messages;
                        
                        if (messages.length === 0) {
                            chatContainer.innerHTML = `
                                <div style="margin: auto; text-align: center; color: var(--text-muted); max-width: 350px;">
                                    <i class="fa-regular fa-comments" style="font-size: 36px; margin-bottom: 12px; color: #cbd5e1; display: block;"></i>
                                    <p style="font-size: 14px; font-weight: 600; color: var(--text-main);">Mulai Konsultasi</p>
                                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px; line-height: 1.4;">
                                        Kirimkan pesan pertama Anda kepada ustadz/ustadzah untuk mendiskusikan perkembangan hafalan anak Anda.
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
                                        <span class="chat-time">${timeStr}</span>
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

        function sendMessage(event) {
            event.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            const formData = new FormData();
            formData.append('message', message);

            messageInput.value = '';

            fetch('konsultasi.php?action=send_message', {
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
        </script>
    <?php endif; ?>
<?php endif; ?>

</main>
</div>
</div>
</body>
</html>
