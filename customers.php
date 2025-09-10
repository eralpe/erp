<?php
session_start();
require_once 'config/db.php';
require_once 'functions/customers.php';
require_once 'vendor/autoload.php'; // Composer autoloader

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Müşteri Yönetimi";
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => $page_title, 'url' => '']
];

try {
    $active_customers = getCustomers($pdo); // Sadece aktif müşteriler
    $all_customers = getCustomers($pdo, true); // Tüm müşteriler
    $inactive_customers = array_filter($all_customers, function($customer) {
        return $customer['status'] === 'inactive';
    });
} catch (Exception $e) {
    $error = $e->getMessage();
    $active_customers = [];
    $inactive_customers = [];
}

$error = null;
$success = null;

// CSRF token oluşturma
if (!isset($_SESSION['customer_token'])) {
    $_SESSION['customer_token'] = bin2hex(random_bytes(32));
}

// Yeni müşteri ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['customer_token'] = bin2hex(random_bytes(32));
        
        addCustomer(
            $pdo,
            $_POST['name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address']
        );
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Yeni müşteri eklendi: ".$_POST['name'],
            'medium'
        );
        $success = "Müşteri başarıyla eklendi.";
        header('Location: customers.php');
        exit;
    } catch (Exception $e) {
        $error = "Müşteri eklenirken hata oluştu: " . $e->getMessage();
    }
}

// Bakiye güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $transactionStarted = false;
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['customer_token'] = bin2hex(random_bytes(32));
        
        $customer_id = intval($_POST['customer_id']);
        $amount = floatval($_POST['amount']);
        $type = $_POST['type'];
        $description = $_POST['description'] ?: "Manuel bakiye güncelleme";
        
        // Müşteri durumunu kontrol et
        $stmt = $pdo->prepare("SELECT status FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer_status = $stmt->fetchColumn();
        if ($customer_status !== 'active') {
            throw new Exception("Pasif müşteriler için bakiye güncellenemez.");
        }
        
        if (!$customer_id || $amount <= 0 || !in_array($type, ['credit', 'debit'])) {
            throw new Exception("Geçerli bir müşteri, tutar ve işlem tipi seçin.");
        }
        
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // Bakiye güncelleme
        $stmt = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
        $adjustment = $type === 'credit' ? $amount : -$amount;
        $stmt->execute([$adjustment, $customer_id]);
        
        // İşlem kaydı
        $stmt = $pdo->prepare("INSERT INTO customer_transactions (customer_id, amount, type, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$customer_id, $amount, $type, $description]);
        
        // Log
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Müşteri bakiyesi güncellendi: {$type} {$amount} TRY for customer ID {$customer_id}"]);
        $pdo->commit();
        // Bildirim oluşturma (düşük önem)
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Müşteri bakiyesi güncellendi: $customer_id",
            'low'
        );
        $success = "Bakiye başarıyla güncellendi.";
        header('Location: customers.php');
        exit;
    } catch (Exception $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Bakiye güncellenirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Bakiye güncelleme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Müşteri durumu değiştirme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['customer_token'] = bin2hex(random_bytes(32));
        
        $customer_id = intval($_POST['customer_id']);
        $new_status = $_POST['new_status'];
        updateCustomerStatus($pdo, $customer_id, $new_status);
        // Bildirim oluşturma (düşük önem)
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Müşteri bilgisi güncellendi: $name",
            'low'
        );
        $success = "Müşteri durumu başarıyla güncellendi.";
        header('Location: customers.php');
        exit;
    } catch (Exception $e) {
        $error = "Müşteri durumu güncellenirken hata oluştu: " . $e->getMessage();
    }
}

// Müşteri silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_customer'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['customer_token'] = bin2hex(random_bytes(32));
        
        $customer_id = intval($_POST['customer_id']);
        deleteCustomer($pdo, $customer_id);
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Müşteri silindi: $customer_id",
            'low'
        );
        $success = "Müşteri başarıyla silindi.";
        header('Location: customers.php');
        exit;
    } catch (Exception $e) {
        $error = "Müşteri silinirken hata oluştu: " . $e->getMessage();
    }
}

// Müşterileri CSV olarak dışarı aktarma
if (isset($_GET['export'])) {
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="musteriler_' . date('Y-m-d_H-i-s') . '.csv"');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility
        fputcsv($output, ['Ad Soyad', 'E-posta', 'Telefon', 'Adres', 'Bakiye (TRY)', 'Durum'], ';');
        
        foreach ($all_customers as $customer) {
            fputcsv($output, [
                $customer['name'] ?? 'Bilinmeyen',
                $customer['email'] ?? '-',
                $customer['phone'] ?? '-',
                $customer['address'] ?? '-',
                number_format($customer['balance'] ?? 0.00, 2, ',', '.'),
                $customer['status'] === 'active' ? 'Aktif' : 'Pasif'
            ], ';');
        }
        fclose($output);
        exit;
    } catch (Exception $e) {
        $error = "Dışarı aktarma hatası: " . $e->getMessage();
    }
}

// Müşteri detaylarını PDF olarak dışarı aktarma
if (isset($_GET['export_pdf'])) {
    try {
        if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['customer_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        
        $customer_id = intval($_GET['customer_id']);
        $stmt = $pdo->prepare("SELECT name, email, phone, address, balance, status FROM customers WHERE id = ?");
        $stmt->execute([$customer_id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            throw new Exception("Müşteri bulunamadı.");
        }
        
        $stmt = $pdo->prepare("SELECT amount, type, description, created_at FROM customer_transactions WHERE customer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$customer_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // TCPDF ile PDF oluşturma
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Müşteri Yönetim Sistemi');
        $pdf->SetTitle('Müşteri Detayları: ' . $customer['name']);
        $pdf->SetSubject('Müşteri Bilgileri ve Hareketleri');
        $pdf->SetKeywords('Müşteri, PDF, Rapor');
        
        // Başlık ve kenar boşlukları
        $pdf->SetHeaderData('', 0, 'Müşteri Detayları: ' . $customer['name'], '');
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
        
        // HTML içeriği
        $html = '
        <style>
            h1 { font-size: 24px; color: #003087; }
            h2 { font-size: 18px; margin-top: 20px; }
            p { font-size: 14px; margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #000; padding: 8px; text-align: left; font-size: 14px; }
            th { background-color: #f2f2f2; }
        </style>
        <h1>Müşteri Detayları: ' . htmlspecialchars($customer['name']) . '</h1>
        <p><strong>Ad Soyad:</strong> ' . htmlspecialchars($customer['name'] ?? 'Bilinmeyen') . '</p>
        <p><strong>E-posta:</strong> ' . htmlspecialchars($customer['email'] ?? '-') . '</p>
        <p><strong>Telefon:</strong> ' . htmlspecialchars($customer['phone'] ?? '-') . '</p>
        <p><strong>Adres:</strong> ' . htmlspecialchars($customer['address'] ?? '-') . '</p>
        <p><strong>Bakiye:</strong> ' . number_format($customer['balance'] ?? 0.00, 2, ',', '.') . ' TRY</p>
        <p><strong>Durum:</strong> ' . ($customer['status'] === 'active' ? 'Aktif' : 'Pasif') . '</p>
        <h2>Bakiye Hareketleri</h2>
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tutar (TRY)</th>
                    <th>Tip</th>
                    <th>Açıklama</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($transactions as $transaction) {
            $date = date('d.m.Y H:i', strtotime($transaction['created_at']));
            $amount = number_format($transaction['amount'], 2, ',', '.');
            $type = $transaction['type'] === 'credit' ? 'Alacak' : 'Borç';
            $description = htmlspecialchars($transaction['description']);
            $html .= '
                <tr>
                    <td>' . $date . '</td>
                    <td>' . $amount . '</td>
                    <td>' . $type . '</td>
                    <td>' . $description . '</td>
                </tr>';
        }
        
        $html .= '
            </tbody>
        </table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('musteri_' . $customer_id . '_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
        exit;
    } catch (Exception $e) {
        $error = "PDF dışarı aktarma hatası: " . $e->getMessage();
    }
}

$_SESSION['customer_token'] = bin2hex(random_bytes(32));
ob_start();
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="fas fa-user-friends me-2"></i> Müşteri Listesi</h3>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                <i class="fas fa-plus-circle me-1"></i> Yeni Müşteri Ekle
            </button>
            <a href="customers.php?export=1&csrf_token=<?php echo $_SESSION['customer_token']; ?>" class="btn btn-success btn-sm">
                <i class="fas fa-download me-1"></i> Müşterileri CSV Olarak Dışarı Aktar
            </a>
        </div>
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link active" id="active-tab" data-bs-toggle="tab" href="#active-customers">Aktif Müşteriler</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="inactive-tab" data-bs-toggle="tab" href="#inactive-customers">Pasif Müşteriler</a>
            </li>
        </ul>
        <div class="tab-content">
            <!-- Aktif Müşteriler -->
            <div class="tab-pane fade show active" id="active-customers">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Telefon</th>
                                <th>Adres</th>
                                <th>Bakiye (TRY)</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? 'Bilinmeyen', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo number_format($customer['balance'] ?? 0.00, 2, ',', '.'); ?></td>
                                    <td><?php echo $customer['status'] === 'active' ? 'Aktif' : 'Pasif'; ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $customer['id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Detay
                                        </button>
                                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateBalanceModal<?php echo $customer['id']; ?>">
                                            <i class="fas fa-money-bill me-1"></i> Bakiye Güncelle
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Müşteriyi pasif hale getirmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <input type="hidden" name="toggle_status" value="1">
                                            <input type="hidden" name="new_status" value="inactive">
                                            <button type="submit" class="btn btn-secondary btn-sm"><i class="fas fa-ban me-1"></i> Pasif Yap</button>
                                        </form>
                                        <a href="customers.php?export_pdf=1&customer_id=<?php echo $customer['id']; ?>&csrf_token=<?php echo $_SESSION['customer_token']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Müşteriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <input type="hidden" name="delete_customer" value="1">
                                            <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash me-1"></i> Sil</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Pasif Müşteriler -->
            <div class="tab-pane fade" id="inactive-customers">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ad Soyad</th>
                                <th>E-posta</th>
                                <th>Telefon</th>
                                <th>Adres</th>
                                <th>Bakiye (TRY)</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inactive_customers as $customer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($customer['name'] ?? 'Bilinmeyen', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($customer['address'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo number_format($customer['balance'] ?? 0.00, 2, ',', '.'); ?></td>
                                    <td><?php echo $customer['status'] === 'active' ? 'Aktif' : 'Pasif'; ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $customer['id']; ?>">
                                            <i class="fas fa-eye me-1"></i> Detay
                                        </button>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Müşteriyi aktif hale getirmek istediğinizden emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <input type="hidden" name="toggle_status" value="1">
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i> Aktif Yap</button>
                                        </form>
                                        <a href="customers.php?export_pdf=1&customer_id=<?php echo $customer['id']; ?>&csrf_token=<?php echo $_SESSION['customer_token']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-file-pdf me-1"></i> PDF
                                        </a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Müşteriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                                            <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                            <input type="hidden" name="delete_customer" value="1">
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
    </div>
</div>

<!-- Yeni Müşteri Ekle Modal -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCustomerModalLabel">Yeni Müşteri Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                <input type="hidden" name="add_customer" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad Soyad</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Adres</label>
                        <textarea name="address" id="address" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Müşteri Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Müşteri Detay Modal -->
<?php foreach ($all_customers as $customer): ?>
    <div class="modal fade" id="detailsModal<?php echo $customer['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $customer['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailsModalLabel<?php echo $customer['id']; ?>">Müşteri Detay: <?php echo htmlspecialchars($customer['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($customer['name'] ?? 'Bilinmeyen'); ?></p>
                    <p><strong>E-posta:</strong> <?php echo htmlspecialchars($customer['email'] ?? '-'); ?></p>
                    <p><strong>Telefon:</strong> <?php echo htmlspecialchars($customer['phone'] ?? '-'); ?></p>
                    <p><strong>Adres:</strong> <?php echo htmlspecialchars($customer['address'] ?? '-'); ?></p>
                    <p><strong>Bakiye:</strong> <?php echo number_format($customer['balance'] ?? 0.00, 2, ',', '.'); ?> TRY</p>
                    <p><strong>Durum:</strong> <?php echo $customer['status'] === 'active' ? 'Aktif' : 'Pasif'; ?></p>
                    <h6>QR Kod:</h6>
                    <div id="qrcode-<?php echo $customer['id']; ?>" class="text-center"></div>
                    <script>
                        new QRCode(document.getElementById("qrcode-<?php echo $customer['id']; ?>"), {
                            text: "<?php echo 'http://localhost/test2/customer?id=' . $customer['id']; ?>",
                            width: 128,
                            height: 128
                        });
                    </script><hr>
                    <h6>Bakiye Hareketleri</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Tutar (TRY)</th>
                                    <th>Tip</th>
                                    <th>Açıklama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                try {
                                    $stmt = $pdo->prepare("SELECT amount, type, description, created_at FROM customer_transactions WHERE customer_id = ? ORDER BY created_at DESC");
                                    $stmt->execute([$customer['id']]);
                                    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    foreach ($transactions as $transaction):
                                ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($transaction['created_at'])); ?></td>
                                        <td><?php echo number_format($transaction['amount'], 2, ',', '.'); ?></td>
                                        <td><?php echo $transaction['type'] === 'credit' ? 'Alacak' : 'Borç'; ?></td>
                                        <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php } catch (PDOException $e) { ?>
                                    <tr><td colspan="4">Hareketler yüklenirken hata oluştu: <?php echo htmlspecialchars($e->getMessage()); ?></td></tr>
                                <?php } ?>
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

    <!-- Bakiye Güncelleme Modal (sadece aktif müşteriler için) -->
    <?php if ($customer['status'] === 'active'): ?>
        <div class="modal fade" id="updateBalanceModal<?php echo $customer['id']; ?>" tabindex="-1" aria-labelledby="updateBalanceModalLabel<?php echo $customer['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateBalanceModalLabel<?php echo $customer['id']; ?>">Bakiye Güncelle: <?php echo htmlspecialchars($customer['name']); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['customer_token']; ?>">
                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                        <input type="hidden" name="update_balance" value="1">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                Mevcut Bakiye: <?php echo number_format($customer['balance'] ?? 0.00, 2, ',', '.'); ?> TRY
                            </div>
                            <div class="mb-3">
                                <label for="type_<?php echo $customer['id']; ?>" class="form-label">İşlem Tipi</label>
                                <select class="form-select" id="type_<?php echo $customer['id']; ?>" name="type" required>
                                    <option value="" disabled selected>Seçin</option>
                                    <option value="credit">Alacak (Bakiye Artışı)</option>
                                    <option value="debit">Borç (Bakiye Azalışı)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="amount_<?php echo $customer['id']; ?>" class="form-label">Tutar</label>
                                <input type="number" step="0.01" class="form-control" id="amount_<?php echo $customer['id']; ?>" name="amount" required>
                            </div>
                            <div class="mb-3">
                                <label for="description_<?php echo $customer['id']; ?>" class="form-label">Açıklama</label>
                                <input type="text" class="form-control" id="description_<?php echo $customer['id']; ?>" name="description" placeholder="Manuel bakiye güncelleme">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                            <button type="submit" class="btn btn-primary">Güncelle</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>