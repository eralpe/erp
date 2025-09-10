<?php
require_once 'inventory.php';

function getSalesInvoices($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT si.*, c.name AS customer_name, i.product_name
            FROM sales_invoices si
            LEFT JOIN customers c ON si.customer_id = c.id
            LEFT JOIN inventory i ON si.inventory_id = i.id
            ORDER BY si.issue_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Satış faturaları alınırken hata oluştu: " . $e->getMessage());
    }
}

function addSalesInvoice($pdo, $invoice_number, $customer_id, $inventory_id, $quantity, $amount_try, $issue_date, $due_date) {
    try {
        // Stok kontrolü
        $stmt = $pdo->prepare("SELECT quantity FROM inventory WHERE id = ?");
        $stmt->execute([$inventory_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($item['quantity'] < $quantity) {
            throw new Exception("Yetersiz stok: Mevcut miktar {$item['quantity']}");
        }

        // Fatura ekleme
        $stmt = $pdo->prepare("
            INSERT INTO sales_invoices (invoice_number, customer_id, inventory_id, quantity, amount_try, issue_date, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$invoice_number, $customer_id, $inventory_id, $quantity, $amount_try, $issue_date, $due_date]);

        // Stok çıkışı
        addStockExit($pdo, $inventory_id, $quantity, "Satış Faturası: $invoice_number");
    } catch (Exception $e) {
        throw new Exception("Satış faturası eklenirken hata oluştu: " . $e->getMessage());
    }
}

function markInvoicePaid($pdo, $invoice_id) {
    try {
        $stmt = $pdo->prepare("UPDATE sales_invoices SET status = 'paid' WHERE id = ?");
        $stmt->execute([$invoice_id]);
    } catch (Exception $e) {
        throw new Exception("Fatura ödeme durumu güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function addSaleItem($pdo, $sale_id, $product_id, $quantity, $unit_price) {
    $stmt = $pdo->prepare("SELECT product_code, stock_quantity FROM inventory WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$product || $product['stock_quantity'] < $quantity) {
        throw new Exception("Yetersiz stok: " . $product['product_code']);
    }
    
    $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_name, quantity, unit_price) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sale_id, $product['product_code'], $quantity, $unit_price]);
    
    require_once 'functions/inventory.php';
    addInventoryTransaction($pdo, $product_id, 'out', $quantity, "Satış #$sale_id", $sale_id, 'sale');
}
?>