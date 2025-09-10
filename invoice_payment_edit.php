<?php
session_start();
require_once 'config/db.php';
require_once 'functions/suppliers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: suppliers.php');
    exit;
}

$payment_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT ip.*, i.supplier_id, i.invoice_number, s.name as supplier_name FROM invoice_payments ip JOIN invoices i ON ip.invoice_id = i.id JOIN suppliers s ON i.supplier_id = s.id WHERE ip.id = ?");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    header('Location: suppliers.php');
    exit;
}

$cash_accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_payment'])) {
    try {
        updateInvoicePayment($pdo, $payment_id, $_POST['cash_id'], $_POST['amount'], $_POST['currency'], $_POST['description']);
        $success = "Ödeme başarıyla güncellendi.";
        header('Location: supplier_details.php?id=' . $payment['supplier_id']);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Ödemesi Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Fatura Ödemesi Düzenle: <?php echo htmlspecialchars($payment['invoice_number']); ?> (<?php echo htmlspecialchars($payment['supplier_name']); ?>)</h2>

        <!-- Mesajlar -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Düzenleme Formu -->
        <form method="POST">
            <div class="mb-3">
                <label for="cash_id" class="form-label">Kasa</label>
                <select name="cash_id" class="form-select" required>
                    <?php foreach ($cash_accounts as $account): ?>
                        <option value="<?php echo $account['id']; ?>">
                            <?php echo htmlspecialchars($account['name']) . ' (' . $account['currency'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Tutar</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $payment['amount']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="currency" class="form-label">Para Birimi</label>
                <select name="currency" class="form-select">
                    <option value="TRY" <?php echo $payment['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY</option>
                    <option value="USD" <?php echo $payment['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo $payment['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($payment['description'] ?: ''); ?></textarea>
            </div>
            <button type="submit" name="edit_payment" class="btn btn-primary">Kaydet</button>
            <a href="supplier_details.php?id=<?php echo $payment['supplier_id']; ?>" class="btn btn-secondary">İptal</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>