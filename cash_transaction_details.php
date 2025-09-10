<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: cash.php');
    exit;
}

$transaction_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT t.*, c.name as cash_name FROM cash_transactions t JOIN cash_accounts c ON t.cash_id = c.id WHERE t.id = ?");
$stmt->execute([$transaction_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header('Location: cash.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>İşlem Detayları</h2>
        <table class="table table-bordered">
            <tr>
                <th>Kasa</th>
                <td><?php echo htmlspecialchars($transaction['cash_name']); ?></td>
            </tr>
            <tr>
                <th>Tutar</th>
                <td><?php echo number_format($transaction['amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Para Birimi</th>
                <td><?php echo $transaction['currency']; ?></td>
            </tr>
            <tr>
                <th>Tutar (TRY)</th>
                <td><?php echo number_format($transaction['amount_try'], 2); ?></td>
            </tr>
            <tr>
                <th>Tür</th>
                <td><?php echo $transaction['type'] == 'in' ? 'Gelir' : 'Gider'; ?></td>
            </tr>
            <tr>
                <th>Açıklama</th>
                <td><?php echo htmlspecialchars($transaction['description'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>Tarih</th>
                <td><?php echo $transaction['created_at']; ?></td>
            </tr>
        </table>
        <a href="cash.php" class="btn btn-primary">Geri Dön</a>
        <a href="cash_transaction_edit.php?id=<?php echo $transaction['id']; ?>" class="btn btn-warning">Düzenle</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>