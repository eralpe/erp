<?php
// Mevcut functions.php içeriği korunacak, sadece yeni fonksiyon eklenecek
require_once 'config/db.php';

function createNotification($user_id, $type, $message, $priority = 'medium') {
    global $pdo;

    try {
        // Kullanıcının bildirim tercihini kontrol et
        $stmt = $pdo->prepare("
            SELECT is_enabled 
            FROM user_notification_preferences 
            WHERE user_id = ? AND notification_type = ?
        ");
        $stmt->execute([$user_id, $type]);
        $is_enabled = $stmt->fetchColumn();

        // Tercih yoksa varsayılan olarak açık kabul et
        if ($is_enabled === false) {
            $is_enabled = 1;
        }

        // Bildirim ekle (sadece tercih açıksa)
        if ($is_enabled) {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, message, created_at, priority) 
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([$user_id, $type, $message, $priority]);
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Bildirim oluşturma hatası: " . $e->getMessage());
        return false;
    }
}
// functions.php’ye ekle
function cacheQuery($pdo, $query, $params, $cache_key, $ttl = 300) {
    $cache_file = 'cache/' . md5($cache_key) . '.cache';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $ttl) {
        return unserialize(file_get_contents($cache_file));
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents($cache_file, serialize($data));
    return $data;
}
?>