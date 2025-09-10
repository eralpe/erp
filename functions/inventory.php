<?php
function getInventoryItems($pdo) {
    $stmt = $pdo->prepare("
        SELECT i.id, i.product_code, i.product_name, c.name AS category_name, i.unit, 
               i.stock_quantity, i.min_stock_level,
               (i.stock_quantity < i.min_stock_level) AS is_low_stock
        FROM inventory i
        LEFT JOIN categories c ON i.category_id = c.id
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addInventoryItem($pdo, $product_name, $quantity, $unit_price, $low_stock_threshold) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO inventory (product_name, quantity, unit_price, low_stock_threshold)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$product_name, $quantity, $unit_price, $low_stock_threshold]);
    } catch (Exception $e) {
        throw new Exception("Ürün eklenirken hata oluştu: " . $e->getMessage());
    }
}

function updateInventoryOnDelivery($pdo, $order_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT po.product_name, po.quantity 
            FROM purchase_orders po 
            WHERE po.id = ? AND po.delivery_status = 'pending'
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $stmt = $pdo->prepare("
                INSERT INTO inventory (product_name, quantity, unit_price, low_stock_threshold)
                VALUES (?, ?, 0.00, 10)
                ON DUPLICATE KEY UPDATE quantity = quantity + ?
            ");
            $stmt->execute([$order['product_name'], $order['quantity'], $order['quantity']]);

            $stmt = $pdo->prepare("UPDATE purchase_orders SET delivery_status = 'delivered' WHERE id = ?");
            $stmt->execute([$order_id]);

            $stmt = $pdo->prepare("
                INSERT INTO stock_transactions (inventory_id, transaction_type, quantity, description)
                SELECT id, 'entry', ?, 'Sipariş teslimatı: ID $order_id'
                FROM inventory WHERE product_name = ?
            ");
            $stmt->execute([$order['quantity'], $order['product_name']]);
        }
    } catch (Exception $e) {
        throw new Exception("Stok güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function addStockExit($pdo, $inventory_id, $quantity, $description) {
    try {
        $stmt = $pdo->prepare("
            SELECT quantity, low_stock_threshold 
            FROM inventory 
            WHERE id = ?
        ");
        $stmt->execute([$inventory_id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item['quantity'] < $quantity) {
            throw new Exception("Yetersiz stok: Mevcut miktar {$item['quantity']}");
        }

        $stmt = $pdo->prepare("
            UPDATE inventory 
            SET quantity = quantity - ? 
            WHERE id = ?
        ");
        $stmt->execute([$quantity, $inventory_id]);

        $stmt = $pdo->prepare("
            INSERT INTO stock_transactions (inventory_id, transaction_type, quantity, description)
            VALUES (?, 'exit', ?, ?)
        ");
        $stmt->execute([$inventory_id, $quantity, $description]);
    } catch (Exception $e) {
        throw new Exception("Stok çıkışı eklenirken hata oluştu: " . $e->getMessage());
    }
}

function addProduct($pdo, $product_code, $product_name, $category_id, $unit, $stock_quantity, $min_stock_level) {
    if (empty($product_code) || empty($product_name) || empty($unit)) {
        throw new Exception("Ürün kodu, adı ve birimi zorunludur.");
    }
    $stmt = $pdo->prepare("INSERT INTO inventory (product_code, product_name, category_id, unit, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$product_code, $product_name, $category_id ?: null, $unit, $stock_quantity, $min_stock_level]);
    return $pdo->lastInsertId();
}

function updateProduct($pdo, $id, $product_code, $product_name, $category_id, $unit, $stock_quantity, $min_stock_level) {
    if (empty($product_code) || empty($product_name) || empty($unit)) {
        throw new Exception("Ürün kodu, adı ve birimi zorunludur.");
    }
    $stmt = $pdo->prepare("UPDATE inventory SET product_code = ?, product_name = ?, category_id = ?, unit = ?, stock_quantity = ?, min_stock_level = ? WHERE id = ?");
    $stmt->execute([$product_code, $product_name, $category_id ?: null, $unit, $stock_quantity, $min_stock_level, $id]);
}

function deleteProduct($pdo, $id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_transactions WHERE product_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Bu ürüne bağlı hareketler var, silinemez.");
    }
    $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->execute([$id]);
}

function addInventoryTransaction($pdo, $product_id, $type, $quantity, $description, $related_id, $related_type) {
    if ($quantity <= 0) {
        throw new Exception("Miktar pozitif olmalıdır.");
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO inventory_transactions (product_id, type, quantity, description, related_id, related_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$product_id, $type, $quantity, $description, $related_id, $related_type]);
        
        $adjustment = $type === 'in' ? $quantity : -$quantity;
        $stmt = $pdo->prepare("UPDATE inventory SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->execute([$adjustment, $product_id]);
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

?>