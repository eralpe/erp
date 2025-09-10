<?php
session_start();
require_once 'config/db.php';
require_once 'functions/cash.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_transaction'])) {
    try {
        deleteCashTransaction($pdo, $_POST['transaction_id']);
        header('Location: cash.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

header('Location: cash.php');
exit;
?>