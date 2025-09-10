<?php
session_start();
require_once 'config/db.php';
require_once 'functions/invoices.php';

$title = 'Fatura Yönetimi';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_payment'])) {
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            addInvoicePayment($pdo, $_POST['invoice_id'], $_POST['account_id'], $_POST['amount'], $_POST['currency'], $_POST['payment_date'], $description);
            $success = "Ödeme eklendi.";
            header('Location: invoices.php');
            exit;
        } elseif (isset($_POST['bulk_payment'])) {
            $invoice_ids = $_POST['invoice_ids'] ?? [];
            if (empty($invoice_ids)) {
                throw new Exception("Lütfen en az bir fatura seçin.");
            }
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            addBulkPayment($pdo, $invoice_ids, $_POST['account_id'], $_POST['payment_date'], $description);
            $success = "Toplu ödeme tamamlandı.";
            header('Location: invoices.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fatura ve ödeme bilgilerini al
$invoices = $pdo->query("SELECT i.id, i.invoice_number, i.amount AS invoice_amount, i.currency AS invoice_currency, i.issue_date, i.due_date, i.status, s.name AS supplier_name, 
                         (SELECT SUM(ip.amount_try) FROM invoice_payments ip WHERE ip.invoice_id = i.id) AS total_paid_try
                         FROM invoices i
                         JOIN suppliers s ON i.supplier_id = s.id
                         ORDER BY i.due_date ASC")->fetchAll();

$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll();

// Şablon içeriğini oluştur
ob_start();
?>

<h2>Fatura Yönetimi</h2>

<!-- Hata ve Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Fatura Listesi -->
<h3>Faturalar</h3>
<form method="POST">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Seç</th>
                <th>Fatura No</th>
                <th>Tedarikçi</th>
                <th>Tutar</th>
                <th>Para Birimi</th>
                <th>Ödenen (TRY)</th>
                <th>Durum</th>
                <th>Vade Tarihi</th>
                <th>İşlemler</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td><input type="checkbox" name="invoice_ids[]" value="<?php echo $invoice['id']; ?>"></td>
                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                    <td><?php echo htmlspecialchars($invoice['supplier_name']); ?></td>
                    <td><?php echo number_format($invoice['invoice_amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($invoice['invoice_currency']); ?></td>
                    <td><?php echo number_format($invoice['total_paid_try'] ?? 0, 2); ?></td>
                    <td><?php echo $invoice['status'] == 'pending' ? 'Ödenmedi' : ($invoice['status'] == 'paid' ? 'Ödendi' : 'Kısmen Ödendi'); ?></td>
                    <td><?php echo $invoice['due_date']; ?></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $invoice['id']; ?>">Ödeme Ekle</button>
                    </td>
                </tr>

                <!-- Ödeme Ekle Modal -->
                <div class="modal fade" id="paymentModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="paymentModalLabel<?php echo $invoice['id']; ?>">Fatura Ödemesi Ekle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                    <div class="mb-3">
                                        <label for="account_id_<?php echo $invoice['id']; ?>" class="form-label">Kasa Hesabı</label>
                                        <select name="account_id" class="form-select" required>
                                            <?php foreach ($accounts as $account): ?>
                                                <option value="<?php echo $account['id']; ?>">
                                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount_<?php echo $invoice['id']; ?>" class="form-label">Tutar</label>
                                        <input type="number" step="0.01" name="amount" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="currency_<?php echo $invoice['id']; ?>" class="form-label">Para Birimi</label>
                                        <select name="currency" class="form-select">
                                            <option value="TRY">TRY</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_date_<?php echo $invoice['id']; ?>" class="form-label">Ödeme Tarihi</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_<?php echo $invoice['id']; ?>" class="form-label">Açıklama</label>
                                        <textarea name="description" class="form-control"></textarea>
                                    </div>
                                    <button type="submit" name="add_payment" class="btn btn-primary">Ödeme Ekle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Toplu Ödeme -->
    <h3>Toplu Ödeme</h3>
    <div class="mb-3">
        <label for="account_id_bulk" class="form-label">Kasa Hesabı</label>
        <select name="account_id" class="form-select" required>
            <?php foreach ($accounts as $account): ?>
                <option value="<?php echo $account['id']; ?>">
                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="payment_date_bulk" class="form-label">Ödeme Tarihi</label>
        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
    </div>
    <div class="mb-3">
        <label for="description_bulk" class="form-label">Açıklama</label>
        <textarea name="description" class="form-control"></textarea>
    </div>
    <button type="submit" name="bulk_payment" class="btn btn-primary">Toplu Ödeme Yap</button>
</form>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>