<?php
session_start();
require_once 'config/db.php';
require_once 'functions/customers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: customers.php');
    exit;
}

$customer_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: customers.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_invoice'])) {
        try {
            addSalesInvoice($pdo, $customer_id, $_POST['invoice_number'], $_POST['amount'], $_POST['currency'], $_POST['issue_date'], $_POST['due_date'], $_POST['description']);
            $success = "Fatura başarıyla eklendi.";
            header('Location: customer_details.php?id=' . $customer_id);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['add_payment'])) {
        try {
            addSalesPayment($pdo, $_POST['invoice_id'], $_POST['cash_id'], $_POST['amount'], $_POST['currency'], $_POST['description']);
            $success = "Ödeme başarıyla eklendi.";
            header('Location: customer_details.php?id=' . $customer_id);
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$invoices = $pdo->query("SELECT si.*, SUM(sp.amount * er.rate) as paid_amount
                         FROM sales_invoices si
                         LEFT JOIN sales_payments sp ON si.id = sp.sales_invoice_id
                         LEFT JOIN exchange_rates er ON sp.currency = er.currency_code
                         WHERE si.customer_id = $customer_id
                         GROUP BY si.id
                         ORDER BY si.due_date ASC")->fetchAll(PDO::FETCH_ASSOC);
$cash_accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Müşteri Detayları: <?php echo htmlspecialchars($customer['name']); ?></h2>

        <!-- Mesajlar -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Müşteri Bilgileri -->
        <table class="table table-bordered">
            <tr><th>Ad</th><td><?php echo htmlspecialchars($customer['name']); ?></td></tr>
            <tr><th>Bakiye</th><td><?php echo number_format($customer['balance'], 2); ?> TRY</td></tr>
            <tr><th>İletişim</th><td><?php echo htmlspecialchars($customer['contact'] ?: '-'); ?></td></tr>
            <tr><th>Adres</th><td><?php echo htmlspecialchars($customer['address'] ?: '-'); ?></td></tr>
        </table>

        <!-- Satış Faturaları -->
        <h3>Satış Faturaları</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Tutar (TRY)</th>
                    <th>Ödenen</th>
                    <th>Kalan</th>
                    <th>Vade Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        <td><?php echo number_format($invoice['amount'], 2); ?></td>
                        <td><?php echo $invoice['currency']; ?></td>
                        <td><?php echo number_format($invoice['amount_try'], 2); ?></td>
                        <td><?php echo number_format($invoice['paid_amount'] ?: 0, 2); ?></td>
                        <td><?php echo number_format($invoice['amount_try'] - ($invoice['paid_amount'] ?: 0), 2); ?></td>
                        <td><?php echo $invoice['due_date']; ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $invoice['id']; ?>">Ödeme Ekle</button>
                        </td>
                    </tr>
                    <!-- Ödeme Ekle Modal -->
                    <div class="modal fade" id="paymentModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="paymentModalLabel<?php echo $invoice['id']; ?>">Fatura Ödemesi Ekle: <?php echo htmlspecialchars($invoice['invoice_number']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST">
                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                        <div class="mb-3">
                                            <label for="cash_id_<?php echo $invoice['id']; ?>" class="form-label">Kasa</label>
                                            <select name="cash_id" id="cash_id_<?php echo $invoice['id']; ?>" class="form-select" required>
                                                <?php foreach ($cash_accounts as $account): ?>
                                                    <option value="<?php echo $account['id']; ?>">
                                                        <?php echo htmlspecialchars($account['name']) . ' (' . $account['currency'] . ')'; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="amount_<?php echo $invoice['id']; ?>" class="form-label">Tutar</label>
                                            <input type="number" step="0.01" name="amount" id="amount_<?php echo $invoice['id']; ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="currency_<?php echo $invoice['id']; ?>" class="form-label">Para Birimi</label>
                                            <select name="currency" id="currency_<?php echo $invoice['id']; ?>" class="form-select">
                                                <option value="TRY">TRY</option>
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description_<?php echo $invoice['id']; ?>" class="form-label">Açıklama</label>
                                            <textarea name="description" id="description_<?php echo $invoice['id']; ?>" class="form-control"></textarea>
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

        <!-- Ödeme Geçmişi -->
        <h3>Ödeme Geçmişi</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Açıklama</th>
                    <th>Tarih</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $payments = $pdo->query("SELECT sp.*, si.invoice_number, ct.created_at as transaction_date
                                         FROM sales_payments sp
                                         JOIN sales_invoices si ON sp.sales_invoice_id = si.id
                                         JOIN cash_transactions ct ON sp.transaction_id = ct.id
                                         WHERE si.customer_id = $customer_id
                                         ORDER BY ct.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['invoice_number']); ?></td>
                        <td><?php echo number_format($payment['amount'], 2); ?></td>
                        <td><?php echo $payment['currency']; ?></td>
                        <td><?php echo htmlspecialchars($payment['description'] ?: '-'); ?></td>
                        <td><?php echo $payment['transaction_date']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Yeni Fatura Ekle -->
        <h3>Yeni Satış Faturası Ekle</h3>
        <form method="POST">
            <div class="mb-3">
                <label for="invoice_number" class="form-label">Fatura No</label>
                <input type="text" name="invoice_number" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Tutar</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
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
                <label for="issue_date" class="form-label">Fatura Tarihi</label>
                <input type="date" name="issue_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="due_date" class="form-label">Vade Tarihi</label>
                <input type="date" name="due_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <button type="submit" name="add_invoice" class="btn btn-primary">Ekle</button>
            <a href="customers.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>