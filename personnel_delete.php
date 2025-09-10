<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_personnel'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM personnel WHERE id = ?");
        $stmt->execute([$_POST['personnel_id']]);
        header('Location: personnel.php');
        exit;
    } catch (Exception $e) {
        $error = "Personel silinirken hata oluştu: " . $e->getMessage();
    }
}

header('Location: personnel.php');
exit;
?>