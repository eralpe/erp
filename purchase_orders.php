<?php
// purchase_orders.php
ob_start();
require_once 'config/db.php';
require_once 'functions/purchase_orders.php';
require_once 'functions/suppliers.php';

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

$page_title = $po ? "Sipariş Detayları: " . htmlspecialchars($po['order_number']) : "Yeni Sipariş Ekle";

?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $po ? 'Sipariş Detayları: ' . htmlspecialchars($po['order_number']) : 'Yeni Sipariş Ekle'; ?></h5>
        <?php if (!$po): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPurchaseOrderModal">
                <i class="fas fa-plus me-2"></i>Yeni Sipariş Ekle
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <!-- Sipariş Bilgileri -->
        <?php if ($po): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <tbody>
                        <tr><th>Tedarikçi</th><td><?php echo htmlspecialchars($po['supplier_name']); ?></td></tr>
                        <tr><th>Sipariş No</th><td><?php echo htmlspecialchars($po['order_number']); ?></td></tr>
                        <tr><th>Tutar</th><td><?php echo number_format($po['total_amount'], 2) . ' ' . $po['currency']; ?></td></tr>
                        <tr><th>Tutar (TRY)</th><td><?php echo number_format($po['amount_try'], 2); ?></td></tr>
                        <tr><th>Durum</th><td><?php echo $po['status'] === 'pending' ? 'Beklemede' : ($po['status'] === 'delivered' ? 'Teslim Edildi' : 'İptal'); ?></td></tr>
                        <tr><th>Sipariş Tarihi</th><td><?php echo $po['order_date']; ?></td></tr>
                        <tr><th>Teslimat Tarihi</th><td><?php echo $po['expected_delivery_date']; ?></td></tr>
                        <tr><th>Açıklama</th><td><?php echo htmlspecialchars($po['description'] ?: '-'); ?></td></tr>
                    </tbody>
                </table>
            </div>
            <?php if ($po['status'] === 'pending'): ?>
                <form method="POST" style="display:inline;">
                    <button type="submit" name="convert_to_invoice" class="btn btn-success btn-sm" onclick="return confirm('Siparişi faturaya dönüştürmek istediğinizden emin misiniz?');">
                        <i class="fas fa-file-invoice me-1"></i>Faturaya Dönüştür
                    </button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Sipariş Ürünleri -->
<?php if ($po): ?>
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Sipariş Ürünleri</h5>
            <?php if ($po['status'] === 'pending'): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                    <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
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
                                <td><?php echo htmlspecialchars($item['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo number_format($item['quantity'], 2); ?></td>
                                <td><?php echo number_format($item['unit_price'], 2); ?></td>
                                <td><?php echo number_format($item['quantity'] * $item['unit_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Yeni Sipariş Ekle Modal -->
<div class="modal fade" id="addPurchaseOrderModal" tabindex="-1" aria-labelledby="addPurchaseOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPurchaseOrderModalLabel">Yeni Sipariş Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Tedarikçi</label>
                        <select name="supplier_id" class="form-select" required>
                            <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name'], ENT_QUOTES, 'UTF-8'); ?></option>
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
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="add_po" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Yeni Ürün Ekle Modal -->
<?php if ($po && $po['status'] === 'pending'): ?>
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Yeni Ürün Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
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
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" name="add_item" class="btn btn-primary">Ürün Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="mt-3">
    <a href="purchase_orders.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Geri Dön
    </a>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>