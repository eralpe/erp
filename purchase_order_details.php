<?php
session_start();
require_once 'config/db.php';
require_once 'functions/purchase_orders.php';
require_once 'functions/suppliers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$po_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$po = null;
if ($po_id) {
    $stmt = $pdo->prepare("SELECT po.*, s.name as supplier_name FROM purchase_orders po JOIN suppliers s ON po.supplier_id = s.id WHERE po.id = ?");
    $stmt->execute([$po_id]);
    $po = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_po'])) {
        try {
            $po_id = addPurchaseOrder($pdo, $_POST['supplier_id'], $_POST['order_number'], $_POST['total_amount'], $_POST['currency'], $_POST['order_date'], $_POST['expected_delivery_date'], $_POST['description']);
            $success = "Sipariş başarıyla eklendi.";
            header("Location: purchase_order_details.php?id=$po_id");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_item'])) {
        try {
            addPurchaseOrderItem($pdo, $po_id, $_POST['product_name'], $_POST['quantity'], $_POST['unit_price']);
            $success = "Ürün eklendi.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['convert_to_invoice'])) {
        try {
            convertToInvoice($pdo, $po_id);
            $success = "Sipariş faturaya dönüştürüldü.";
            header("Location: supplier_details.php?id=" . $po['supplier_id']);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$suppliers = $pdo->query("SELECT * FROM suppliers")->fetchAll(PDO::FETCH_ASSOC);
$items = $po ? $pdo->query("SELECT * FROM purchase_order_items WHERE purchase_order_id = $po_id")->fetchAll(PDO::FETCH_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sipariş Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><?php echo $po ? 'Sipariş Detayları: ' . htmlspecialchars($po['order_number']) : 'Yeni Sipariş Ekle'; ?></h2>

        <!-- Mesajlar -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Sipariş Bilgileri -->
        <?php if ($po): ?>
            <table class="table table-bordered">
                <tr><th>Tedarikçi</th><td><?php echo htmlspecialchars($po['supplier_name']); ?></td></tr>
                <tr><th>Sipariş No</th><td><?php echo htmlspecialchars($po['order_number']); ?></td></tr>
                <tr><th>Tutar</th><td><?php echo number_format($po['total_amount'], 2) . ' ' . $po['currency']; ?></td></tr>
                <tr><th>Tutar (TRY)</th><td><?php echo number_format($po['amount_try'], 2); ?></td></tr>
                <tr><th>Durum</th><td><?php echo $po['status'] === 'pending' ? 'Beklemede' : ($po['status'] === 'delivered' ? 'Teslim Edildi' : 'İptal'); ?></td></tr>
                <tr><th>Sipariş Tarihi</th><td><?php echo $po['order_date']; ?></td></tr>
                <tr><th>Teslimat Tarihi</th><td><?php echo $po['expected_delivery_date']; ?></td></tr>
                <tr><th>Açıklama</th><td><?php echo htmlspecialchars($po['description'] ?: '-'); ?></td></tr>
            </table>
            <?php if ($po['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="convert_to_invoice" class="btn btn-success" onclick="return confirm('Siparişi faturaya dönüştürmek istediğinizden emin misiniz?');">Faturaya Dönüştür</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <!-- Yeni Sipariş Ekle -->
            <h3>Yeni Sipariş Ekle</h3>
            <form method="POST">
                <div class="mb-3">
                    <label for="supplier_id" class="form-label">Tedarikçi</label>
                    <select name="supplier_id" class="form-select" required>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="order_number" class="form-label">Sipariş No</label>
                    <input type="text" name="order_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="total_amount" class="form-label">Toplam Tutar</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="currency" class="form-label">Para Birimi</label>
                    <select name="currency" class="form-select">
                        <option value="TRY">TRY</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="order_date" class="form-label">Sipariş Tarihi</label>
                    <input type="date" name="order_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="expected_delivery_date" class="form-label">Teslimat Tarihi</label>
                    <input type="date" name="expected_delivery_date" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Açıklama</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
                <button type="submit" name="add_po" class="btn btn-primary">Ekle</button>
                <a href="purchase_orders.php" class="btn btn-secondary">Geri Dön</a>
            </form>
        <?php endif; ?>

        <!-- Sipariş Ürünleri -->
        <?php if ($po): ?>
            <h3>Sipariş Ürünleri</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Ürün Adı</th>
                        <th>Miktar</th>
                        <th>Birim Fiyat</th>
                        <th>Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td><?php echo number_format($item['quantity'], 2); ?></td>
                            <td><?php echo number_format($item['unit_price'], 2); ?></td>
                            <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($po['status'] === 'pending'): ?>
                <h4>Yeni Ürün Ekle</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Ürün Adı</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Miktar</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="unit_price" class="form-label">Birim Fiyat</label>
                        <input type="number" step="0.01" name="unit_price" class="form-control" required>
                    </div>
                    <button type="submit" name="add_item" class="btn btn-primary">Ürün Ekle</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>