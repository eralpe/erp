<?php
// sales.php
ob_start();
require_once 'config/db.php';
require_once 'functions/sales.php';
require_once 'functions/customers.php';

$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$sale = null;
if ($sale_id) {
    $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id WHERE s.id = ?");
    $stmt->execute([$sale_id]);
    $sale = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sale'])) {
        try {
            $sale_id = addSale($pdo, $_POST['customer_id'], $_POST['order_number'], $_POST['total_amount'], $_POST['currency'], $_POST['sale_date'], $_POST['description']);
            $success = "Satış başarıyla eklendi.";
            header("Location: sales.php?id=$sale_id");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_item'])) {
        try {
            addSaleItem($pdo, $sale_id, $_POST['product_name'], $_POST['quantity'], $_POST['unit_price']);
            $success = "Ürün eklendi.";
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_sale'])) {
        try {
            deleteSale($pdo, $sale_id);
            $success = "Satış silindi.";
            header("Location: sales.php");
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$customers = $pdo->query("SELECT * FROM customers")->fetchAll(PDO::FETCH_ASSOC);
$items = $sale ? $pdo->query("SELECT * FROM sale_items WHERE sale_id = $sale_id")->fetchAll(PDO::FETCH_ASSOC) : [];
$sales = $sale ? [] : $pdo->query("SELECT s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id")->fetchAll(PDO::FETCH_ASSOC);

$page_title = $sale ? "Satış Detayları: " . htmlspecialchars($sale['order_number']) : "Satış Yönetimi";

?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?php echo $sale ? 'Satış Detayları: ' . htmlspecialchars($sale['order_number']) : 'Satış Listesi'; ?></h5>
        <?php if (!$sale): ?>
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSaleModal">
                <i class="fas fa-plus me-2"></i>Yeni Satış Ekle
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if ($sale): ?>
            <!-- Satış Bilgileri -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <tbody>
                        <tr><th>Müşteri</th><td><?php echo htmlspecialchars($sale['customer_name']); ?></td></tr>
                        <tr><th>Sipariş No</th><td><?php echo htmlspecialchars($sale['order_number']); ?></td></tr>
                        <tr><th>Tutar</th><td><?php echo number_format($sale['total_amount'], 2) . ' ' . $sale['currency']; ?></td></tr>
                        <tr><th>Satış Tarihi</th><td><?php echo $sale['sale_date']; ?></td></tr>
                        <tr><th>Açıklama</th><td><?php echo htmlspecialchars($sale['description'] ?: '-'); ?></td></tr>
                    </tbody>
                </table>
            </div>
            <form method="POST" style="display:inline;">
                <button type="submit" name="delete_sale" class="btn btn-danger btn-sm" onclick="return confirm('Satışı silmek istediğinizden emin misiniz?');">
                    <i class="fas fa-trash me-1"></i>Sil
                </button>
            </form>
        <?php else: ?>
            <!-- Satış Listesi -->
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Sipariş No</th>
                            <th>Müşteri</th>
                            <th>Tutar</th>
                            <th>Para Birimi</th>
                            <th>Satış Tarihi</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sales as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($s['customer_name']); ?></td>
                                <td><?php echo number_format($s['total_amount'], 2); ?></td>
                                <td><?php echo $s['currency']; ?></td>
                                <td><?php echo $s['sale_date']; ?></td>
                                <td>
                                    <a href="sales.php?id=<?php echo $s['id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Detay
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Satış Ürünleri -->
<?php if ($sale): ?>
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Satış Ürünleri</h5>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus me-2"></i>Yeni Ürün Ekle
            </button>
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

<!-- Yeni Satış Ekle Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1" aria-labelledby="addSaleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSaleModalLabel">Yeni Satış Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Müşteri</label>
                        <select name="customer_id" class="form-select" required>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>"><?php echo htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8'); ?></option>
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
                        <label for="sale_date" class="form-label">Satış Tarihi</label>
                        <input type="date" name="sale_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="add_sale" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Yeni Ürün Ekle Modal -->
<?php if ($sale): ?>
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
    <a href="sales.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Geri Dön
    </a>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>