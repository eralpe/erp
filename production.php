<?php
session_start();
require_once 'config/db.php';
require_once 'functions/auth.php';
checkRoleAccess(['muhasebeci', 'yönetici', 'admin']);

$title = 'Üretim Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => 'Üretim Yönetimi', 'url' => '']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['personnel_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }

        if (isset($_POST['start_production'])) {
            $product_id = intval($_POST['product_id']);
            $quantity = intval($_POST['quantity']);
            $recipe_id = intval($_POST['recipe_id']);
            if ($quantity <= 0) throw new Exception("Miktar 0'dan büyük olmalıdır.");
            $stmt = $pdo->prepare("SELECT SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as available FROM stock_movements WHERE product_id = ? AND warehouse_id = 1");
            $stmt->execute([$product_id]);
            $available = $stmt->fetchColumn();
            if ($available < $quantity) throw new Exception("Yeterli stok yok.");
            $stmt = $pdo->prepare("INSERT INTO production (product_id, quantity, recipe_id, status) VALUES (?, ?, ?, 'in_progress')");
            $stmt->execute([$product_id, $quantity, $recipe_id]);
            $success = "Üretim emri başarıyla başlatıldı.";
        } elseif (isset($_POST['complete_production'])) {
            $production_id = intval($_POST['production_id']);
            $stmt = $pdo->prepare("UPDATE production SET status = 'completed', completed_at = NOW() WHERE id = ? AND status = 'in_progress'");
            $stmt->execute([$production_id]);
            if ($stmt->rowCount() > 0) {
                $stmt = $pdo->prepare("SELECT product_id, quantity FROM production WHERE id = ?");
                $stmt->execute([$production_id]);
                $prod = $stmt->fetch();
                $stmt = $pdo->prepare("INSERT INTO stock_movements (product_id, warehouse_id, quantity, type, created_at) VALUES (?, 1, ?, 'in', NOW())");
                $stmt->execute([$prod['product_id'], $prod['quantity']]);
                $success = "Üretim tamamlandı ve stok güncellendi.";
            } else {
                throw new Exception("Üretim emri zaten tamamlanmış veya bulunamadı.");
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Verileri çekme
$products = $pdo->query("SELECT * FROM products WHERE production_status != 'raw' ORDER BY created_at DESC")->fetchAll();
$recipes = $pdo->query("SELECT r.*, p.name as product_name FROM recipes r JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC")->fetchAll();
$production_orders = $pdo->query("SELECT p.*, pr.name as product_name, r.name as recipe_name FROM production p JOIN products pr ON p.product_id = pr.id LEFT JOIN recipes r ON p.recipe_id = r.id ORDER BY p.created_at DESC")->fetchAll();

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
        <h2>Üretim Yönetimi</h2>

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

        <!-- Üretim Emri Başlat Modal -->
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#startProductionModal">Üretim Emri Başlat</button>
        <div class="modal fade" id="startProductionModal" tabindex="-1" aria-labelledby="startProductionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="startProductionModalLabel">Üretim Emri Başlat</h5>
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
                                <label for="quantity" class="form-label">Miktar</label>
                                <input type="number" name="quantity" id="quantity" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="recipe_id" class="form-label">Reçete</label>
                                <select name="recipe_id" id="recipe_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($recipes as $recipe): ?>
                                        <option value="<?php echo $recipe['id']; ?>"><?php echo htmlspecialchars($recipe['name']) . ' (Toplam Maliyet: ' . number_format($recipe['total_cost'], 2) . ' TRY)'; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="start_production" class="btn btn-primary">Başlat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Üretim Tamamla Modal -->
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#completeProductionModal">Üretim Tamamla</button>
        <div class="modal fade" id="completeProductionModal" tabindex="-1" aria-labelledby="completeProductionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="completeProductionModalLabel">Üretim Tamamla</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="production_id" class="form-label">Üretim Emri</label>
                                <select name="production_id" id="production_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($production_orders as $order): if ($order['status'] === 'in_progress'): ?>
                                        <option value="<?php echo $order['id']; ?>"><?php echo htmlspecialchars($order['product_name']) . ' (Reçete: ' . htmlspecialchars($order['recipe_name'] ?? 'Yok') . ')'; ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="complete_production" class="btn btn-primary">Tamamla</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Üretim Emirleri -->
        <div class="table-responsive">
            <h3>Üretim Emirleri</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ürün</th>
                        <th>Miktar</th>
                        <th>Reçete</th>
                        <th>Durum</th>
                        <th>Oluşturulma Tarihi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($production_orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($order['recipe_name'] ?? 'Yok'); ?></td>
                            <td><?php echo $order['status']; ?></td>
                            <td><?php echo $order['created_at']; ?></td>
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