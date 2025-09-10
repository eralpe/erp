<?php
session_start();
require_once 'config/db.php';
require_once 'functions/salary_advances.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: personnel.php');
    exit;
}

$advance_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT sa.*, p.name as personnel_name FROM salary_advances sa JOIN personnel p ON sa.personnel_id = p.id WHERE sa.id = ?");
$stmt->execute([$advance_id]);
$advance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$advance) {
    header('Location: personnel.php');
    exit;
}

$cash_accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_advance'])) {
    try {
        updateSalaryAdvance($pdo, $advance_id, $_POST['cash_id'], $_POST['amount'], $_POST['currency'], $_POST['description']);
        $success = "Maaş avansı başarıyla güncellendi.";
        header('Location: personnel_details.php?id=' . $advance['personnel_id']);
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
    <title>Maaş Avansı Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Maaş Avansı Düzenle: <?php echo htmlspecialchars($advance['personnel_name']); ?></h2>

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
                <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $advance['amount']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="currency" class="form-label">Para Birimi</label>
                <select name="currency" class="form-select">
                    <option value="TRY" <?php echo $advance['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY</option>
                    <option value="USD" <?php echo $advance['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                    <option value="EUR" <?php echo $advance['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" class="form-control"><?php echo htmlspecialchars($advance['description'] ?: ''); ?></textarea>
            </div>
            <button type="submit" name="edit_advance" class="btn btn-primary">Kaydet</button>
            <a href="personnel_details.php?id=<?php echo $advance['personnel_id']; ?>" class="btn btn-secondary">İptal</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>