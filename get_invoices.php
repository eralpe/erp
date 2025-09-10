<?php
require_once 'config/db.php';
header('Content-Type: application/json');
try {
    $supplier_id = isset($_GET['supplier_id']) ? intval($_GET['supplier_id']) : null;
    if (!$supplier_id) {
        echo json_encode(['error' => 'Tedarikçi ID belirtilmedi']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id, invoice_number, amount, issue_date, due_date, status 
                           FROM invoices 
                           WHERE supplier_id = ? AND status = 'pending' 
                           GROUP BY id, invoice_number, amount, issue_date, due_date, status 
                           ORDER BY issue_date DESC");
    $stmt->execute([$supplier_id]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
    $stmt->execute(['info', "get_invoices.php: {$supplier_id} için " . count($invoices) . " fatura döndürüldü"]);
    echo json_encode($invoices);
} catch (Exception $e) {
    $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
    $stmt->execute(['error', "get_invoices.php hatası: {$e->getMessage()}"]);
    echo json_encode(['error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}
?>