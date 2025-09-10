<?php
session_start();
require_once 'config/db.php';
require_once 'functions/users.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Yalnızca yöneticilere erişim (isteğe bağlı)
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$user_id = (int)$_GET['id'];
$user = getUserById($pdo, $user_id);

if (!$user) {
    header('Location: users.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        try {
            $password = !empty($_POST['password']) ? $_POST['password'] : null;
            updateUser($pdo, $user_id, $_POST['name'], $_POST['email'], $password, $_POST['role'] ?? 'user');
            $success = "Kullanıcı başarıyla güncellendi.";
            header('Location: users.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif (isset($_POST['delete_user']) && $user_id != $_SESSION['user_id']) {
        try {
            deleteUser($pdo, $user_id);
            $success = "Kullanıcı başarıyla silindi.";
            header('Location: users.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Kullanıcı Düzenle: <?php echo htmlspecialchars($user['username']); ?></h2>

        <!-- Mesajlar -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Kullanıcı Bilgileri -->
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Kullanıcı Adı (Değiştirilemez)</label>
                <input type="text" name="username" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Ad</label>
                <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta</label>
                <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?: ''); ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Yeni Şifre (Boş bırakılırsa değişmez)</label>
                <input type="password" name="password" id="password" class="form-control">
            </div>
            <div class="mb-3">
                <label for="role" class="form-label">Rol</label>
                <select name="role" id="role" class="form-select">
                    <option value="user" <?php echo (isset($user['role']) && $user['role'] === 'user') ? 'selected' : ''; ?>>Kullanıcı</option>
                    <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') ? 'selected' : ''; ?>>Yönetici</option>
                </select>
            </div>
            <button type="submit" name="update_user" class="btn btn-primary">Güncelle</button>
            <a href="users.php" class="btn btn-secondary">Geri Dön</a>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>