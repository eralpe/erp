<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_supplier'])) {
    try {
        // Check if supplier has invoices
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE supplier_id = ?");
        $stmt->execute([$_POST['supplier_id']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Bu tedarikçiye ait faturalar var, önce faturaları silin.");
        }

        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$_POST['supplier_id']]);
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

header('Location: suppliers.php');
exit;
?>