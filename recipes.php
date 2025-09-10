<?php
session_start();
require_once 'config/db.php';
require_once 'functions/auth.php';
require_once 'vendor/autoload.php';

checkRoleAccess(['muhasebeci', 'yönetici', 'admin']);

$title = 'Reçete Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => 'Reçete Yönetimi', 'url' => '']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['personnel_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }

        if (isset($_POST['add_recipe'])) {
            $name = trim($_POST['recipe_name']);
            $product_id = intval($_POST['product_id']);
            $raw_materials = $_POST['raw_material_id'];
            $quantities = $_POST['quantity'];
            $unit_types = $_POST['unit_type'];
            if (empty($name) || empty($product_id)) throw new Exception("Reçete adı ve ürün zorunludur.");
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO recipes (name, product_id) VALUES (?, ?)");
            $stmt->execute([$name, $product_id]);
            $recipe_id = $pdo->lastInsertId();
            $total_cost = 0;
            foreach ($raw_materials as $index => $raw_id) {
                $quantity = intval($quantities[$index]);
                $unit_type = $unit_types[$index];
                $stmt = $pdo->prepare("SELECT cost_price FROM products WHERE id = ?");
                $stmt->execute([$raw_id]);
                $cost_per_unit = $stmt->fetchColumn();
                $cost = $cost_per_unit * $quantity;
                $total_cost += $cost;
                $stmt = $pdo->prepare("INSERT INTO recipe_details (recipe_id, raw_material_id, quantity, unit_type, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$recipe_id, $raw_id, $quantity, $unit_type, $cost_per_unit]);
            }
            $stmt = $pdo->prepare("UPDATE recipes SET total_cost = ? WHERE id = ?");
            $stmt->execute([$total_cost, $recipe_id]);
            $pdo->commit();
            $success = "Reçete başarıyla eklendi.";
        } elseif (isset($_POST['edit_recipe'])) {
            $recipe_id = intval($_POST['recipe_id']);
            $name = trim($_POST['recipe_name']);
            $product_id = intval($_POST['product_id']);
            if (empty($name) || empty($product_id)) throw new Exception("Reçete adı ve ürün zorunludur.");
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE recipes SET name = ?, product_id = ? WHERE id = ?");
            $stmt->execute([$name, $product_id, $recipe_id]);
            $stmt = $pdo->prepare("DELETE FROM recipe_details WHERE recipe_id = ?");
            $stmt->execute([$recipe_id]);
            $raw_materials = $_POST['raw_material_id'];
            $quantities = $_POST['quantity'];
            $unit_types = $_POST['unit_type'];
            $total_cost = 0;
            foreach ($raw_materials as $index => $raw_id) {
                if (!empty($raw_id)) {
                    $quantity = intval($quantities[$index]);
                    $unit_type = $unit_types[$index];
                    $stmt = $pdo->prepare("SELECT cost_price FROM products WHERE id = ?");
                    $stmt->execute([$raw_id]);
                    $cost_per_unit = $stmt->fetchColumn();
                    $cost = $cost_per_unit * $quantity;
                    $total_cost += $cost;
                    $stmt = $pdo->prepare("INSERT INTO recipe_details (recipe_id, raw_material_id, quantity, unit_type, cost_per_unit) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$recipe_id, $raw_id, $quantity, $unit_type, $cost_per_unit]);
                }
            }
            $stmt = $pdo->prepare("UPDATE recipes SET total_cost = ? WHERE id = ?");
            $stmt->execute([$total_cost, $recipe_id]);
            $pdo->commit();
            $success = "Reçete başarıyla güncellendi.";
        } elseif (isset($_POST['delete_recipe'])) {
            $recipe_id = intval($_POST['recipe_id']);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM recipe_details WHERE recipe_id = ?");
            $stmt->execute([$recipe_id]);
            $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
            $stmt->execute([$recipe_id]);
            $pdo->commit();
            $success = "Reçete başarıyla silindi.";
        }
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Export işlemi için GET ile tetikleme
if (isset($_GET['export_recipe']) && isset($_GET['format']) && isset($_GET['recipe_id'])) {
    try {
        $recipe_id = intval($_GET['recipe_id']);
        $format = $_GET['format'];
        $stmt = $pdo->prepare("SELECT r.name, r.total_cost, p.name as product_name, rd.raw_material_id, pr.name as raw_material_name, rd.quantity, rd.unit_type, rd.cost_per_unit 
                            FROM recipes r 
                            JOIN recipe_details rd ON r.id = rd.recipe_id 
                            JOIN products p ON r.product_id = p.id 
                            JOIN products pr ON rd.raw_material_id = pr.id 
                            WHERE r.id = ?");
        $stmt->execute([$recipe_id]);
        $recipe = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($recipe)) throw new Exception("Reçete bulunamadı veya detayları eksik.");

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="recipe_' . $recipe_id . '.csv"');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            $output = fopen('php://output', 'w');
            if ($output === false) throw new Exception("Çıkış akışı oluşturulamadı.");
            fputcsv($output, ['Reçete Adı', 'Ürün Adı', 'Ham Madde Adı', 'Miktar', 'Birim Türü', 'Birim Maliyet', 'Toplam Maliyet']);
            foreach ($recipe as $row) {
                fputcsv($output, [
                    $row['name'],
                    $row['product_name'],
                    $row['raw_material_name'],
                    $row['quantity'],
                    $row['unit_type'],
                    $row['cost_per_unit'],
                    $row['total_cost']
                ]);
            }
            fclose($output);
            exit;
        } elseif ($format === 'pdf') {
            // TCPDF ile PDF oluşturma
    require_once __DIR__ . '/vendor/autoload.php'; // TCPDF için autoload
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Reçete Yönetim Sistemi');
    $pdf->SetAuthor('Reçete Sistemi');
    $pdf->SetTitle('Reçete Detayları: ' . $recipe[0]['name']);
    $pdf->SetSubject('Reçete Bilgileri');
    $pdf->SetKeywords('Reçete, PDF, Rapor');

    // Başlık ve kenar boşlukları
    $pdf->SetHeaderData('', 0, 'Reçete Detayları: ' . $recipe[0]['name'], '');
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

    // HTML içeriği
    $html = '
    <style>
        h2 { font-size: 18px; color: #003087; }
        p { font-size: 12px; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
    </style>
    <h2>Reçete Detayları</h2>
    <p><strong>Reçete Adı:</strong> ' . htmlspecialchars($recipe[0]['name']) . '</p>
    <p><strong>Ürün Adı:</strong> ' . htmlspecialchars($recipe[0]['product_name']) . '</p>
    <p><strong>Toplam Maliyet:</strong> ' . number_format($recipe[0]['total_cost'], 2) . ' TRY</p>
    <table>
        <thead>
            <tr>
                <th>Ham Madde Adı</th>
                <th>Miktar</th>
                <th>Birim Türü</th>
                <th>Birim Maliyet</th>
            </tr>
        </thead>
        <tbody>';
    foreach ($recipe as $row) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($row['raw_material_name']) . '</td>
                <td>' . $row['quantity'] . '</td>
                <td>' . htmlspecialchars($row['unit_type']) . '</td>
                <td>' . number_format($row['cost_per_unit'], 2) . ' TRY</td>
            </tr>';
    }
    $html .= '
        </tbody>
    </table>';

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf->Output('recipe_' . $recipe_id . '_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
        }
    } catch (Exception $e) {
        $error = "Export hatası: " . $e->getMessage();
        header("Location: recipes.php?error=" . urlencode($error));
        exit;
    }
}

// Verileri çekme
$products = $pdo->query("SELECT * FROM products WHERE production_status != 'raw' ORDER BY created_at DESC")->fetchAll();
$raw_materials = $pdo->query("SELECT * FROM products WHERE production_status = 'raw' ORDER BY created_at DESC")->fetchAll();
$recipes = $pdo->query("SELECT r.*, p.name as product_name FROM recipes r JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC")->fetchAll();

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
        .btn-custom { background-color: #007bff; border-color: #007bff; }
        .btn-custom:hover { background-color: #0056b3; border-color: #0056b3; }
        .modal-header { background-color: #f8f9fa; }
        .btn-export { margin-right: 5px; }
    </style>
</head>
<body>
    <div class="containe p-5">

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>


        <!-- Reçete Ekleme Modal -->
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addRecipeModal">Yeni Reçete Ekle</button>
        <div class="modal fade" id="addRecipeModal" tabindex="-1" aria-labelledby="addRecipeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRecipeModalLabel">Yeni Reçete Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="recipe_name" class="form-label">Reçete Adı</label>
                                <input type="text" name="recipe_name" id="recipe_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Ürün</label>
                                <select name="product_id" id="product_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="raw_materials_container">
                                <div class="row mb-3">
                                    <div class="col-md-5">
                                        <label for="raw_material_id[0]" class="form-label">Ham Madde</label>
                                        <select name="raw_material_id[0]" class="form-select" required>
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($raw_materials as $material): ?>
                                                <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="quantity[0]" class="form-label">Miktar</label>
                                        <input type="number" name="quantity[0]" class="form-control" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="unit_type[0]" class="form-label">Birim Türü</label>
                                        <select name="unit_type[0]" class="form-select" required>
                                            <option value="adet">Adet</option>
                                            <option value="kg">Kg</option>
                                            <option value="gram">Gram</option>
                                            <option value="ml">Ml</option>
                                            <option value="litre">Litre</option>
                                            <option value="metre">Metre</option>
                                            <option value="paket">Paket</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-secondary mt-4" onclick="addRawMaterial()">+ Ekle</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="add_recipe" class="btn btn-success">Ekle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reçete Düzenleme Modal -->
        <div class="modal fade" id="editRecipeModal" tabindex="-1" aria-labelledby="editRecipeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRecipeModalLabel">Reçeteyi Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <input type="hidden" name="recipe_id" id="edit_recipe_id">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit_recipe_name" class="form-label">Reçete Adı</label>
                                <input type="text" name="recipe_name" id="edit_recipe_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="edit_product_id" class="form-label">Ürün</label>
                                <select name="product_id" id="edit_product_id" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="edit_raw_materials_container">
                                <!-- Veriler dinamik olarak doldurulacak -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" name="edit_recipe" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reçeteler Listesi -->
        <div class="table-responsive pb-5" style="z-index:1070 !important">
            <h3>Reçeteler</h3>
            <table class="table table-striped pb-5" style="z-index:1070 !important">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ad</th>
                        <th>Ürün</th>
                        <th>Toplam Maliyet</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $recipe): ?>
                        <tr>
                            <td><?php echo $recipe['id']; ?></td>
                            <td><?php echo htmlspecialchars($recipe['name']); ?></td>
                            <td><?php echo htmlspecialchars($recipe['product_name']); ?></td>
                            <td><?php echo number_format($recipe['total_cost'], 2); ?> TRY</td>
                            <td><?php echo $recipe['created_at']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a class="btn btn-success btn-sm btn-export" href="?export_recipe=1&recipe_id=<?php echo $recipe['id']; ?>&format=csv"><i class="fa fa-file-csv"></i></a>
                                    <a class="btn btn-danger btn-sm btn-export" href="?export_recipe=1&recipe_id=<?php echo $recipe['id']; ?>&format=pdf"><i class="fa fa-file-pdf"></i></a>
                                    <button type="button" class="btn btn-warning btn-sm btn-export" data-bs-toggle="modal" data-bs-target="#editRecipeModal" onclick="editRecipe(<?php echo $recipe['id']; ?>)"><i class="fa fa-edit"></i></button>
                                    <button type="button" class="btn btn-danger btn-sm btn-export" data-bs-toggle="modal" data-bs-target="#deleteRecipeModal" onclick="confirmDelete(<?php echo $recipe['id']; ?>)"><i class="fa fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Silme Onay Modal -->
    <div class="modal fade" id="deleteRecipeModal" tabindex="-1" aria-labelledby="deleteRecipeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRecipeModalLabel">Reçeteyi Sil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Reçeteyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.
                    <input type="hidden" id="delete_recipe_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
                        <input type="hidden" name="recipe_id" id="delete_recipe_id_form">
                        <button type="submit" name="delete_recipe" class="btn btn-danger">Sil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let rawMaterialIndex = 0;
    let editRawMaterialIndex = 0;

    function addRawMaterial() {
        rawMaterialIndex++;
        const container = document.getElementById('raw_materials_container');
        const row = document.createElement('div');
        row.className = 'row mb-3';
        row.innerHTML = `
            <div class="col-md-5">
                <label for="raw_material_id[${rawMaterialIndex}]" class="form-label">Ham Madde</label>
                <select name="raw_material_id[${rawMaterialIndex}]" class="form-select" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($raw_materials as $material): ?>
                        <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="quantity[${rawMaterialIndex}]" class="form-label">Miktar</label>
                <input type="number" name="quantity[${rawMaterialIndex}]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="unit_type[${rawMaterialIndex}]" class="form-label">Birim Türü</label>
                <select name="unit_type[${rawMaterialIndex}]" class="form-select" required>
                    <option value="adet">Adet</option>
                    <option value="kg">Kg</option>
                    <option value="gram">Gram</option>
                    <option value="ml">Ml</option>
                    <option value="litre">Litre</option>
                    <option value="metre">Metre</option>
                    <option value="paket">Paket</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger mt-4" onclick="this.parentElement.parentElement.remove()">- Kaldır</button>
            </div>
        `;
        container.appendChild(row);
    }

    function addEditRawMaterial() {
        editRawMaterialIndex++;
        const container = document.getElementById('edit_raw_materials_container');
        const row = document.createElement('div');
        row.className = 'row mb-3';
        row.innerHTML = `
            <div class="col-md-5">
                <label for="edit_raw_material_id[${editRawMaterialIndex}]" class="form-label">Ham Madde</label>
                <select name="raw_material_id[${editRawMaterialIndex}]" class="form-select" required>
                    <option value="">Seçiniz</option>
                    <?php foreach ($raw_materials as $material): ?>
                        <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="edit_quantity[${editRawMaterialIndex}]" class="form-label">Miktar</label>
                <input type="number" name="quantity[${editRawMaterialIndex}]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label for="edit_unit_type[${editRawMaterialIndex}]" class="form-label">Birim Türü</label>
                <select name="unit_type[${editRawMaterialIndex}]" class="form-select" required>
                    <option value="adet">Adet</option>
                    <option value="kg">Kg</option>
                    <option value="gram">Gram</option>
                    <option value="ml">Ml</option>
                    <option value="litre">Litre</option>
                    <option value="metre">Metre</option>
                    <option value="paket">Paket</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger mt-4" onclick="this.parentElement.parentElement.remove()">- Kaldır</button>
            </div>
        `;
        container.appendChild(row);
    }

    function editRecipe(recipeId) {
        fetch(`fetch_recipe.php?id=${recipeId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('edit_recipe_id').value = data.id;
                document.getElementById('edit_recipe_name').value = data.name;
                document.getElementById('edit_product_id').value = data.product_id;
                const container = document.getElementById('edit_raw_materials_container');
                container.innerHTML = ''; // Önceki verileri temizle
                editRawMaterialIndex = 0;
                data.details.forEach(detail => {
                    addEditRawMaterial();
                    const selects = container.querySelectorAll('select[name^="raw_material_id"]');
                    const quantities = container.querySelectorAll('input[name^="quantity"]');
                    const unitTypes = container.querySelectorAll('select[name^="unit_type"]');
                    if (selects[editRawMaterialIndex - 1] && quantities[editRawMaterialIndex - 1] && unitTypes[editRawMaterialIndex - 1]) {
                        selects[editRawMaterialIndex - 1].value = detail.raw_material_id || '';
                        quantities[editRawMaterialIndex - 1].value = detail.quantity || '';
                        unitTypes[editRawMaterialIndex - 1].value = detail.unit_type || 'adet';
                    }
                });
            }
)            .catch(error => console.error('Hata:', error));
    }

    function confirmDelete(recipeId) {
        document.getElementById('delete_recipe_id').value = recipeId;
        document.getElementById('delete_recipe_id_form').value = recipeId;
    }
</script>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>