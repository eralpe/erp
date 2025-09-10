<?php
session_start();
require_once 'config/db.php';
require_once 'functions/cash.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = "Kasa Hesapları Yönetimi";

$cash_accounts = [];
$error = null;
$success = null;
$edit_account = null;

// Düzenleme için kasa ID'sini al
$edit_account_id = isset($_GET['edit_account_id']) && is_numeric($_GET['edit_account_id']) ? (int)$_GET['edit_account_id'] : null;

try {
    $cash_accounts = getCashAccounts($pdo);
    
    // Düzenleme için kasa bilgisi
    if ($edit_account_id) {
        $edit_account = getCashAccountById($pdo, $edit_account_id);
        if (!$edit_account) {
            $error = "Düzenlenecek kasa bulunamadı.";
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Yeni kasa ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    try {
        addCashAccount(
            $pdo,
            $_POST['account_name'],
            $_POST['description']
        );
        $success = "Kasa başarıyla eklendi.";
        header('Location: cash_accounts.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Kasa düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    try {
        updateCashAccount(
            $pdo,
            $_POST['account_id'],
            $_POST['account_name'],
            $_POST['description']
        );
        $success = "Kasa başarıyla güncellendi.";
        header('Location: cash_accounts.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

ob_start();
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="fas fa-wallet me-2"></i> Kasa Hesapları</h3>
    </div>
    <div class="card-body">
        <?php if (empty($cash_accounts)): ?>
            <p class="text-muted">Henüz kasa tanımlanmamış.</p>
        <?php else: ?>
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kasa Adı</th>
                        <th>Açıklama</th>
                        <th>Oluşturma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cash_accounts as $account): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($account['name'] ?? 'Bilinmeyen', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($account['description'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($account['created_at'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="cash_accounts.php?edit_account_id=<?php echo htmlspecialchars($account['id'], ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit me-1"></i> Düzenle
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0"><i class="fas fa-plus-circle me-2"></i> <?php echo $edit_account ? 'Kasa Düzenle' : 'Yeni Kasa Ekle'; ?></h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <?php if ($edit_account): ?>
                <input type="hidden" name="account_id" value="<?php echo htmlspecialchars($edit_account['id'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="account_name" class="form-label">Kasa Adı</label>
                <input type="text" name="account_name" id="account_name" class="form-control" value="<?php echo $edit_account ? htmlspecialchars($edit_account['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Açıklama</label>
                <textarea name="description" id="description" class="form-control"><?php echo $edit_account ? htmlspecialchars($edit_account['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
            </div>
            <button type="submit" name="<?php echo $edit_account ? 'update_account' : 'add_account'; ?>" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> <?php echo $edit_account ? 'Güncelle' : 'Ekle'; ?>
            </button>
            <a href="cash_accounts.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Geri Dön
            </a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>