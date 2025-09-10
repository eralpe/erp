<?php
require_once __DIR__ . '/suppliers.php';
require_once __DIR__ . '/reminders.php';
require_once __DIR__ . '/currency.php';

function addPurchaseOrder($pdo, $supplier_id, $order_number, $total_amount, $currency, $order_date, $expected_delivery_date, $description) {
    try {
        $rate = getExchangeRate($pdo, $currency);
        $amount_try = $total_amount * $rate;

        $stmt = $pdo->prepare("INSERT INTO purchase_orders (supplier_id, order_number, total_amount, currency, amount_try, order_date, expected_delivery_date, description)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$supplier_id, $order_number, $total_amount, $currency, $amount_try, $order_date, $expected_delivery_date, $description]);

        $po_id = $pdo->lastInsertId();

        addReminder($pdo, "Sipariş Teslimatı: $order_number", $expected_delivery_date, 'purchase_order', $po_id);

        return $po_id;
    } catch (Exception $e) {
        throw new Exception("Sipariş eklenirken hata oluştu: " . $e->getMessage());
    }
}

function addPurchaseOrderItem($pdo, $purchase_order_id, $product_name, $quantity, $unit_price) {
    try {
        $stmt = $pdo->prepare("INSERT INTO purchase_order_items (purchase_order_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$purchase_order_id, $product_name, $quantity, $unit_price]);
    } catch (Exception $e) {
        throw new Exception("Ürün eklenirken hata oluştu: " . $e->getMessage());
    }
}

function convertToInvoice($pdo, $po_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM purchase_orders WHERE id = ? AND status = 'pending'");
        $stmt->execute([$po_id]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$po) {
            throw new Exception("Sipariş bulunamadı veya zaten işlenmiş.");
        }

        $invoice_id = addInvoice($pdo, $po['supplier_id'], $po['order_number'], $po['total_amount'], $po['currency'], $po['order_date'], $po['expected_delivery_date'], $po['description']);

        $stmt = $pdo->prepare("UPDATE purchase_orders SET status = 'delivered' WHERE id = ?");
        $stmt->execute([$po_id]);

        return $invoice_id;
    } catch (Exception $e) {
        throw new Exception("Faturaya dönüştürme sırasında hata oluştu: " . $e->getMessage());
    }
}
?>