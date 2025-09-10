<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['csrf_token']) || $_SERVER['HTTP_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz oturum veya CSRF token']);
    exit;
}

require_once 'config/db.php';

$action = $_POST['action'] ?? '';
$response = ['success' => false, 'error' => 'Geçersiz işlem'];

try {
    if ($action === 'read' || $action === 'unread') {
        $is_read = $action === 'read' ? 1 : 0;
        if (isset($_POST['id'])) {
            // Tekil işlem
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$is_read, $_POST['id'], $_SESSION['user_id']]);
            $response = ['success' => $stmt->rowCount() > 0];
        } elseif (isset($_POST['ids'])) {
            // Toplu işlem
            $ids = explode(',', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = ? WHERE id IN ($placeholders) AND user_id = ?");
            $params = array_merge([$is_read], $ids, [$_SESSION['user_id']]);
            $stmt->execute($params);
            $response = ['success' => $stmt->rowCount() > 0];
        }
    } elseif ($action === 'archive' || $action === 'unarchive') {
        $is_archived = $action === 'archive' ? 1 : 0;
        if (isset($_POST['id'])) {
            // Tekil arşivleme/geri yükleme
            $stmt = $pdo->prepare("UPDATE notifications SET is_archived = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$is_archived, $_POST['id'], $_SESSION['user_id']]);
            $response = ['success' => $stmt->rowCount() > 0];
        } elseif (isset($_POST['ids'])) {
            // Toplu arşivleme/geri yükleme
            $ids = explode(',', $_POST['ids']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("UPDATE notifications SET is_archived = ? WHERE id IN ($placeholders) AND user_id = ?");
            $params = array_merge([$is_archived], $ids, [$_SESSION['user_id']]);
            $stmt->execute($params);
            $response = ['success' => $stmt->rowCount() > 0];
        }
    }
} catch (PDOException $e) {
    $response = ['success' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()];
}

echo json_encode($response);
?>