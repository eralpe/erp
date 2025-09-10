<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Oturum geçersiz']);
    exit;
}

require_once 'config/db.php';

try {
    // Kullanıcının bildirim tercihlerini çekme
    $stmt = $pdo->prepare("SELECT notification_type FROM user_notification_preferences WHERE user_id = ? AND is_enabled = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $enabled_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Varsayılan olarak tüm türler açıksa (tercih yoksa)
    if (empty($enabled_types)) {
        $enabled_types = ['stock', 'finance', 'chat', 'general'];
    }

    // Yeni bildirimler (son 10 saniye, aktif türler)
    $placeholders = implode(',', array_fill(0, count($enabled_types), '?'));
    $stmt = $pdo->prepare("
        SELECT id, type, message, created_at, priority 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND is_archived = 0 
        AND type IN ($placeholders)
        AND created_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ");
    $stmt->execute(array_merge([$_SESSION['user_id']], $enabled_types));
    $new_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Okunmamış bildirim sayısı (aktif türler)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND is_archived = 0 
        AND type IN ($placeholders)
    ");
    $stmt->execute(array_merge([$_SESSION['user_id']], $enabled_types));
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    echo json_encode([
        'success' => true,
        'notifications' => $new_notifications,
        'count' => $count
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>