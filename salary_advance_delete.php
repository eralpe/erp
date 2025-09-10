<?php
session_start();
require_once 'config/db.php';
require_once 'functions/salary_advances.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_advance'])) {
    try {
        $stmt = $pdo->prepare("SELECT personnel_id FROM salary_advances WHERE id = ?");
        $stmt->execute([$_POST['advance_id']]);
        $personnel_id = $stmt->fetchColumn();
        if ($personnel_id === false) {
            throw new Exception("Maaş avansı bulunamadı.");
        }

        deleteSalaryAdvance($pdo, $_POST['advance_id']);
        header('Location: personnel_details.php?id=' . $personnel_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

header('Location: personnel.php');
exit;
?>