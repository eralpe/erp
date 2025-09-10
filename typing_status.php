<?php
session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

try {
    if ($action === 'set' && $receiver_id) {
        // Yazma durumunu Redis veya bir geçici tabloya kaydet (basitlik için dosya kullanıyoruz)
        file_put_contents("typing_$receiver_id.txt", json_encode(['user_id' => $user_id, 'timestamp' => time()]));
        echo json_encode(['success' => true]);
    } elseif ($action === 'check' && $receiver_id) {
        $is_typing = false;
        $file = "typing_$user_id.txt";
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['user_id'] == $receiver_id && (time() - $data['timestamp']) < 5) {
                $is_typing = true;
            }
        }
        echo json_encode(['is_typing' => $is_typing]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Geçersiz işlem']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>