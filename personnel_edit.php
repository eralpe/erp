<?php
session_start();
require_once 'config/db.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_personnel'])) {
    try {
        $stmt = $pdo->prepare("UPDATE personnel SET name = ?, position = ?, salary = ?, email = ?, phone = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['position'] ?: null,
            $_POST['salary'] ?: null,
            $_POST['email'] ?: null,
            $_POST['phone'] ?: null,
            $personnel_id
        ]);
        $success = "Personel başarıyla güncellendi.";
        header('Location: personnel.php');
        exit;
    } catch (Exception $e) {
        $error = "Personel güncellenirken hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personel Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Personel Düzenle</h2>

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
                <label for="name" class="form-label">Ad Soyad</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($person['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="position" class="form-label">Pozisyon</label>
                <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($person['position'] ?: ''); ?>">
            </div>
            <div class="mb-3">
                <label for="salary" class="form-label">Maaş</label>
                <input type="number" step="0.01" name="salary" class="form-control" value="<?php echo isset($person['salary']) ? $person['salary'] : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($person['email'] ?: ''); ?>">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telefon</label>
                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($person['phone'] ?: ''); ?>">
            </div>
            <button type="submit" name="edit_personnel" class="btn btn-primary">Kaydet</button>
            <a href="personnel.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>