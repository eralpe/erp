 <?php
session_start();
require_once 'config/db.php';
require_once 'functions/currency.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, unit_price, cost_price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_POST['name'], $_POST['sku'], $_POST['unit_price'], $_POST['cost_price']]);
    } elseif (isset($_POST['add_stock_movement'])) {
        $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, warehouse_id, quantity, type, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST['product_id'], $_POST['warehouse_id'], $_POST['quantity'], $_POST['type'], $_POST['description']]);
    }
}

$products = $pdo->query("SELECT * FROM products")->fetchAll();
$warehouses = $pdo->query("SELECT * FROM warehouses")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stok Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Stok Yönetimi</h2>
        <h3>Ürün Listesi</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Ürün Adı</th>
                    <th>SKU</th>
                    <th>Birim Fiyat</th>
                    <th>Maliyet Fiyatı</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td><?php echo htmlspecialchars($p['sku']); ?></td>
                        <td><?php echo number_format($p['unit_price'], 2); ?> TRY</td>
                        <td><?php echo number_format($p['cost_price'], 2); ?> TRY</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Yeni Ürün Ekle</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="name" class="form-label">Ürün Adı</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="sku" class="form-label">SKU</label>
                <input type="text" name="sku" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="unit_price" class="form-label">Birim Fiyat</label>
                <input type="number" step="0.01" name="unit_price" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="cost_price" class="form-label">Maliyet Fiyatı</label>
                <input type="number" step="0.01" name="cost_price" class="form-control" required>
            </div>
            <button type="submit" name="add_product" class="btn btn-primary">Ekle</button>
        </form>

        <h3>Stok Hareketi Ekle</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="product_id" class="form-label">Ürün</label>
                <select name="product_id" class="form-select" required>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="warehouse_id" class="form-label">Depo</label>
                <select name="warehouse_id" class="form-select" required>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="quantity" class="form-label">Miktar</label>
                <input type="number" name="quantity" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Hareket Türü</label>
                <select name="type" class="form-select" required>
                    <option value="in">Giriş</option>
                    <option value="out">Çıkış</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <input type="text" name="description" class="form-control">
            </div>
            <button type="submit" name="add_stock_movement" class="btn btn-primary">Ekle</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>