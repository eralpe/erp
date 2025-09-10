<?php
session_start();
require_once 'config/db.php';
$page_title = "Mesajlar";

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => 'Mesajlar', 'url' => '']
];

ob_start();

// Hata ve başarı mesajları
$error = '';
$success = '';

// Oturum açmış kullanıcı ID'si
$user_id = $_SESSION['user_id'];

// Kullanıcı listesi
try {
    $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id != ?");
    $stmt->execute([$user_id]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kullanıcılar yüklenemedi: " . $e->getMessage();
}

// Seçilen kullanıcı
$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($selected_user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
        $stmt->execute([$user_id, $selected_user_id]);
    } catch (PDOException $e) {
        $error = "Mesajlar güncellenemedi: " . $e->getMessage();
    }
}
?>

<div class="card">
    <div class="card-body d-flex flex-column">
        <div class="bootstrap snippets bootdey flex-grow-1">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <div class="row h-100">
                <!-- Üye Listesi -->
                <div class="col-md-4 bg-white d-flex flex-column">
                    <div class="row border-bottom p-3" style="height: 40px;">
                        Üyeler
                    </div>
                    <ul class="friend-list flex-grow-1 overflow-auto">
                        <?php foreach ($users as $user): ?>
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT message, created_at, is_read 
                                FROM chat_messages 
                                WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                                ORDER BY created_at DESC LIMIT 1
                            ");
                            $stmt->execute([$user_id, $user['id'], $user['id'], $user_id]);
                            $last_message = $stmt->fetch(PDO::FETCH_ASSOC);

                            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
                            $stmt->execute([$user_id, $user['id']]);
                            $unread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                            ?>
                            <li class="<?php echo $selected_user_id == $user['id'] ? 'active bounceInDown' : ''; ?>">
                                <a href="?user_id=<?php echo $user['id']; ?>" class="clearfix">
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="" class="img-circle">
                                    <div class="friend-name">
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </div>
                                    <div class="last-message text-muted">
                                        <?php echo $last_message ? htmlspecialchars(substr($last_message['message'], 0, 50)) : 'Mesaj yok'; ?>
                                    </div>
                                    <small class="time text-muted">
                                        <?php echo $last_message ? date('d.m.Y H:i', strtotime($last_message['created_at'])) : ''; ?>
                                    </small>
                                    <?php if ($unread_count > 0): ?>
                                        <small class="chat-alert label label-danger"><?php echo $unread_count; ?></small>
                                    <?php elseif ($last_message && $last_message['is_read']): ?>
                                        <small class="chat-alert text-muted"><i class="fa fa-check"></i></small>
                                    <?php else: ?>
                                        <small class="chat-alert text-muted"><i class="fa fa-reply"></i></small>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <!-- Mesajlaşma Alanı -->
                <div class="col-md-8 bg-white d-flex flex-column">
                    <div class="chat-message flex-grow-1 overflow-y-scroll">
                        <ul class="chat" id="chat-messages">
                            <?php if ($selected_user_id): ?>
                                <?php
                                $stmt = $pdo->prepare("
                                    SELECT cm.*, u.username, u.avatar 
                                    FROM chat_messages cm 
                                    JOIN users u ON u.id = cm.sender_id 
                                    WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) 
                                    ORDER BY cm.created_at ASC
                                ");
                                $stmt->execute([$user_id, $selected_user_id, $selected_user_id, $user_id]);
                                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($messages as $message):
                                    $is_right = $message['sender_id'] == $user_id;
                                ?>
                                    <li class="<?php echo $is_right ? 'right' : 'left'; ?> clearfix">
                                        <span class="chat-img <?php echo $is_right ? 'pull-right' : 'pull-left'; ?>">
                                            <img src="<?php echo htmlspecialchars($message['avatar']); ?>" alt="User Avatar">
                                        </span>
                                        <div class="chat-body clearfix">
                                            <div class="header">
                                                <strong class="primary-font"><?php echo htmlspecialchars($message['username']); ?></strong>
                                                <small class="pull-right text-muted">
                                                    <i class="fa fa-clock-o"></i> <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p><?php echo htmlspecialchars($message['message']); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="text-center">Bir kullanıcı seçin</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <?php if ($selected_user_id): ?>
                        <div class="chat-box bg-white">
                            <div id="typing-indicator" class="text-muted small p-2" style="display: none;">Yazıyor...</div>
                            <div class="input-group">
                                <input id="chat-input" class="form-control border no-shadow no-rounded" placeholder="Mesajınızı yazın">
                                <span class="input-group-btn">
                                    <button class="btn btn-success no-rounded" type="button" onclick="sendMessage()">Gönder</button>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.friend-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.friend-list li {
    border-bottom: 1px solid #eee;
}
.friend-list li a img {
    float: left;
    width: 45px;
    height: 45px;
    margin-right: 10px;
}
.friend-list li a {
    position: relative;
    display: block;
    padding: 10px;
    transition: all .2s ease;
}
.friend-list li.active a {
    background-color: #f1f5fc;
}
.friend-list li a .friend-name, 
.friend-list li a .friend-name:hover {
    color: #777;
}
.friend-list li a .last-message {
    width: 65%;
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
}
.friend-list li a .time {
    position: absolute;
    top: 10px;
    right: 8px;
}
.friend-list li a .chat-alert {
    position: absolute;
    right: 8px;
    top: 27px;
    font-size: 10px;
    padding: 3px 5px;
}
.chat-message {
    padding: 20px;
    height: 100%;
    overflow-y: scroll;
    position: relative;
    scrollbar-width: thin; /* Firefox için */
    scrollbar-color: #888 #f9f9f9; /* Firefox için */
}
.chat-message::-webkit-scrollbar {
    width: 8px; /* Webkit tarayıcılar için kaydırma çubuğu genişliği */
}
.chat-message::-webkit-scrollbar-track {
    background: #f9f9f9; /* Kaydırma çubuğu arka planı */
}
.chat-message::-webkit-scrollbar-thumb {
    background: #888; /* Kaydırma çubuğu rengi */
    border-radius: 4px;
}
.chat-message::-webkit-scrollbar-thumb:hover {
    background: #555; /* Üzerine gelindiğinde renk */
}
.chat {
    list-style: none;
    margin: 0;
    padding: 0;
}
.chat-message {
    background: #f9f9f9;
}
.chat li img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
}
.chat-body {
    padding-bottom: 20px;
}
.chat li.left .chat-body {
    margin-left: 70px;
    background-color: #fff;
}
.chat li.right .chat-body {
    margin-right: 70px;
    background-color: #fff;
}
.chat li .chat-body {
    position: relative;
    font-size: 11px;
    padding: 10px;
    border: 1px solid #f1f5fc;
    box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.chat li .chat-body .header {
    padding-bottom: 5px;
    border-bottom: 1px solid #f1f5fc;
}
.chat li .chat-body p {
    margin: 0;
}
.chat li.left .chat-body:before {
    position: absolute;
    top: 10px;
    left: -8px;
    display: inline-block;
    background: #fff;
    width: 16px;
    height: 16px;
    border-top: 1px solid #f1f5fc;
    border-left: 1px solid #f1f5fc;
    content: '';
    transform: rotate(-45deg);
}
.chat li.right .chat-body:before {
    position: absolute;
    top: 10px;
    right: -8px;
    display: inline-block;
    background: #fff;
    width: 16px;
    height: 16px;
    border-top: 1px solid #f1f5fc;
    border-right: 1px solid #f1f5fc;
    content: '';
    transform: rotate(45deg);
}
.chat li {
    margin: 15px 0;
}
.chat-box {
    padding: 15px;
    border-top: 1px solid #eee;
}
.primary-font {
    color: #3c8dbc;
}
body.dark-mode .chat-message {
    background: #343a40;
}
body.dark-mode .chat-message::-webkit-scrollbar-track {
    background: #343a40;
}
body.dark-mode .chat-message::-webkit-scrollbar-thumb {
    background: #adb5bd;
}
body.dark-mode .chat-message::-webkit-scrollbar-thumb:hover {
    background: #ced4da;
}
body.dark-mode .chat li .chat-body {
    background: #495057;
    color: #f8f9fa;
}
body.dark-mode .chat-box {
    background: #343a40;
    border-top: 1px solid #495057;
}
body.dark-mode .friend-list li {
    border-bottom: 1px solid #495057;
}
body.dark-mode .friend-list li.active a {
    background-color: #495057;
}
body.dark-mode .friend-list li a .friend-name,
body.dark-mode .friend-list li a .friend-name:hover {
    color: #f8f9fa;
}
body.dark-mode .friend-list li a .last-message {
    color: #adb5bd;
}
body.dark-mode .friend-list li a .time {
    color: #adb5bd;
}
body.dark-mode #typing-indicator {
    color: #adb5bd;
}
.main-content {
    min-height: calc(100vh - 150px); /* Navbar ve footer yüksekliği tahmini */
}
.card.h-100 {
    height: 100%;
}
.card-body {
    padding: 0;
}
.bootstrap.snippets.bootdey {
    height: 100%;
}
@media (max-width: 768px) {
    .row.h-100 {
        flex-direction: column;
    }
    .col-md-4, .col-md-8 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    .chat-message {
        max-height: 300px;
    }
    .friend-list {
        max-height: 200px;
        overflow-y: auto;
    }
}
</style>

<script>
function sendMessage() {
    const input = document.getElementById('chat-input');
    const message = input.value.trim();
    if (!message) return;

    fetch('send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receiver_id=<?php echo $selected_user_id; ?>&message=${encodeURIComponent(message)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            loadMessages();
            fetch('check_notifications.php');
            // Yazma durumunu sıfırla
            fetch('typing_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=<?php echo $selected_user_id; ?>&action=set`
            });
        } else {
            alert('Mesaj gönderilemedi: ' + (data.error || 'Bilinmeyen hata'));
        }
    })
    .catch(error => console.error('Mesaj gönderme hatası:', error));
}

function loadMessages() {
    const chatMessages = document.getElementById('chat-messages');
    console.log('Mesajlar yükleniyor...');
    fetch(`get_messages.php?user_id=<?php echo $selected_user_id; ?>`)
        .then(response => response.json())
        .then(data => {
            console.log('Mesajlar alındı:', data);
            chatMessages.innerHTML = '';
            if (data.messages.length === 0) {
                chatMessages.innerHTML = '<li class="text-center">Mesaj yok</li>';
                return;
            }
            data.messages.forEach(msg => {
                const isRight = msg.sender_id == <?php echo $user_id; ?>;
                const li = document.createElement('li');
                li.className = isRight ? 'right clearfix' : 'left clearfix';
                li.innerHTML = `
                    <span class="chat-img ${isRight ? 'pull-right' : 'pull-left'}">
                        <img src="${msg.avatar}" alt="User Avatar">
                    </span>
                    <div class="chat-body clearfix">
                        <div class="header">
                            <strong class="primary-font">${msg.username}</strong>
                            <small class="pull-right text-muted">
                                <i class="fa fa-clock-o"></i> ${new Date(msg.created_at).toLocaleString('tr-TR')}
                            </small>
                        </div>
                        <p>${msg.message}</p>
                    </div>
                `;
                chatMessages.appendChild(li);
            });
            setTimeout(() => {
                console.log('Kaydırma denendi, scrollHeight:', chatMessages.scrollHeight);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 100);
        })
        .catch(error => console.error('Mesaj yükleme hatası:', error));
}

function checkTypingStatus() {
    fetch('typing_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `receiver_id=<?php echo $selected_user_id; ?>&action=check`
    })
    .then(response => response.json())
    .then(data => {
        const typingIndicator = document.getElementById('typing-indicator');
        if (data.is_typing) {
            typingIndicator.style.display = 'block';
        } else {
            typingIndicator.style.display = 'none';
        }
    })
    .catch(error => console.error('Yazma durumu kontrol hatası:', error));
}

// Enter tuşu ve yazma durumu
document.addEventListener('DOMContentLoaded', () => {
    const chatInput = document.getElementById('chat-input');
    if (chatInput) {
        chatInput.addEventListener('keypress', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
        chatInput.addEventListener('input', () => {
            fetch('typing_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `receiver_id=<?php echo $selected_user_id; ?>&action=set`
            }).catch(error => console.error('Yazma durumu gönderme hatası:', error));
        });
    }
});

<?php if ($selected_user_id): ?>
setInterval(loadMessages, 5000);
setInterval(checkTypingStatus, 2000);
loadMessages();
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>