<?php
ob_start();
require_once 'config/db.php';
require_once 'functions/inventory.php';
require_once 'functions.php';
require_once 'vendor/autoload.php'; // TCPDF için Composer autoloader

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Stok Yönetimi";
$error = null;
$success = null;

// CSRF token oluşturma
if (!isset($_SESSION['inventory_token'])) {
    $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
}

// Ürün ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
        
        addProduct(
            $pdo,
            $_POST['product_code'],
            $_POST['product_name'],
            $_POST['category_id'],
            $_POST['unit'],
            $_POST['stock_quantity'],
            $_POST['min_stock_level']
        );
         // Bildirim oluşturma (orta önem)
        createNotification(
            $_SESSION['user_id'],
            'stock',
            "Yeni ürün eklendi: ".$_POST['product_name'],
            'medium'
        );
        $success = "Ürün başarıyla eklendi.";
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        $error = "Ürün eklenirken hata oluştu: " . $e->getMessage();
    }
}

// Ürün düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
        
        updateProduct(
            $pdo,
            $_POST['id'],
            $_POST['product_code'],
            $_POST['product_name'],
            $_POST['category_id'],
            $_POST['unit'],
            $_POST['stock_quantity'],
            $_POST['min_stock_level']
        );
        $success = "Ürün başarıyla güncellendi.";
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        $error = "Ürün güncellenirken hata oluştu: " . $e->getMessage();
    }
}

// Ürün silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
        
        deleteProduct($pdo, $_POST['id']);
        $success = "Ürün başarıyla silindi.";
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        $error = "Ürün silinirken hata oluştu: " . $e->getMessage();
    }
}

// Stok hareketi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['inventory_token'] = bin2hex(random_bytes(32));
        
        addInventoryTransaction(
            $pdo,
            $_POST['product_id'],
            $_POST['type'],
            $_POST['quantity'],
            $_POST['description'],
            null,
            'manual'
        ); 
        // Bildirim oluşturma (düşük önem)
        createNotification(
            $_SESSION['user_id'],
            'stock',
            "Ürün bilgisi güncellendi: ".$_POST['product_id'],
            'low'
        );
        $success = "Stok hareketi başarıyla eklendi.";
        header('Location: inventory.php');
        exit;
    } catch (Exception $e) {
        $error = "Stok hareketi eklenirken hata oluştu: " . $e->getMessage();
    }
}

// PDF dışarı aktarma
if (isset($_GET['export_pdf'])) {
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        
        $products = $pdo->query("SELECT i.*, c.name as category_name FROM inventory i LEFT JOIN categories c ON i.category_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
        
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Stok Yönetim Sistemi');
        $pdf->SetTitle('Stok Listesi');
        $pdf->SetSubject('Stok Raporu');
        $pdf->SetKeywords('Stok, PDF, Rapor');
        
        $pdf->SetHeaderData('', 0, 'Stok Listesi', '');
        $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
        $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->AddPage();
        
        $html = '
        <style>
            h1 { font-size: 24px; color: #003087; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 14px; }
            th { background-color: #f2f2f2; }
        </style>
        <h1>Stok Listesi</h1>
        <table>
            <thead>
                <tr>
                    <th>Ürün Kodu</th>
                    <th>Ürün Adı</th>
                    <th>Kategori</th>
                    <th>Birim</th>
                    <th>Stok Miktarı</th>
                    <th>Minimum Stok</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($products as $product) {
            $html .= '
                <tr>
                    <td>' . htmlspecialchars($product['product_code']) . '</td>
                    <td>' . htmlspecialchars($product['product_name']) . '</td>
                    <td>' . htmlspecialchars($product['category_name'] ?: '-') . '</td>
                    <td>' . htmlspecialchars($product['unit']) . '</td>
                    <td>' . number_format($product['stock_quantity'], 2, ',', '.') . '</td>
                    <td>' . number_format($product['min_stock_level'], 2, ',', '.') . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('stok_listesi_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
        $error = "PDF dışarı aktarma hatası: " . $e->getMessage();
    }
}

// CSV dışarı aktarma
if (isset($_GET['export_csv'])) {
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['inventory_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stok_listesi_' . date('Y-m-d_H-i-s') . '.csv"');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($output, ['Ürün Kodu', 'Ürün Adı', 'Kategori', 'Birim', 'Stok Miktarı', 'Minimum Stok'], ';');
        
        $products = $pdo->query("SELECT i.*, c.name as category_name FROM inventory i LEFT JOIN categories c ON i.category_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($products as $product) {
            fputcsv($output, [
                $product['product_code'],
                $product['product_name'],
                $product['category_name'] ?: '-',
                $product['unit'],
                number_format($product['stock_quantity'], 2, ',', '.'),
                number_format($product['min_stock_level'], 2, ',', '.')
            ], ';');
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        $error = "CSV dışarı aktarma hatası: " . $e->getMessage();
    }
}

$products = $pdo->query("SELECT i.*, c.name as category_name FROM inventory i LEFT JOIN categories c ON i.category_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$_SESSION['inventory_token'] = bin2hex(random_bytes(32));
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index'],
    ['title' => 'Stok Yönetimi', 'url' => '']
];
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="fas fa-warehouse me-2"></i> Stok Listesi</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus-circle me-1"></i> Yeni Ürün Ekle
            </button>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-exchange-alt me-1"></i> Stok Hareketi Ekle
            </button>
            <a href="inventory.php?export_csv=1&csrf_token=<?php echo $_SESSION['inventory_token']; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download me-1"></i> CSV Dışarı Aktar
            </a>
            <a href="inventory.php?export_pdf=1&csrf_token=<?php echo $_SESSION['inventory_token']; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-file-pdf me-1"></i> PDF Dışarı Aktar
            </a>
        </div>
        <div class="mb-3">
            <label for="category_filter" class="form-label">Kategoriye Göre Filtrele</label>
            <select id="category_filter" class="form-select" onchange="filterProducts(this.value)">
                <option value="">Tümü</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <script>
            function filterProducts(categoryId) {
                document.querySelectorAll('table tbody tr').forEach(row => {
                    const category = row.querySelector('td:nth-child(3)').textContent;
                    row.style.display = categoryId && !category.includes(categoryId) ? 'none' : '';
                });
            }
        </script>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Ürün Kodu</th>
                        <th>Ürün Adı</th>
                        <th>Kategori</th>
                        <th>Birim</th>
                        <th>Stok Miktarı</th>
                        <th>Minimum Stok</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr <?php echo $product['stock_quantity'] < $product['min_stock_level'] ? 'class="table-danger"' : ''; ?>>
                            <td><?php echo htmlspecialchars($product['product_code'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($product['unit'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo number_format($product['stock_quantity'], 2, ',', '.'); ?></td>
                            <td><?php echo number_format($product['min_stock_level'], 2, ',', '.'); ?></td>
                            <td><?php echo $product['stock_quantity'] < $product['min_stock_level'] ? 'Düşük Stok' : 'Normal'; ?></td>
                            <td>
                                <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewTransactionsModal<?php echo $product['id']; ?>">
                                    <i class="fas fa-eye me-1"></i> Hareketler
                                </button>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editProductModal<?php echo $product['id']; ?>">
                                    <i class="fas fa-edit me-1"></i> Düzenle
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Ürünü silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['inventory_token']; ?>">
                                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="delete_product" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash me-1"></i> Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Ürün Ekle Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['inventory_token']; ?>">
                <input type="hidden" name="add_product" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">Yeni Ürün Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_code" class="form-label">Ürün Kodu</label>
                        <input type="text" name="product_code" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="product_name" class="form-label">Ürün Adı</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Seçiniz</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="unit" class="form-label">Birim</label>
                        <input type="text" name="unit" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">Stok Miktarı</label>
                        <input type="number" step="0.01" name="stock_quantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="min_stock_level" class="form-label">Minimum Stok Seviyesi</label>
                        <input type="number" step="0.01" name="min_stock_level" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Stok Hareketi Ekle Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['inventory_token']; ?>">
                <input type="hidden" name="add_transaction" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="addTransactionModalLabel">Stok Hareketi Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Ürün</label>
                        <select name="product_id" class="form-select" required>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Hareket Tipi</label>
                        <select name="type" class="form-select" required>
                            <option value="in">Giriş</option>
                            <option value="out">Çıkış</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Miktar</label>
                        <input type="number" step="0.01" name="quantity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Ürün Düzenleme ve Hareket Görüntüleme Modalları -->
<?php foreach ($products as $product): ?>
    <!-- Düzenleme Modal -->
    <div class="modal fade" id="editProductModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="editProductModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['inventory_token']; ?>">
                    <input type="hidden" name="update_product" value="1">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProductModalLabel<?php echo $product['id']; ?>">Ürün Düzenle: <?php echo htmlspecialchars($product['product_name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="product_code_<?php echo $product['id']; ?>" class="form-label">Ürün Kodu</label>
                            <input type="text" name="product_code" class="form-control" value="<?php echo htmlspecialchars($product['product_code']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="product_name_<?php echo $product['id']; ?>" class="form-label">Ürün Adı</label>
                            <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id_<?php echo $product['id']; ?>" class="form-label">Kategori</label>
                            <select name="category_id" class="form-select">
                                <option value="">Seçiniz</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="unit_<?php echo $product['id']; ?>" class="form-label">Birim</label>
                            <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($product['unit']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock_quantity_<?php echo $product['id']; ?>" class="form-label">Stok Miktarı</label>
                            <input type="number" step="0.01" name="stock_quantity" class="form-control" value="<?php echo $product['stock_quantity']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="min_stock_level_<?php echo $product['id']; ?>" class="form-label">Minimum Stok Seviyesi</label>
                            <input type="number" step="0.01" name="min_stock_level" class="form-control" value="<?php echo $product['min_stock_level']; ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hareket Görüntüleme Modal -->
    <div class="modal fade" id="viewTransactionsModal<?php echo $product['id']; ?>" tabindex="-1" aria-labelledby="viewTransactionsModalLabel<?php echo $product['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewTransactionsModalLabel<?php echo $product['id']; ?>">Stok Hareketleri: <?php echo htmlspecialchars($product['product_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Tip</th>
                                    <th>Miktar</th>
                                    <th>Açıklama</th>
                                    <th>İlgili İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->prepare("SELECT * FROM inventory_transactions WHERE product_id = ? ORDER BY created_at DESC");
                                $stmt->execute([$product['id']]);
                                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($transactions as $transaction):
                                ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td><?php echo $transaction['type'] === 'in' ? 'Giriş' : 'Çıkış'; ?></td>
                                        <td><?php echo number_format($transaction['quantity'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></td>
                                        <td>
                                            <?php
                                            if ($transaction['related_id'] && $transaction['related_type'] === 'sale') {
                                                echo '<a href="sales.php?id=' . $transaction['related_id'] . '">Satış #' . $transaction['related_id'] . '</a>';
                                            } elseif ($transaction['related_type'] === 'purchase') {
                                                echo '<a href="purchase_orders.php?id=' . $transaction['related_id'] . '">Sipariş #' . $transaction['related_id'] . '</a>';
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>