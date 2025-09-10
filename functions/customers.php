<?php
function getCustomers($pdo, $include_inactive = false) {
    try {
        $query = "SELECT id, name, email, phone, address, balance, status FROM customers";
        if (!$include_inactive) {
            $query .= " WHERE status = 'active'";
        }
        $query .= " ORDER BY name";
        $stmt = $pdo->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Müşteriler alınırken hata oluştu: " . $e->getMessage());
    }
}

function addCustomer($pdo, $name, $email, $phone, $address) {
    if (empty($name)) {
        throw new Exception("Müşteri adı zorunludur.");
    }
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Geçersiz e-posta adresi.");
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, address, balance, status, created_at) VALUES (?, ?, ?, ?, 0.00, 'active', NOW())");
        $stmt->execute([$name, $email ?: null, $phone ?: null, $address ?: null]);
    } catch (PDOException $e) {
        throw new Exception("Müşteri eklenirken hata oluştu: " . $e->getMessage());
    }
}

function updateCustomerStatus($pdo, $customer_id, $status) {
    try {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception("Geçersiz durum: $status");
        }
        $stmt = $pdo->prepare("UPDATE customers SET status = ? WHERE id = ?");
        $stmt->execute([$status, $customer_id]);
    } catch (PDOException $e) {
        throw new Exception("Müşteri durumu güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function deleteCustomer($pdo, $customer_id) {
    try {
        $pdo->beginTransaction();
        // customer_transactions ON DELETE CASCADE ile otomatik silinir
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw new Exception("Müşteri silinirken hata oluştu: " . $e->getMessage());
    }
}
?>