<?php
function addCategory($pdo, $name, $description) {
    if (empty($name)) {
        throw new Exception("Kategori adı zorunludur.");
    }
    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    return $pdo->lastInsertId();
}

function updateCategory($pdo, $id, $name, $description) {
    if (empty($name)) {
        throw new Exception("Kategori adı zorunludur.");
    }
    $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$name, $description, $id]);
}

function deleteCategory($pdo, $id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE category_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception("Bu kategoriye bağlı ürünler var, silinemez.");
    }
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
}

function getCategories($pdo) {
    $stmt = $pdo->query("SELECT c.*, 
                            (SELECT COUNT(*) FROM inventory i WHERE i.category_id = c.id) as product_count,
                            (SELECT COUNT(*) FROM inventory i WHERE i.category_id = c.id AND i.stock_quantity < i.min_stock_level) as low_stock_count
                         FROM categories c");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>