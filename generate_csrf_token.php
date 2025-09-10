<?php
session_start();
require_once 'config/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $invoice_id = $input['invoice_id'] ?? null;

    if (!$invoice_id || !is_numeric($invoice_id)) {
        $error = 'Geçersiz fatura ID: ' . ($invoice_id ?? 'none');
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', $error]);
        echo json_encode(['error' => $error]);
        exit;
    }

    if (!isset($_SESSION['single_payment_tokens'])) {
        $_SESSION['single_payment_tokens'] = [];
    }

    $new_token = bin2hex(random_bytes(32));
    $_SESSION['single_payment_tokens'][$invoice_id] = $new_token;

    // Token oluşturma logu
    $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
    $stmt->execute(['generate_csrf_token', "New CSRF token for invoice $invoice_id: $new_token"]);

    echo json_encode(['csrf_token' => $new_token]);
    exit;
}

$error = 'Geçersiz istek';
$stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
$stmt->execute(['error', $error]);
echo json_encode(['error' => $error]);
?>