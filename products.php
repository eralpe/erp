<?php
session_start();
require_once 'config/db.php';
require_once 'functions/auth.php';

checkRoleAccess(['muhasebeci', 'yönetici', 'admin']);

$title = 'Ürün Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => 'Ürün Yönetimi', 'url' => '']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['personnel_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }

        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            $cost_price = floatval($_POST['cost_price']);
            $production_status = trim($_POST['production_status']);
            if (empty($name) || empty($cost_price)) throw new Exception("Ürün adı ve maliyet fiyatı zorunludur.");
            $stmt = $pdo->prepare("INSERT INTO products (name, cost_price, production_status) VALUES (?, ?, ?)");
            $stmt->execute([$name, $cost_price, $production_status]);
            $success = "Ürün başarıyla eklendi.";
        } elseif (isset($_POST['edit_product'])) {
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $cost_price = floatval($_POST['cost_price']);
            $production_status = trim($_POST['production_status']);
            if (empty($name) || empty($cost_price)) throw new Exception("Ürün adı ve maliyet fiyatı zorunludur.");
            $stmt = $pdo->prepare("UPDATE products SET name = ?, cost_price = ?, production_status = ? WHERE id = ?");
            $stmt->execute([$name, $cost_price, $production_status, $id]);
            $success = "Ürün başarıyla güncellendi.";
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Export işlemi için GET ile tetikleme
if (isset($_GET['export_product']) && isset($_GET['format']) && isset($_GET['product_id'])) {
    try {
        $product_id = intval($_GET['product_id']);
        $format = $_GET['format'];
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) throw new Exception("Ürün bulunamadı.");

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="product_' . $product_id . '.csv"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Ad', 'Maliyet Fiyatı', 'Üretim Durumu', 'Oluşturulma Tarihi']);
            fputcsv($output, [$product['id'], $product['name'], $product['cost_price'], $product['production_status'], $product['created_at']]);
            fclose($output);
            exit;
        } elseif ($format === 'pdf') {
            require_once __DIR__ . '/vendor/autoload.php';
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator('Ürün Yönetim Sistemi');
            $pdf->SetAuthor('Ürün Sistemi');
            $pdf->SetTitle('Ürün Detayları: ' . $product['name']);
            $pdf->SetSubject('Ürün Bilgileri');
            $pdf->SetKeywords('Ürün, PDF, Rapor');
            $pdf->SetHeaderData('', 0, 'Ürün Detayları: ' . $product['name'], '');
            $pdf->setHeaderFont(['dejavusans', '', 10]);
            $pdf->setFooterFont(['dejavusans', '', 8]);
            $pdf->SetDefaultMonospacedFont('dejavusans');
            $pdf->SetMargins(10, 20, 10);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->setImageScale(1.0);
            $pdf->SetFont('dejavusans', '', 10);
            $pdf->AddPage();

            $html = '
            <style>
                h2 { font-size: 18px; color: #003087; }
                p { font-size: 12px; margin: 5px 0; }
            </style>
            <h2>Ürün Detayları</h2>
            <p><strong>ID:</strong> ' . $product['id'] . '</p>
            <p><strong>Ad:</strong> ' . htmlspecialchars($product['name']) . '</p>
            <p><strong>Maliyet Fiyatı:</strong> ' . number_format($product['cost_price'], 2) . ' TRY</p>
            <p><strong>Üretim Durumu:</strong> ' . htmlspecialchars($product['production_status']) . '</p>
            <p><strong>Oluşturulma Tarihi:</strong> ' . $product['created_at'] . '</p>';

            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('product_' . $product_id . '_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
            exit;
        }
    } catch (Exception $e) {
        $error = "Export hatası: " . $e->getMessage();
        header("Location: products.php?error=" . urlencode($error));
        exit;
    }
}

// Verileri çekme
$products = $pdo->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();

ob_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section { margin-bottom: 20px; }
        .table-responsive { margin-top: 20px; }
        .btn-export { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="container mt-5">

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>


        <!-- Ürün Ekleme Modal -->
        <button type="button" class="btn btn-success mb-3 btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fa fa-plus"></i>&nbsp;&nbsp;Yeni Ürün Ekle</button>
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
                                <label for="name" class="form-label">Ürün Adı</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="cost_price" class="form-label">Maliyet Fiyatı (TRY)</label>
                                <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="production_status" class="form-label">Üretim Durumu</label>
                                <select name="production_status" id="production_status" class="form-select" required>
                                    <option value="raw">Ham Madde</option>
                                    <option value="finished">Mamul</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="add_product" class="btn btn-success">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ürün Düzenleme Modal -->
        <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProductModalLabel">Ürünü Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <input type="hidden" name="id" id="edit_product_id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Ürün Adı</label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_cost_price" class="form-label">Maliyet Fiyatı (TRY)</label>
                                <input type="number" step="0.01" name="cost_price" id="edit_cost_price" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_production_status" class="form-label">Üretim Durumu</label>
                                <select name="production_status" id="edit_production_status" class="form-select" required>
                                    <option value="raw">Ham Madde</option>
                                    <option value="finished">Mamul</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="edit_product" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Ürünler Listesi -->
        <div class="table-responsive pb-5" style="z-index:1070 !important">
            <h3>Ürünler</h3>
            <table class="table table-striped pb-5" style="z-index:1070 !important">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Maliyet Fiyatı</th>
                        <th>Üretim Durumu</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo number_format($product['cost_price'], 2); ?> TRY</td>
                            <td><?php echo htmlspecialchars($product['production_status']); ?></td>
                            <td><?php echo $product['created_at']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a class="btn btn-success btn-sm btn-export" href="?export_product=1&product_id=<?php echo $product['id']; ?>&format=csv"><i class="fa fa-file-csv"></i></a>
                                    <a class="btn btn-danger btn-sm btn-export" href="?export_product=1&product_id=<?php echo $product['id']; ?>&format=pdf"><i class="fa fa-file-pdf"></i></a>
                                    <button type="button" class="btn btn-warning btn-sm btn-export" data-bs-toggle="modal" data-bs-target="#editProductModal" onclick="editProduct(<?php echo $product['id']; ?>)"><i class="fa fa-edit"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editProduct(productId) {
            fetch(`fetch_product.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_product_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_cost_price').value = data.cost_price;
                    document.getElementById('edit_production_status').value = data.production_status;
                })
                .catch(error => console.error('Hata:', error));
        }
    </script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>