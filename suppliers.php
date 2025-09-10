<?php
// suppliers.php
session_start();
ob_start();
require_once 'config/db.php';
require_once 'functions/customers.php';
require_once 'functions/suppliers.php';

if (!isset($_SESSION['supplier_token'])) {
    $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
}

$title = 'Tedarikçi Yönetimi';
$error = null;
$success = null;

// Tedarikçi ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supplier'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        $name = trim($_POST['name']);
        $balance = floatval($_POST['balance'] ?? 0.00);
        $currency = trim($_POST['currency'] ?? 'TRY');
        $contact_name = trim($_POST['contact_name'] ?: '');
        $email = trim($_POST['email'] ?: '');
        $phone = trim($_POST['phone'] ?: '');
        $city = trim($_POST['city'] ?: '');
        $district = trim($_POST['district'] ?: '');
        $address = $city . ', ' . $district; // Adres birleştirme

        if (empty($name) || empty($city) || empty($district) || ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) || ($phone && !preg_match('/^\+?\d{10,15}$/', $phone))) {
            throw new Exception("Geçerli bir ad, il, ilçe, e-posta ve telefon numarası girin.");
        }

        $stmt = $pdo->prepare("INSERT INTO suppliers (name, balance, contact_name, email, phone, address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $balance, $contact_name ?: null, $email ?: null, $phone ?: null, $address ?: null]);

        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Tedarikçi {$name} eklendi (Bakiye: {$balance} {$currency})."]);
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Yeni tedarikçi eklendi: $name (Bakiye: {$balance} {$currency})",
            'medium'
        );
        $success = "Tedarikçi başarıyla eklendi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        $error = "Tedarikçi eklenirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Tedarikçi ekleme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Tedarikçi güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_supplier'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $balance = floatval($_POST['balance'] ?? 0.00);
        $currency = trim($_POST['currency'] ?? 'TRY');
        $contact_name = trim($_POST['contact_name'] ?: '');
        $email = trim($_POST['email'] ?: '');
        $phone = trim($_POST['phone'] ?: '');
        $city = trim($_POST['city'] ?: '');
        $district = trim($_POST['district'] ?: '');
        $address = $city . ', ' . $district;

        if (empty($name) || !$id || empty($city) || empty($district) || ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) || ($phone && !preg_match('/^\+?\d{10,15}$/', $phone))) {
            throw new Exception("Geçerli bir ID, ad, il, ilçe, e-posta ve telefon numarası girin.");
        }

        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, balance = ?, contact_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $balance, $contact_name ?: null, $email ?: null, $phone ?: null, $address ?: null, $id]);

        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Tedarikçi ID {$id} güncellendi: {$name} (Bakiye: {$balance} {$currency})."]);
        createNotification(
            $_SESSION['user_id'],
            'general',
            "Tedarikçi güncellendi: $name (Bakiye: {$balance} {$currency})",
            'low'
        );
        $success = "Tedarikçi başarıyla güncellendi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        $error = "Tedarikçi güncellenirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Tedarikçi güncelleme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Tedarikçi silme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_supplier'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        $id = intval($_POST['supplier_id']);
        if (!$id) {
            throw new Exception("Geçerli bir tedarikçi ID girin.");
        }
        $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Tedarikçi ID {$id} silindi."]);
        $success = "Tedarikçi başarıyla silindi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        $error = "Tedarikçi silinirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Tedarikçi silme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Fatura ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_invoice'])) {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        $supplier_id = intval($_POST['supplier_id']);
        $invoice_number = trim($_POST['invoice_number']);
        $amount = floatval($_POST['amount']);
        $issue_date = $_POST['issue_date'];
        $due_date = $_POST['due_date'];
        
        if (!$supplier_id || !$invoice_number || $amount <= 0 || !$issue_date || !$due_date || $issue_date > $due_date) {
            throw new Exception("Geçerli bir tedarikçi, fatura numarası, tutar, fatura tarihi ve vade tarihi girin.");
        }
        
        // Fatura numarasının benzersizliğini kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE supplier_id = ? AND invoice_number = ?");
        $stmt->execute([$supplier_id, $invoice_number]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Bu fatura numarası zaten mevcut.");
        }
        
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO invoices (supplier_id, invoice_number, amount, issue_date, due_date, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$supplier_id, $invoice_number, $amount, $issue_date, $due_date]);
        
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount, $supplier_id]);
        
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Fatura #{$invoice_number} tedarikçi ID {$supplier_id} için eklendi."]);
        $pdo->commit();
        $success = "Fatura başarıyla eklendi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Fatura eklenirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Fatura ekleme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Ödeme ekleme (fatura veya bakiye)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $transactionStarted = false;
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        
        if (!$pdo) {
            throw new Exception("Veritabanı bağlantısı başarısız.");
        }
        
        $payment_type = $_POST['payment_type'];
        $amount = floatval($_POST['amount']);
        $supplier_id = intval($_POST['supplier_id']);
        $payment_date = $_POST['payment_date'];
        $account_id = intval($_POST['account_id']);
        
        if (!$supplier_id || !$payment_type || $amount <= 0 || !$payment_date || !$account_id || new DateTime($payment_date) > new DateTime()) {
            throw new Exception("Geçerli bir tedarikçi, ödeme tipi, tutar, ödeme tarihi ve kasa hesabı girin.");
        }
        
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // Kasa hesabı bakiyesi kontrolü
        $stmt = $pdo->prepare("SELECT balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account_balance = $stmt->fetchColumn();
        if ($account_balance === false || $account_balance < $amount) {
            throw new Exception("Kasa hesabında yeterli bakiye yok: Mevcut {$account_balance} TRY, Gerekli {$amount} TRY.");
        }
        
        // Tedarikçi bakiyesi kontrolü
        $stmt = $pdo->prepare("SELECT balance FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $supplier_balance = $stmt->fetchColumn();
        if ($supplier_balance === false || $supplier_balance < $amount) {
            throw new Exception("Tedarikçi bakiyesi yetersiz: Mevcut {$supplier_balance} TRY, Gerekli {$amount} TRY.");
        }
        
        // Ödeme kaydı ekleme
        $stmt = $pdo->prepare("INSERT INTO payments (supplier_id, invoice_id, amount, payment_date, payment_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        if ($payment_type == 'invoice') {
            if (!isset($_POST['invoice_id']) || empty($_POST['invoice_id'])) {
                throw new Exception("Fatura seçimi zorunludur.");
            }
            $invoice_id = intval($_POST['invoice_id']);
            $stmt_check = $pdo->prepare("SELECT amount, status FROM invoices WHERE id = ? AND supplier_id = ?");
            $stmt_check->execute([$invoice_id, $supplier_id]);
            $invoice = $stmt_check->fetch(PDO::FETCH_ASSOC);
            if (!$invoice || $invoice['status'] == 'paid') {
                throw new Exception("Geçersiz veya zaten ödenmiş fatura.");
            }
            if (abs($invoice['amount'] - $amount) > 0.01) {
                throw new Exception("Ödeme tutarı fatura tutarıyla eşleşmiyor: Fatura {$invoice['amount']} TRY, Girilen {$amount} TRY.");
            }
            $stmt->execute([$supplier_id, $invoice_id, $amount, $payment_date, $payment_type]);
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_amount = paid_amount + ? WHERE id = ?");
            $stmt->execute([$amount, $invoice_id]);
        } else {
            $stmt->execute([$supplier_id, null, $amount, $payment_date, $payment_type]);
        }
        
        // Tedarikçi bakiyesini düş
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $supplier_id]);
        
        // Kasa işlemleri
        $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, amount, type, description, created_at) VALUES (?, ?, 'debit', ?, NOW())");
        $stmt->execute([$account_id, $amount, "Tedarikçi ödemesi (ID: {$supplier_id}, {$payment_type})"]);
        
        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$amount, $account_id]);
        
        // Log
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Ödeme {$payment_type} of {$amount} TRY for supplier ID {$supplier_id}"]);
        $pdo->commit();
        $success = "Ödeme başarıyla eklendi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Ödeme eklenirken hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Ödeme ekleme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Toplu fatura ödeme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_all_invoices'])) {
    $transactionStarted = false;
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['supplier_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['supplier_token'] = bin2hex(random_bytes(32));
        
        if (!$pdo) {
            throw new Exception("Veritabanı bağlantısı başarısız.");
        }
        
        $supplier_id = intval($_POST['supplier_id']);
        $payment_date = $_POST['payment_date'];
        $account_id = intval($_POST['account_id']);
        
        if (!$supplier_id || !$payment_date || !$account_id || new DateTime($payment_date) > new DateTime()) {
            throw new Exception("Geçerli bir tedarikçi, ödeme tarihi ve kasa hesabı girin.");
        }
        
        $pdo->beginTransaction();
        $transactionStarted = true;
        
        // Ödenmemiş faturaları al
        $stmt = $pdo->prepare("SELECT id, amount FROM invoices WHERE supplier_id = ? AND status = 'pending'");
        $stmt->execute([$supplier_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $total_amount = 0;
        
        if (empty($invoices)) {
            throw new Exception("Ödenmemiş fatura bulunamadı.");
        }
        
        // Toplam tutarı hesapla ve kasa hesabını kontrol et
        $stmt = $pdo->prepare("SELECT balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account_balance = $stmt->fetchColumn();
        foreach ($invoices as $invoice) {
            $total_amount += floatval($invoice['amount']);
        }
        if ($account_balance === false || $account_balance < $total_amount) {
            throw new Exception("Kasa hesabında yeterli bakiye yok: Mevcut {$account_balance} TRY, Gerekli {$total_amount} TRY.");
        }
        
        // Tedarikçi bakiyesini kontrol et
        $stmt = $pdo->prepare("SELECT balance FROM suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $supplier_balance = $stmt->fetchColumn();
        if ($supplier_balance === false || $supplier_balance < $total_amount) {
            throw new Exception("Tedarikçi bakiyesi yetersiz: Mevcut {$supplier_balance} TRY, Gerekli {$total_amount} TRY.");
        }
        
        // Her fatura için ödeme kaydı ekle
        foreach ($invoices as $invoice) {
            $stmt = $pdo->prepare("INSERT INTO payments (supplier_id, invoice_id, amount, payment_date, payment_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$supplier_id, $invoice['id'], $invoice['amount'], $payment_date, 'invoice']);
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_amount = paid_amount + ? WHERE id = ?");
            $stmt->execute([$invoice['amount'], $invoice['id']]);
        }
        
        // Tedarikçi bakiyesini düş
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$total_amount, $supplier_id]);
        
        // Kasa işlemleri
        $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, amount, type, description, created_at) VALUES (?, ?, 'debit', ?, NOW())");
        $stmt->execute([$account_id, $total_amount, "Tedarikçi toplu fatura ödemesi (ID: {$supplier_id})"]);
        
        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$total_amount, $account_id]);
        
        // Log
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['info', "Toplu fatura ödemesi {$total_amount} TRY for supplier ID {$supplier_id}"]);
        $pdo->commit();
        $success = "Tüm faturalar başarıyla ödendi.";
        header('Location: suppliers.php');
        exit;
    } catch (Exception $e) {
        if ($transactionStarted && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = "Toplu ödeme sırasında hata oluştu: " . $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Toplu ödeme hatası: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Tedarikçi listeleme
$suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
$stmt->execute(['info', "Fetched " . count($suppliers) . " suppliers and " . count($accounts) . " cash accounts"]);

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index'],
    ['title' => 'Tedarikçiler', 'url' => '']
];

$page_title = 'Tedarikçi Yönetimi';
$content = ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tedarikçi Yönetimi</h5>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
            <i class="fas fa-plus me-2"></i>Yeni Tedarikçi Ekle
        </button>
    </div>
    <div class="card-body">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (empty($suppliers)): ?>
            <div class="alert alert-info">Kayıtlı tedarikçi bulunmamaktadır.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad</th>
                            <th>Bakiye</th>
                            <th>İletişim Kişisi</th>
                            <th>E-posta</th>
                            <th>Telefon</th>
                            <th>Adres</th>
                            <th>İletişim</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['id']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['name']); ?></td>
                                <td><?php echo number_format($supplier['balance'] ?? 0.00, 2); ?> TRY</td>
                                <td><?php echo htmlspecialchars($supplier['contact_name'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['email'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['phone'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['address'] ?: '-'); ?></td>
                                <td><?php echo htmlspecialchars($supplier['contact'] ?: '-'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#invoicesModal<?php echo $supplier['id']; ?>" data-supplier-id="<?php echo $supplier['id']; ?>" data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>">
                                        <i class="fas fa-file-invoice me-1"></i>Faturalar
                                    </button>
                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addInvoiceModal<?php echo $supplier['id']; ?>" data-supplier-id="<?php echo $supplier['id']; ?>" data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>">
                                        <i class="fas fa-plus me-1"></i>Fatura Ekle
                                    </button>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal<?php echo $supplier['id']; ?>" data-supplier-id="<?php echo $supplier['id']; ?>" data-supplier-name="<?php echo htmlspecialchars($supplier['name']); ?>">
                                        <i class="fas fa-money-bill me-1"></i>Ödeme Ekle
                                    </button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editSupplierModal<?php echo $supplier['id']; ?>" data-id="<?php echo $supplier['id']; ?>" data-name="<?php echo htmlspecialchars($supplier['name']); ?>" data-balance="<?php echo $supplier['balance'] ?? 0.00; ?>" data-contact_name="<?php echo htmlspecialchars($supplier['contact_name'] ?: ''); ?>" data-email="<?php echo htmlspecialchars($supplier['email'] ?: ''); ?>" data-phone="<?php echo htmlspecialchars($supplier['phone'] ?: ''); ?>" data-address="<?php echo htmlspecialchars($supplier['address'] ?: ''); ?>" data-contact="<?php echo htmlspecialchars($supplier['contact'] ?: ''); ?>">
                                        <i class="fas fa-edit me-1"></i>Düzenle
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Bu tedarikçiyi ve ilgili kayıtlarını silmek istediğinizden emin misiniz?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                                        <button type="submit" name="delete_supplier" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash me-1"></i>Sil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Yeni Tedarikçi Ekle Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel">Yeni Tedarikçi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="addSupplierForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                <input type="hidden" name="add_supplier" value="1">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="balance" class="form-label">Bakiye</label>
                        <div class="input-group">
                            <input type="number" step="0.01" class="form-control" id="balance" name="balance" value="0.00" required>
                            <select class="form-select" id="currency" name="currency" required>
                                <option value="TRY">TRY</option>
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="contact_name" class="form-label">İletişim Kişisi</label>
                        <input type="text" class="form-control" id="contact_name" name="contact_name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Telefon</label>
                        <input type="text" class="form-control" id="phone" name="phone" pattern="\+?\d{10,15}">
                    </div>
                    <div class="mb-3">
                        <label for="city" class="form-label">İl</label>
                        <input type="text" class="form-control" id="city" name="city" required>
                    </div>
                    <div class="mb-3">
                        <label for="district" class="form-label">İlçe</label>
                        <input type="text" class="form-control" id="district" name="district" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($suppliers as $supplier): ?>
    <!-- Tedarikçi Düzenleme Modal -->
    <div class="modal fade" id="editSupplierModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-labelledby="editSupplierModalLabel<?php echo $supplier['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editSupplierModalLabel<?php echo $supplier['id']; ?>">Tedarikçi Düzenle: <?php echo htmlspecialchars($supplier['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editSupplierForm<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                    <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="update_supplier" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name_<?php echo $supplier['id']; ?>" class="form-label">Ad</label>
                            <input type="text" class="form-control" id="edit_name_<?php echo $supplier['id']; ?>" name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_balance_<?php echo $supplier['id']; ?>" class="form-label">Bakiye</label>
                            <div class="input-group">
                                <input type="number" step="0.01" class="form-control" id="edit_balance_<?php echo $supplier['id']; ?>" name="balance" value="<?php echo $supplier['balance'] ?? 0.00; ?>" required>
                                <select class="form-select" id="edit_currency_<?php echo $supplier['id']; ?>" name="currency" required>
                                    <option value="TRY" <?php echo ($supplier['currency'] ?? 'TRY') === 'TRY' ? 'selected' : ''; ?>>TRY</option>
                                    <option value="USD" <?php echo ($supplier['currency'] ?? 'TRY') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                    <option value="EUR" <?php echo ($supplier['currency'] ?? 'TRY') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_contact_name_<?php echo $supplier['id']; ?>" class="form-label">İletişim Kişisi</label>
                            <input type="text" class="form-control" id="edit_contact_name_<?php echo $supplier['id']; ?>" name="contact_name" value="<?php echo htmlspecialchars($supplier['contact_name'] ?: ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="edit_email_<?php echo $supplier['id']; ?>" class="form-label">E-posta</label>
                            <input type="email" class="form-control" id="edit_email_<?php echo $supplier['id']; ?>" name="email" value="<?php echo htmlspecialchars($supplier['email'] ?: ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone_<?php echo $supplier['id']; ?>" class="form-label">Telefon</label>
                            <input type="text" class="form-control" id="edit_phone_<?php echo $supplier['id']; ?>" name="phone" value="<?php echo htmlspecialchars($supplier['phone'] ?: ''); ?>" pattern="\+?\d{10,15}">
                        </div>
                        <div class="mb-3">
                            <label for="edit_city_<?php echo $supplier['id']; ?>" class="form-label">İl</label>
                            <input type="text" class="form-control" id="edit_city_<?php echo $supplier['id']; ?>" name="city" value="<?php echo htmlspecialchars(explode(', ', $supplier['address'])[0] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_district_<?php echo $supplier['id']; ?>" class="form-label">İlçe</label>
                            <input type="text" class="form-control" id="edit_district_<?php echo $supplier['id']; ?>" name="district" value="<?php echo htmlspecialchars(explode(', ', $supplier['address'])[1] ?? ''); ?>" required>
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

    <!-- Fatura Ekleme Modal -->
    <div class="modal fade" id="addInvoiceModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-labelledby="addInvoiceModalLabel<?php echo $supplier['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addInvoiceModalLabel<?php echo $supplier['id']; ?>">Fatura Ekle: <?php echo htmlspecialchars($supplier['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="addInvoiceForm<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="add_invoice" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="invoice_number_<?php echo $supplier['id']; ?>" class="form-label">Fatura Numarası</label>
                            <input type="text" class="form-control" id="invoice_number_<?php echo $supplier['id']; ?>" name="invoice_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="amount_<?php echo $supplier['id']; ?>" class="form-label">Tutar</label>
                            <input type="number" step="0.01" class="form-control" id="amount_<?php echo $supplier['id']; ?>" name="amount" required>
                        </div>
                        <div class="mb-3">
                            <label for="issue_date_<?php echo $supplier['id']; ?>" class="form-label">Fatura Tarihi</label>
                            <input type="date" class="form-control" id="issue_date_<?php echo $supplier['id']; ?>" name="issue_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="due_date_<?php echo $supplier['id']; ?>" class="form-label">Vade Tarihi</label>
                            <input type="date" class="form-control" id="due_date_<?php echo $supplier['id']; ?>" name="due_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="button" class="btn btn-primary" onclick="confirmAddInvoice(<?php echo $supplier['id']; ?>)">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ödeme Ekleme Modal -->
    <div class="modal fade" id="addPaymentModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-labelledby="addPaymentModalLabel<?php echo $supplier['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPaymentModalLabel<?php echo $supplier['id']; ?>">Ödeme Ekle: <?php echo htmlspecialchars($supplier['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="paymentForm<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                    <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                    <input type="hidden" name="add_payment" value="1">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="payment_type_<?php echo $supplier['id']; ?>" class="form-label">Ödeme Türü</label>
                            <select class="form-select" id="payment_type_<?php echo $supplier['id']; ?>" name="payment_type" onchange="toggleInvoiceSelect(<?php echo $supplier['id']; ?>)" required>
                                <option value="" disabled selected>Ödeme türü seçin</option>
                                <option value="invoice">Fatura Ödemesi (Belirli bir faturayı öde)</option>
                                <option value="balance">Bakiye Ödemesi (Genel bakiyeden düş)</option>
                            </select>
                        </div>
                        <div class="mb-3" id="balance_info_<?php echo $supplier['id']; ?>" style="display: none;">
                            <div class="alert alert-info">
                                Mevcut Tedarikçi Bakiyesi: <?php echo number_format($supplier['balance'] ?? 0.00, 2); ?> TRY
                                <br>
                                <small>Genel bakiyeden düşülecek tutarı aşağıda belirtin.</small>
                            </div>
                        </div>
                        <div class="mb-3" id="invoice_select_container_<?php echo $supplier['id']; ?>" style="display: none;">
                            <label for="invoice_id_<?php echo $supplier['id']; ?>" class="form-label">Fatura Seç</label>
                            <select class="form-select" id="invoice_id_<?php echo $supplier['id']; ?>" name="invoice_id">
                                <option value="">Fatura Seçin</option>
                            </select>
                            <div id="invoice_error_<?php echo $supplier['id']; ?>" class="error-message" style="display: none;"></div>
                        </div>
                        <div class="mb-3">
                            <label for="payment_amount_<?php echo $supplier['id']; ?>" class="form-label">Tutar</label>
                            <input type="number" step="0.01" class="form-control" id="payment_amount_<?php echo $supplier['id']; ?>" name="amount" required>
                            <small id="amount_help_<?php echo $supplier['id']; ?>" class="form-text text-muted"></small>
                        </div>
                        <div class="mb-3">
                            <label for="account_id_<?php echo $supplier['id']; ?>" class="form-label">Kasa Hesabı</label>
                            <select class="form-select" id="account_id_<?php echo $supplier['id']; ?>" name="account_id" required>
                                <option value="" disabled selected>Bir kasa hesabı seçin</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_date_<?php echo $supplier['id']; ?>" class="form-label">Ödeme Tarihi</label>
                            <input type="date" class="form-control" id="payment_date_<?php echo $supplier['id']; ?>" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <button type="button" class="btn btn-primary" onclick="confirmPayment(<?php echo $supplier['id']; ?>)">Ekle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Faturalar Modal -->
    <!-- Faturalar Modal -->
    <div class="modal fade" id="invoicesModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-labelledby="invoicesModalLabel<?php echo $supplier['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="invoicesModalLabel<?php echo $supplier['id']; ?>">Faturalar: <?php echo htmlspecialchars($supplier['name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="total_amount_info_<?php echo $supplier['id']; ?>" class="alert alert-info" style="display: none;">
                        Toplam Ödenecek Tutar: <span id="total_amount_<?php echo $supplier['id']; ?>">0.00</span> TRY
                    </div>
                    <form method="POST" id="payAllInvoicesForm<?php echo $supplier['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['supplier_token']; ?>">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                        <input type="hidden" name="pay_all_invoices" value="1">
                        <div class="mb-3">
                            <label for="invoices_payment_date_<?php echo $supplier['id']; ?>" class="form-label">Ödeme Tarihi</label>
                            <input type="date" class="form-control" id="invoices_payment_date_<?php echo $supplier['id']; ?>" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="invoices_account_id_<?php echo $supplier['id']; ?>" class="form-label">Kasa Hesabı</label>
                            <select class="form-select" id="invoices_account_id_<?php echo $supplier['id']; ?>" name="account_id" required>
                                <option value="" disabled selected>Bir kasa hesabı seçin</option>
                                <?php foreach ($accounts as $account): ?>
                                    <option value="<?php echo $account['id']; ?>">
                                        <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm mb-3" onclick="confirmPayAllInvoices(<?php echo $supplier['id']; ?>)">
                            <i class="fas fa-money-bill me-1"></i>Tüm Faturaları Öde
                        </button>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Fatura No</th>
                                    <th>Tutar</th>
                                    <th>Fatura Tarihi</th>
                                    <th>Vade Tarihi</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody id="invoices_table_<?php echo $supplier['id']; ?>"></tbody>
                        </table>
                        <div id="invoices_error_<?php echo $supplier['id']; ?>" class="error-message" style="display: none;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
// Bootstrap yükleme kontrolü
document.addEventListener('DOMContentLoaded', () => {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap JavaScript yüklenmedi!');
        alert('Sayfa düzgün yüklenemedi. Lütfen internet bağlantınızı kontrol edin veya sayfayı yenileyin.');
    }
});

// Tedarikçi ekleme onayı
function confirmPayment(supplierId) {
    const form = document.getElementById('paymentForm' + supplierId);
    if (!form) {
        console.error('Ödeme formu bulunamadı:', supplierId);
        alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
        return;
    }
    const paymentType = form.querySelector('#payment_type_' + supplierId).value;
    const invoiceId = form.querySelector('#invoice_id_' + supplierId).value;
    const amount = parseFloat(form.querySelector('#payment_amount_' + supplierId).value);
    const accountId = form.querySelector('#account_id_' + supplierId).value;
    const paymentDate = form.querySelector('#payment_date_' + supplierId).value;
    
    if (!paymentType) {
        alert('Lütfen bir ödeme türü seçin.');
        return;
    }
    if (paymentType === 'invoice' && !invoiceId) {
        alert('Lütfen bir fatura seçin.');
        return;
    }
    if (!amount || amount <= 0 || isNaN(amount)) {
        alert('Lütfen geçerli bir tutar girin.');
        return;
    }
    if (!accountId) {
        alert('Lütfen bir kasa hesabı seçin.');
        return;
    }
    if (!paymentDate) {
        alert('Lütfen bir ödeme tarihi seçin.');
        return;
    }
    if (new Date(paymentDate) > new Date()) {
        alert('Ödeme tarihi gelecekte olamaz.');
        return;
    }
    if (paymentType === 'balance') {
        const supplierBalance = <?php echo json_encode($supplier['balance'] ?? 0.00); ?>;
        if (amount > supplierBalance) {
            alert(`Girilen tutar (${amount.toFixed(2)} TRY) tedarikçi bakiyesinden büyük (${supplierBalance.toFixed(2)} TRY).`);
            return;
        }
    }
    if (!confirm(`Bu ${paymentType === 'invoice' ? 'fatura' : 'bakiye'} ödemesini eklemek istediğinizden emin misiniz? Tutar: ${amount.toFixed(2)} TRY`)) {
        return;
    }
    form.dataset.submitted = 'true';
    form.submit();
}

// Tedarikçi düzenleme onayı
function confirmEditSupplier(supplierId) {
    const form = document.getElementById('editSupplierForm' + supplierId);
    if (!form) {
        console.error('Tedarikçi düzenleme formu bulunamadı:', supplierId);
        alert('Form bulunamadı, lütfen sayfayı yenileyin.');
        return;
    }
    const name = form.querySelector('#edit_name_' + supplierId).value;
    const email = form.querySelector('#edit_email_' + supplierId).value;
    const phone = form.querySelector('#edit_phone_' + supplierId).value;
    if (!name) {
        alert('Lütfen bir tedarikçi adı girin.');
        return;
    }
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Lütfen geçerli bir e-posta adresi girin.');
        return;
    }
    if (phone && !/^\+?\d{10,15}$/.test(phone)) {
        alert('Lütfen geçerli bir telefon numarası girin.');
        return;
    }
    if (!confirm('Bu tedarikçiyi güncellemek istediğinizden emin misiniz?')) {
        return;
    }
    form.dataset.submitted = 'true';
    form.submit();
}

// Fatura ekleme onayı
function confirmAddInvoice(supplierId) {
    const form = document.getElementById('addInvoiceForm' + supplierId);
    if (!form) {
        console.error('Fatura ekleme formu bulunamadı:', supplierId);
        alert('Form bulunamadı, lütfen sayfayı yenileyin.');
        return;
    }
    const invoiceNumber = form.querySelector('#invoice_number_' + supplierId).value;
    const amount = parseFloat(form.querySelector('#amount_' + supplierId).value);
    const issueDate = form.querySelector('#issue_date_' + supplierId).value;
    const dueDate = form.querySelector('#due_date_' + supplierId).value;
    if (!invoiceNumber) {
        alert('Lütfen bir fatura numarası girin.');
        return;
    }
    if (!amount || amount <= 0) {
        alert('Lütfen geçerli bir tutar girin.');
        return;
    }
    if (!issueDate) {
        alert('Lütfen bir fatura tarihi seçin.');
        return;
    }
    if (!dueDate) {
        alert('Lütfen bir vade tarihi seçin.');
        return;
    }
    if (issueDate > dueDate) {
        alert('Vade tarihi fatura tarihinden önce olamaz.');
        return;
    }
    if (!confirm('Bu faturayı eklemek istediğinizden emin misiniz?')) {
        return;
    }
    form.dataset.submitted = 'true';
    form.submit();
}

// Ödeme ekleme onayı
function confirmPayment(supplierId) {
    const form = document.getElementById('paymentForm' + supplierId);
    if (!form) {
        console.error('Ödeme formu bulunamadı:', supplierId);
        alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
        return;
    }
    const paymentType = form.querySelector('#payment_type_' + supplierId).value;
    const invoiceId = form.querySelector('#invoice_id_' + supplierId).value;
    const amount = parseFloat(form.querySelector('#payment_amount_' + supplierId).value);
    const accountId = form.querySelector('#account_id_' + supplierId).value;
    const paymentDate = form.querySelector('#payment_date_' + supplierId).value;
    if (!paymentType) {
        alert('Lütfen bir ödeme türü seçin.');
        return;
    }
    if (paymentType === 'invoice' && !invoiceId) {
        alert('Lütfen bir fatura seçin.');
        return;
    }
    if (!amount || amount <= 0 || isNaN(amount)) {
        alert('Lütfen geçerli bir tutar girin.');
        return;
    }
    if (!accountId) {
        alert('Lütfen bir kasa hesabı seçin.');
        return;
    }
    if (!paymentDate) {
        alert('Lütfen bir ödeme tarihi seçin.');
        return;
    }
    if (new Date(paymentDate) > new Date()) {
        alert('Ödeme tarihi gelecekte olamaz.');
        return;
    }
    if (!confirm(`Bu ${paymentType === 'invoice' ? 'fatura' : 'bakiye'} ödemesini eklemek istediğinizden emin misiniz? Tutar: ${amount.toFixed(2)} TRY`)) {
        return;
    }
    form.dataset.submitted = 'true';
    form.submit();
}

// Toplu fatura ödeme onayı
function confirmPayAllInvoices(supplierId) {
    const form = document.getElementById('payAllInvoicesForm' + supplierId);
    if (!form) {
        console.error('Toplu ödeme formu bulunamadı:', supplierId);
        alert('Form bulunamadı, lütfen sayfayı yenileyin.');
        return;
    }
    const paymentDate = form.querySelector('#invoices_payment_date_' + supplierId).value;
    const accountId = form.querySelector('#invoices_account_id_' + supplierId).value;
    if (!paymentDate) {
        alert('Lütfen bir ödeme tarihi seçin.');
        return;
    }
    if (!accountId) {
        alert('Lütfen bir kasa hesabı seçin.');
        return;
    }
    if (new Date(paymentDate) > new Date()) {
        alert('Ödeme tarihi gelecekte olamaz.');
        return;
    }
    if (!confirm('Bu tedarikçiye ait tüm ödenmemiş faturaları ödemek istediğinizden emin misiniz?')) {
        return;
    }
    form.dataset.submitted = 'true';
    form.submit();
}

// Ödeme türüne göre fatura seçimi göster/gizle
function toggleInvoiceSelect(supplierId) {
    const paymentType = document.getElementById('payment_type_' + supplierId);
    const invoiceSelectContainer = document.getElementById('invoice_select_container_' + supplierId);
    const invoiceSelect = document.getElementById('invoice_id_' + supplierId);
    const balanceInfo = document.getElementById('balance_info_' + supplierId);
    const amountInput = document.getElementById('payment_amount_' + supplierId);
    const amountHelp = document.getElementById('amount_help_' + supplierId);
    
    if (paymentType && invoiceSelectContainer && invoiceSelect && balanceInfo && amountInput && amountHelp) {
        const isInvoice = paymentType.value === 'invoice';
        invoiceSelectContainer.style.display = isInvoice ? 'block' : 'none';
        balanceInfo.style.display = isInvoice ? 'none' : 'block';
        invoiceSelect.required = isInvoice;
        if (isInvoice) {
            amountInput.setAttribute('readonly', 'readonly');
            amountHelp.textContent = 'Tutar, seçilen faturaya göre otomatik doldurulacak.';
        } else {
            amountInput.removeAttribute('readonly');
            amountInput.value = '';
            amountHelp.textContent = 'Tedarikçi bakiyesinden düşülecek tutarı girin.';
        }
    } else {
        console.error('toggleInvoiceSelect: Gerekli elemanlar bulunamadı:', supplierId);
    }
}

// Modal verilerini doldurma ve fatura yükleme
document.addEventListener('DOMContentLoaded', () => {
    <?php foreach ($suppliers as $supplier): ?>
        const invoicesModal<?php echo $supplier['id']; ?> = document.getElementById('invoicesModal<?php echo $supplier['id']; ?>');
        const addPaymentModal<?php echo $supplier['id']; ?> = document.getElementById('addPaymentModal<?php echo $supplier['id']; ?>');

        // Düzenleme modalı
        document.getElementById('editSupplierModal<?php echo $supplier['id']; ?>').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const balance = button.getAttribute('data-balance');
            const contact_name = button.getAttribute('data-contact_name');
            const email = button.getAttribute('data-email');
            const phone = button.getAttribute('data-phone');
            const address = button.getAttribute('data-address');
            const contact = button.getAttribute('data-contact');

            const form = document.getElementById('editSupplierForm<?php echo $supplier['id']; ?>');
            if (form) {
                form.querySelector('input[name="id"]').value = id;
                form.querySelector('#edit_name_<?php echo $supplier['id']; ?>').value = name;
                form.querySelector('#edit_balance_<?php echo $supplier['id']; ?>').value = balance;
                form.querySelector('#edit_contact_name_<?php echo $supplier['id']; ?>').value = contact_name;
                form.querySelector('#edit_email_<?php echo $supplier['id']; ?>').value = email;
                form.querySelector('#edit_phone_<?php echo $supplier['id']; ?>').value = phone;
                form.querySelector('#edit_address_<?php echo $supplier['id']; ?>').value = address;
                form.querySelector('#edit_contact_<?php echo $supplier['id']; ?>').value = contact;
            }
        });

        // Fatura ekleme modalı
        document.getElementById('addInvoiceModal<?php echo $supplier['id']; ?>').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const supplier_id = button.getAttribute('data-supplier-id');
            const supplier_name = button.getAttribute('data-supplier-name');
            const form = document.getElementById('addInvoiceForm<?php echo $supplier['id']; ?>');
            if (form) {
                form.querySelector('input[name="supplier_id"]').value = supplier_id;
                document.getElementById('addInvoiceModalLabel<?php echo $supplier['id']; ?>').textContent = `Fatura Ekle - ${supplier_name}`;
            }
        });

        // Ödeme ekleme modalı
        document.getElementById('addPaymentModal<?php echo $supplier['id']; ?>').addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const supplier_id = button.getAttribute('data-supplier-id');
            const supplier_name = button.getAttribute('data-supplier-name');
            const form = document.getElementById('paymentForm<?php echo $supplier['id']; ?>');
            if (!form) {
                console.error('Ödeme formu bulunamadı:', <?php echo $supplier['id']; ?>);
                alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal<?php echo $supplier['id']; ?>'));
                modal.hide();
                return;
            }
            form.querySelector('input[name="supplier_id"]').value = supplier_id;
            document.getElementById('addPaymentModalLabel<?php echo $supplier['id']; ?>').textContent = `Ödeme Ekle - ${supplier_name}`;

            const invoiceSelect = document.getElementById('invoice_id_<?php echo $supplier['id']; ?>');
            const invoiceError = document.getElementById('invoice_error_<?php echo $supplier['id']; ?>');
            invoiceSelect.innerHTML = '<option value="">Fatura Seçin</option>';
            invoiceError.style.display = 'none';

            try {
                const response = await fetch(`get_invoices.php?supplier_id=${supplier_id}&t=${new Date().getTime()}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    throw new Error(`JSON parse hatası: ${e.message}`);
                }
                if (data.error) {
                    invoiceError.textContent = data.error;
                    invoiceError.style.display = 'block';
                    invoiceSelect.setAttribute('disabled', 'disabled');
                } else if (data.length === 0) {
                    invoiceError.textContent = 'Bu tedarikçi için ödenmemiş fatura bulunamadı.';
                    invoiceError.style.display = 'block';
                    invoiceSelect.setAttribute('disabled', 'disabled');
                } else {
                    const seenIds = new Set();
                    const uniqueInvoices = data.filter(invoice => {
                        if (seenIds.has(invoice.id)) {
                            console.warn('Çift fatura tespit edildi:', invoice.id);
                            return false;
                        }
                        seenIds.add(invoice.id);
                        return invoice.status === 'pending';
                    });
                    uniqueInvoices.forEach(invoice => {
                        invoiceSelect.innerHTML += `<option value="${invoice.id}" data-amount="${invoice.amount}">${invoice.invoice_number} - ${parseFloat(invoice.amount).toFixed(2)} TRY</option>`;
                    });
                    invoiceSelect.removeAttribute('disabled');
                }

                const existingListener = invoiceSelect.dataset.listener;
                if (!existingListener) {
                    invoiceSelect.addEventListener('change', function () {
                        const selectedOption = invoiceSelect.options[invoiceSelect.selectedIndex];
                        const amount = selectedOption.getAttribute('data-amount');
                        const amountInput = document.getElementById('payment_amount_<?php echo $supplier['id']; ?>');
                        if (amount) {
                            amountInput.value = parseFloat(amount).toFixed(2);
                            amountInput.setAttribute('readonly', 'readonly');
                        } else {
                            amountInput.value = '';
                            amountInput.removeAttribute('readonly');
                        }
                    });
                    invoiceSelect.dataset.listener = 'true';
                }
            } catch (error) {
                console.error('Fatura yükleme hatası (addPaymentModal):', error);
                invoiceError.textContent = 'Faturalar yüklenirken hata oluştu: ' + error.message;
                invoiceError.style.display = 'block';
                invoiceSelect.setAttribute('disabled', 'disabled');
            }
            toggleInvoiceSelect(<?php echo $supplier['id']; ?>);
        });

        // Faturalar modalı
        document.getElementById('invoicesModal<?php echo $supplier['id']; ?>').addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const supplier_id = button.getAttribute('data-supplier-id');
            const supplier_name = button.getAttribute('data-supplier-name');
            const form = document.getElementById('payAllInvoicesForm<?php echo $supplier['id']; ?>');
            if (!form) {
                console.error('Toplu ödeme formu bulunamadı:', <?php echo $supplier['id']; ?>);
                alert('Form bulunamadı, lütfen sayfayı yenileyin.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('invoicesModal<?php echo $supplier['id']; ?>'));
                modal.hide();
                return;
            }
            form.querySelector('input[name="supplier_id"]').value = supplier_id;
            document.getElementById('invoicesModalLabel<?php echo $supplier['id']; ?>').textContent = `Faturalar - ${supplier_name}`;

            const invoicesTable = document.getElementById('invoices_table_<?php echo $supplier['id']; ?>');
            const invoicesError = document.getElementById('invoices_error_<?php echo $supplier['id']; ?>');
            invoicesTable.innerHTML = '';
            invoicesError.style.display = 'none';

            try {
                const response = await fetch(`get_invoices.php?supplier_id=${supplier_id}&t=${new Date().getTime()}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    throw new Error(`JSON parse hatası: ${e.message}`);
                }
                if (data.error) {
                    invoicesError.textContent = data.error;
                    invoicesError.style.display = 'block';
                } else if (data.length === 0) {
                    invoicesError.textContent = 'Bu tedarikçi için fatura bulunamadı.';
                    invoicesError.style.display = 'block';
                } else {
                    const seenIds = new Set();
                    const uniqueInvoices = data.filter(invoice => {
                        if (seenIds.has(invoice.id)) {
                            console.warn('Çift fatura tespit edildi:', invoice.id);
                            return false;
                        }
                        seenIds.add(invoice.id);
                        return true;
                    });
                    uniqueInvoices.forEach(invoice => {
                        invoicesTable.innerHTML += `
                            <tr>
                                <td>${invoice.invoice_number}</td>
                                <td>${parseFloat(invoice.amount).toFixed(2)} TRY</td>
                                <td>${invoice.issue_date}</td>
                                <td>${invoice.due_date}</td>
                                <td>${invoice.status === 'pending' ? 'Ödenmemiş' : 'Ödendi'}</td>
                            </tr>
                        `;
                    });
                }
            } catch (error) {
                console.error('Fatura yükleme hatası (invoicesModal):', error);
                invoicesError.textContent = 'Faturalar yüklenirken hata oluştu: ' + error.message;
                invoicesError.style.display = 'block';
            }
        });
    <?php endforeach; ?>
});

document.getElementById('addPaymentModal<?php echo $supplier['id']; ?>').addEventListener('show.bs.modal', async function (event) {
    const button = event.relatedTarget;
    const supplier_id = button.getAttribute('data-supplier-id');
    const supplier_name = button.getAttribute('data-supplier-name');
    const form = document.getElementById('paymentForm<?php echo $supplier['id']; ?>');
    if (!form) {
        console.error('Ödeme formu bulunamadı:', <?php echo $supplier['id']; ?>);
        alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
        const modal = bootstrap.Modal.getInstance(document.getElementById('addPaymentModal<?php echo $supplier['id']; ?>'));
        modal.hide();
        return;
    }
    form.querySelector('input[name="supplier_id"]').value = supplier_id;
    document.getElementById('addPaymentModalLabel<?php echo $supplier['id']; ?>').textContent = `Ödeme Ekle - ${supplier_name}`;

    const invoiceSelect = document.getElementById('invoice_id_<?php echo $supplier['id']; ?>');
    const invoiceError = document.getElementById('invoice_error_<?php echo $supplier['id']; ?>');
    const balanceInfo = document.getElementById('balance_info_<?php echo $supplier['id']; ?>');
    invoiceSelect.innerHTML = '<option value="">Fatura Seçin</option>';
    invoiceError.style.display = 'none';
    balanceInfo.style.display = 'none';

    try {
        const response = await fetch(`get_invoices.php?supplier_id=${supplier_id}&t=${new Date().getTime()}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        let data;
        try {
            data = await response.json();
        } catch (e) {
            throw new Error(`JSON parse hatası: ${e.message}`);
        }
        if (data.error) {
            invoiceError.textContent = data.error;
            invoiceError.style.display = 'block';
            invoiceSelect.setAttribute('disabled', 'disabled');
        } else if (data.length === 0) {
            invoiceError.textContent = 'Bu tedarikçi için ödenmemiş fatura bulunamadı.';
            invoiceError.style.display = 'block';
            invoiceSelect.setAttribute('disabled', 'disabled');
        } else {
            const seenIds = new Set();
            const uniqueInvoices = data.filter(invoice => {
                if (seenIds.has(invoice.id)) {
                    console.warn('Çift fatura tespit edildi:', invoice.id);
                    return false;
                }
                seenIds.add(invoice.id);
                return invoice.status === 'pending';
            });
            uniqueInvoices.forEach(invoice => {
                invoiceSelect.innerHTML += `<option value="${invoice.id}" data-amount="${invoice.amount}">${invoice.invoice_number} - ${parseFloat(invoice.amount).toFixed(2)} TRY</option>`;
            });
            invoiceSelect.removeAttribute('disabled');
        }

        const existingListener = invoiceSelect.dataset.listener;
        if (!existingListener) {
            invoiceSelect.addEventListener('change', function () {
                const selectedOption = invoiceSelect.options[invoiceSelect.selectedIndex];
                const amount = selectedOption.getAttribute('data-amount');
                const amountInput = document.getElementById('payment_amount_<?php echo $supplier['id']; ?>');
                const amountHelp = document.getElementById('amount_help_<?php echo $supplier['id']; ?>');
                if (amount) {
                    amountInput.value = parseFloat(amount).toFixed(2);
                    amountInput.setAttribute('readonly', 'readonly');
                    amountHelp.textContent = 'Tutar, seçilen faturaya göre otomatik doldurulacak.';
                } else {
                    amountInput.value = '';
                    amountInput.removeAttribute('readonly');
                    amountHelp.textContent = 'Fatura seçin veya tutarı manuel girin.';
                }
            });
            invoiceSelect.dataset.listener = 'true';
        }
    } catch (error) {
        console.error('Fatura yükleme hatası (addPaymentModal):', error);
        invoiceError.textContent = 'Faturalar yüklenirken hata oluştu: ' + error.message;
        invoiceError.style.display = 'block';
        invoiceSelect.setAttribute('disabled', 'disabled');
    }
    toggleInvoiceSelect(<?php echo $supplier['id']; ?>);
});
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>