<?php
session_start();
require_once 'config/db.php';
require_once 'functions/auth.php';

checkRoleAccess(['muhasebeci', 'yönetici', 'admin']);
$title = 'Depo Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => $title, 'url' => '']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['personnel_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }

        if (isset($_POST['add_warehouse'])) {
            $name = trim($_POST['warehouse_name']);
            $location = trim($_POST['warehouse_location']);
            if (empty($name)) throw new Exception("Depo adı zorunludur.");
            $stmt = $pdo->prepare("INSERT INTO warehouses (name, location) VALUES (?, ?)");
            $stmt->execute([$name, $location]);
            $success = "Depo başarıyla eklendi.";
        } elseif (isset($_POST['add_product'])) {
            $name = trim($_POST['product_name']);
            $sku = trim($_POST['sku']);
            $unit_price = floatval($_POST['unit_price']);
            $cost_price = floatval($_POST['cost_price']);
            $category = trim($_POST['category']);
            $production_status = $_POST['production_status'];
            $unit_type = trim($_POST['unit_type']);
            if (empty($name) || empty($sku)) throw new Exception("Ürün adı ve SKU zorunludur.");
            $stmt = $pdo->prepare("INSERT INTO products (name, sku, unit_price, cost_price, category, production_status, unit_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $sku, $unit_price, $cost_price, $category, $production_status, $unit_type]);
            $success = "Ürün başarıyla eklendi.";
        } elseif (isset($_POST['add_movement'])) {
            $product_id = intval($_POST['product_id']);
            $warehouse_id = intval($_POST['warehouse_id']);
            $quantity = intval($_POST['quantity']);
            $type = $_POST['type'];
            if ($quantity <= 0) throw new Exception("Miktar 0'dan büyük olmalıdır.");
            $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, warehouse_id, quantity, type, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$product_id, $warehouse_id, $quantity, $type]);
            $success = "Stok hareketi başarıyla kaydedildi.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Verileri çekme
$warehouses = $pdo->query("SELECT * FROM warehouses ORDER BY created_at DESC")->fetchAll();
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
$stmt = $pdo->prepare("SELECT sm.*, p.name as product_name, w.name as warehouse_name 
                     FROM stock_movements sm 
                     JOIN products p ON sm.product_id = p.id 
                     JOIN warehouses w ON sm.warehouse_id = w.id 
                     ORDER BY sm.created_at DESC");
$stmt->execute();
$movements = $stmt->fetchAll();

// Stok durumu hesaplama
$stock_status = [];
foreach ($products as $product) {
    $stmt = $pdo->prepare("SELECT warehouse_id, SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as total 
                         FROM stock_movements 
                         WHERE product_id = ? 
                         GROUP BY warehouse_id");
    $stmt->execute([$product['id']]);
    $stock_status[$product['id']] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

ob_start();
?>


<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-section { margin-bottom: 20px; }
        .table-responsive { margin-top: 20px; }
        .btn-custom { background-color: #007bff; border-color: #007bff; }
        .btn-custom:hover { background-color: #0056b3; border-color: #0056b3; }
        .modal-header { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Depo Yönetimi</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item<?php echo !$crumb['url'] ? ' active' : ''; ?>">
                        <?php if ($crumb['url']): ?>
                            <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['title']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($crumb['title']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <!-- Ürün Ekleme Modal -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addProductModal">Yeni Ürün Ekle</button>
        <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProductModalLabel">Yeni Ürün Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="product_name" class="form-label">Ürün Adı</label>
                                <input type="text" name="product_name" id="product_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU</label>
                                <input type="text" name="sku" id="sku" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="unit_price" class="form-label">Birim Fiyatı (TRY)</label>
                                <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Maliyet Fiyatı (TRY)</label>
                                <input type="number" name="cost_price" id="cost_price" class="form-control" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="category" class="form-label">Kategori</label>
                                <input type="text" name="category" id="category" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="production_status" class="form-label">Üretim Durumu</label>
                                <select name="production_status" id="production_status" class="form-select" required>
                                    <option value="raw">Ham Madde</option>
                                    <option value="finished">Bitmiş Ürün</option>
                                    <option value="semi-finished">Yarı Mamul</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="unit_type" class="form-label">Birim Türü</label>
                                <select name="unit_type" id="unit_type" class="form-select" required>
                                    <option value="adet">Adet</option>
                                    <option value="kg">Kg</option>
                                    <option value="gram">Gram</option>
                                    <option value="ml">Ml</option>
                                    <option value="litre">Litre</option>
                                    <option value="metre">Metre</option>
                                    <option value="paket">Paket</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="add_product" class="btn btn-primary">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Stok Hareketi Ekle Modal -->
        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addMovementModal">Stok Hareketi Ekle</button>
        <div class="modal fade" id="addMovementModal" tabindex="-1" aria-labelledby="addMovementModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMovementModalLabel">Stok Hareketi Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Ürün</label>
                                <select name="product_id" id="product_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="warehouse_id" class="form-label">Depo</label>
                                <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Miktar</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="type" class="form-label">Tür</label>
                                <select name="type" id="type" class="form-select" required>
                                    <option value="in">Giriş</option>
                                    <option value="out">Çıkış</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="add_movement" class="btn btn-primary">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Depolar Listesi -->
        <div class="table-responsive">
            <h3>Depolar</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Konum</th>
                        <th>Oluşturulma Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <tr>
                            <td><?php echo $warehouse['id']; ?></td>
                            <td><?php echo htmlspecialchars($warehouse['name']); ?></td>
                            <td><?php echo htmlspecialchars($warehouse['location'] ?? '-'); ?></td>
                            <td><?php echo $warehouse['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Ürünler ve Stok Durumu -->
        <div class="table-responsive">
            <h3>Ürünler ve Stok Durumu</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>SKU</th>
                        <th>Kategori</th>
                        <th>Üretim Durumu</th>
                        <th>Birim Türü</th>
                        <th>Stok Durumu (Depo 1)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['category'] ?? '-'); ?></td>
                            <td><?php echo $product['production_status']; ?></td>
                            <td><?php echo htmlspecialchars($product['unit_type']); ?></td>
                            <td><?php echo $stock_status[$product['id']][1] ?? 0; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Stok Hareketleri -->
        <div class="table-responsive">
            <h3>Stok Hareketleri</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ürün</th>
                        <th>Depo</th>
                        <th>Miktar</th>
                        <th>Tür</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td><?php echo $movement['id']; ?></td>
                            <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($movement['warehouse_name']); ?></td>
                            <td><?php echo $movement['quantity']; ?></td>
                            <td><?php echo $movement['type'] === 'in' ? 'Giriş' : 'Çıkış'; ?></td>
                            <td><?php echo $movement['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>