<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: suppliers.php');
    exit;
}

$supplier_id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
$stmt->execute([$supplier_id]);
$supplier = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$supplier) {
    header('Location: suppliers.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_supplier'])) {
    try {
        $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact = ?, address = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['contact'] ?: null, $_POST['address'] ?: null, $supplier_id]);
        $success = "Tedarikçi başarıyla güncellendi.";
        header('Location: suppliers.php');
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
    <title>Tedarikçi Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Tedarikçi Düzenle: <?php echo htmlspecialchars($supplier['name']); ?></h2>

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
                <label for="name" class="form-label">Ad</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="contact" class="form-label">İletişim</label>
                <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($supplier['contact'] ?: ''); ?>">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Adres</label>
                <textarea name="address" class="form-control"><?php echo htmlspecialchars($supplier['address'] ?: ''); ?></textarea>
            </div>
            <button type="submit" name="edit_supplier" class="btn btn-primary">Kaydet</button>
            <a href="suppliers.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>