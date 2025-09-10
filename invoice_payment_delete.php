<?php
session_start();
require_once 'config/db.php';
require_once 'functions/suppliers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment'])) {
    try {
        $stmt = $pdo->prepare("SELECT i.supplier_id FROM invoice_payments ip JOIN invoices i ON ip.invoice_id = i.id WHERE ip.id = ?");
        $stmt->execute([$_POST['payment_id']]);
        $supplier_id = $stmt->fetchColumn();
        if ($supplier_id === false) {
            throw new Exception("Fatura ödemesi bulunamadı.");
        }

        deleteInvoicePayment($pdo, $_POST['payment_id']);
        header('Location: supplier_details.php?id=' . $supplier_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

header('Location: suppliers.php');
exit;
?>