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

$personnel_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
$stmt->execute([$personnel_id]);
$person = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$person) {
    header('Location: personnel.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_advance'])) {
    try {
        addSalaryAdvance($pdo, $personnel_id, $_POST['cash_id'], $_POST['amount'], $_POST['currency'], $_POST['description']);
        $success = "Maaş avansı başarıyla eklendi.";
        header('Location: personnel_details.php?id=' . $personnel_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$cash_accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll(PDO::FETCH_ASSOC);
$advances = $pdo->query("SELECT sa.*, ct.created_at as transaction_date FROM salary_advances sa JOIN cash_transactions ct ON sa.transaction_id = ct.id WHERE sa.personnel_id = $personnel_id ORDER BY ct.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Detayları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Personel Detayları: <?php echo htmlspecialchars($person['name']); ?></h2>

        <!-- Mesajlar -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Personel Bilgileri -->
        <table class="table table-bordered">
            <tr>
                <th>Ad Soyad</th>
                <td><?php echo htmlspecialchars($person['name']); ?></td>
            </tr>
            <tr>
                <th>Pozisyon</th>
                <td><?php echo htmlspecialchars($person['position'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>Maaş</th>
                <td><?php echo isset($person['salary']) ? number_format($person['salary'], 2) : '-'; ?></td>
            </tr>
            <tr>
                <th>E-posta</th>
                <td><?php echo htmlspecialchars($person['email'] ?: '-'); ?></td>
            </tr>
            <tr>
                <th>Telefon</th>
                <td><?php echo htmlspecialchars($person['phone'] ?: '-'); ?></td>
            </tr>
        </table>

        <!-- Maaş Avansları -->
        <h3>Maaş Avansları</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Açıklama</th>
                    <th>Tarih</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($advances as $advance): ?>
                    <tr>
                        <td><?php echo number_format($advance['amount'], 2); ?></td>
                        <td><?php echo $advance['currency']; ?></td>
                        <td><?php echo htmlspecialchars($advance['description'] ?: '-'); ?></td>
                        <td><?php echo $advance['transaction_date']; ?></td>
                        <td>
                            <a href="salary_advance_edit.php?id=<?php echo $advance['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                            <form method="POST" action="salary_advance_delete.php" style="display:inline;">
                                <input type="hidden" name="advance_id" value="<?php echo $advance['id']; ?>">
                                <button type="submit" name="delete_advance" class="btn btn-sm btn-danger" onclick="return confirm('Bu maaş avansını silmek istediğinizden emin misiniz?');">Sil</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Yeni Maaş Avansı Ekle -->
        <h3>Yeni Maaş Avansı Ekle</h3>
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
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <button type="submit" name="add_advance" class="btn btn-primary">Ekle</button>
            <a href="personnel.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>