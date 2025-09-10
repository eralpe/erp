<?php
// users.php
ob_start();
require_once 'config/db.php';
require_once 'functions/users.php';

$users = getUsers($pdo);
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    try {
        addUser($pdo, $_POST['username'], $_POST['name'], $_POST['email'], $_POST['password'], $_POST['role'] ?? 'user');
        $success = "Kullanıcı başarıyla eklendi.";
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$page_title = "Kullanıcı Yönetimi";

?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Kullanıcı Listesi</h5>
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="fas fa-plus me-2"></i>Yeni Kullanıcı Ekle
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Kullanıcı Adı</th>
                        <th>Ad</th>
                        <th>E-posta</th>
                        <th>Rol</th>
                        <th>Oluşturma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($user['email'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo isset($user['role']) ? ($user['role'] === 'admin' ? 'Yönetici' : 'Kullanıcı') : 'Kullanıcı'; ?></td>
                            <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="user_details.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Düzenle
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="user_details.php?id=<?php echo $user['id']; ?>" style="display:inline;">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Kullanıcıyı silmek istediğinizden emin misiniz?');">
                                            <i class="fas fa-trash me-1"></i>Sil
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Ekle Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel">Yeni Kullanıcı Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Ad</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta</label>
                        <input type="email" name="email" id="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Rol</label>
                        <select name="role" id="role" class="form-select">
                            <option value="user">Kullanıcı</option>
                            <option value="admin">Yönetici</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="index.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left me-1"></i>Geri Dön
    </a>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>