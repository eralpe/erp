<?php
session_start();
require_once 'config/db.php';
require_once 'functions/cash.php';
require_once 'functions/categories.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: cash.php');
    exit;
}

$transaction_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM cash_transactions WHERE id = ?");
$stmt->execute([$transaction_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header('Location: cash.php');
    exit;
}

$cash_accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);
$categories = getCategories($pdo);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_transaction'])) {
    try {
        $category_id = $_POST['category_id'];
        // Yeni kategori ekleme
        if ($_POST['new_category_name']) {
            $category_type = $_POST['type'] === 'in' ? 'income' : 'expense';
            $category_id = addCategory($pdo, $_POST['new_category_name'], $category_type);
        }
        updateCashTransaction($pdo, $transaction_id, $_POST['cash_id'], $_POST['amount'], $_POST['currency'], $_POST['type'], $category_id, $_POST['description']);
        $success = "İşlem başarıyla güncellendi.";
        header('Location: cash.php');
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
    <title>İşlem Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>İşlem Düzenle</h2>

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
                        <option value="<?php echo $account['id']; ?>" <?php echo $account['id'] == $transaction['cash_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($account['name']) . ' (' . $account['currency'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="amount" class="form-label">Tutar</label>
                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $transaction['amount']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="currency" class="form-label">Para Birimi</label>
                <select name="currency" class="form-select">
                    <option value="TRY" <?php echo $transaction['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY</option>
                    <option value="USD" <?php echo $transaction['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo $transaction['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">İşlem Türü</label>
                <select name="type" class="form-select" required onchange="updateCategoryOptions(this.value)">
                    <option value="in" <?php echo $transaction['type'] == 'in' ? 'selected' : ''; ?>>Gelir</option>
                    <option value="out" <?php echo $transaction['type'] == 'out' ? 'selected' : ''; ?>>Gider</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="category_id" class="form-label">Kategori</label>
                <select name="category_id" id="category_id" class="form-select">
                    <option value="">Kategori Seçin</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['type']; ?>" <?php echo $transaction['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="new_category_name" class="form-label">Yeni Kategori (Opsiyonel)</label>
                <input type="text" name="new_category_name" class="form-control" placeholder="Yeni kategori adı girin">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($transaction['description'] ?: ''); ?></textarea>
            </div>
            <button type="submit" name="edit_transaction" class="btn btn-primary">Kaydet</button>
            <a href="cash.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateCategoryOptions(type) {
            const select = document.getElementById('category_id');
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                const optionType = option.getAttribute('data-type');
                if (option.value === '') {
                    option.style.display = 'block';
                } else if (type === 'in' && (optionType === 'income' || optionType === 'both')) {
                    option.style.display = 'block';
                } else if (type === 'out' && (optionType === 'expense' || optionType === 'both')) {
                    option.style.display = 'block';
                } else {
                    option.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>