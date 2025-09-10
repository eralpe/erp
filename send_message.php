<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$receiver_id || !$message) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz veri']);
    exit;
}

try {
    // Mesajı kaydet
    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $receiver_id, $message]);

    // Gönderenin kullanıcı adını al
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $sender_username = $stmt->fetchColumn();

    // Bildirim oluştur
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message, created_at)
        VALUES (?, 'chat', ?, NOW())
    ");
    $stmt->execute([$receiver_id, "Yeni mesaj from {$sender_username}: " . substr($message, 0, 50)]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>