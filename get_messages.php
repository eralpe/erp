<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if (!$other_user_id) {
    echo json_encode(['messages' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT cm.*, u.username, u.avatar 
        FROM chat_messages cm 
        JOIN users u ON u.id = cm.sender_id 
        WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) 
        ORDER BY cm.created_at ASC
    ");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mesajları okundu olarak işaretle
    $stmt = $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$user_id, $other_user_id]);

    echo json_encode(['messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>