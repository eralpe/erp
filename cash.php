<?php
session_start();
require_once 'config/db.php';
require_once 'functions/cash.php';

$title = 'Kasa Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => $title, 'url' => '']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_transaction'])) {
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $transaction_date = !empty($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
            addCashTransaction($pdo, $_POST['account_id'], $_POST['amount'], $_POST['currency'], $_POST['type'], $category_id, $_POST['description'], $transaction_date);
            $success = "Kasa işlemi eklendi.";
        } elseif (isset($_POST['add_transfer'])) {
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $transaction_date = !empty($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
            addTransfer($pdo, $_POST['from_account_id'], $_POST['to_account_id'], $_POST['amount'], $_POST['currency'], $_POST['description'], $category_id, $transaction_date);
            $success = "Transfer işlemi tamamlandı.";
        } elseif (isset($_POST['delete_transaction'])) {
            deleteCashTransaction($pdo, $_POST['transaction_id']);
            $success = "Kasa işlemi silindi.";
        } elseif (isset($_POST['update_transaction'])) {
            $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
            $transaction_date = !empty($_POST['transaction_date']) ? $_POST['transaction_date'] : date('Y-m-d');
            updateCashTransaction($pdo, $_POST['transaction_id'], $_POST['account_id'], $_POST['amount'], $_POST['currency'], $_POST['type'], $category_id, $_POST['description'], $transaction_date);
            $success = "Kasa işlemi güncellendi.";
        }
        if ($success) {
            header('Location: cash.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$transactions = $pdo->query("SELECT t.*, a.name as account_name, c.name as category_name 
                             FROM cash_transactions t 
                             JOIN cash_accounts a ON t.account_id = a.id 
                             LEFT JOIN categories c ON t.category_id = c.id 
                             ORDER BY t.created_at DESC")->fetchAll();

// Şablon içeriğini oluştur
ob_start();
?>

<!-- Hata ve Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Yeni İşlem Ekle Butonu -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">Yeni İşlem Ekle</button>

<!-- Para Transferi Butonu -->
<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransferModal">Transfer Yap</button>

<!-- Kasa Hesapları -->
<table class="table table-striped mt-3">
    <thead>
        <tr>
            <th>Kasa</th>
            <th>Para Birimi</th>
            <th>Bakiye (TRY)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($accounts as $account): ?>
            <tr>
                <td><?php echo htmlspecialchars($account['name']); ?></td>
                <td><?php echo htmlspecialchars($account['currency']); ?></td>
                <td><?php echo number_format($account['balance'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<!-- İşlem Geçmişi -->
<h3>İşlem Geçmişi</h3>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Kasa</th>
            <th>Tutar</th>
            <th>Para Birimi</th>
            <th>Tutar (TRY)</th>
            <th>Tür</th>
            <th>Kategori</th>
            <th>Açıklama</th>
            <th>İşlem Tarihi</th>
            <th>Tarih</th>
            <th>İşlemler</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?php echo htmlspecialchars($t['account_name']); ?></td>
                <td><?php echo number_format($t['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($t['currency']); ?></td>
                <td><?php echo number_format($t['amount_try'], 2); ?></td>
                <td><?php echo $t['type'] == 'income' ? 'Gelir' : ($t['type'] == 'expense' ? 'Gider' : ($t['type'] == 'transfer_out' ? 'Transfer Çıkış' : 'Transfer Giriş')); ?></td>
                <td><?php echo htmlspecialchars($t['category_name'] ?: '-'); ?></td>
                <td><?php echo htmlspecialchars($t['description'] ?: '-'); ?></td>
                <td><?php echo $t['transaction_date'] ?? '-'; ?></td>
                <td><?php echo $t['created_at']; ?></td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $t['id']; ?>">Düzenle</button>
                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $t['id']; ?>">Sil</button>
                </td>
            </tr>

            <!-- Düzenleme Modal -->
            <div class="modal fade" id="editModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?php echo $t['id']; ?>">Kasa İşlemi Düzenle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                <div class="mb-3">
                                    <label for="account_id_<?php echo $t['id']; ?>" class="form-label">Kasa Hesabı</label>
                                    <select name="account_id" class="form-select" required>
                                        <?php foreach ($accounts as $account): ?>
                                            <option value="<?php echo $account['id']; ?>" <?php echo $account['id'] == $t['account_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="amount_<?php echo $t['id']; ?>" class="form-label">Tutar</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo $t['amount']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="currency_<?php echo $t['id']; ?>" class="form-label">Para Birimi</label>
                                    <select name="currency" class="form-select">
                                        <option value="TRY" <?php echo $t['currency'] == 'TRY' ? 'selected' : ''; ?>>TRY</option>
                                        <option value="USD" <?php echo $t['currency'] == 'USD' ? 'selected' : ''; ?>>USD</option>
                                        <option value="EUR" <?php echo $t['currency'] == 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="type_<?php echo $t['id']; ?>" class="form-label">İşlem Türü</label>
                                    <select name="type" class="form-select" required>
                                        <option value="income" <?php echo $t['type'] == 'income' ? 'selected' : ''; ?>>Gelir</option>
                                        <option value="expense" <?php echo $t['type'] == 'expense' ? 'selected' : ''; ?>>Gider</option>
                                        <option value="transfer_out" <?php echo $t['type'] == 'transfer_out' ? 'selected' : ''; ?>>Transfer Çıkış</option>
                                        <option value="transfer_in" <?php echo $t['type'] == 'transfer_in' ? 'selected' : ''; ?>>Transfer Giriş</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="transaction_date_<?php echo $t['id']; ?>" class="form-label">İşlem Tarihi</label>
                                    <input type="date" name="transaction_date" class="form-control" value="<?php echo $t['transaction_date'] ?? date('Y-m-d'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="category_id_<?php echo $t['id']; ?>" class="form-label">Kategori</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">Kategori Seçin (Opsiyonel)</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $t['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name'] . ' (' . ($category['type'] == 'income' ? 'Gelir' : 'Gider') . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="description_<?php echo $t['id']; ?>" class="form-label">Açıklama</label>
                                    <textarea name="description" class="form-control"><?php echo htmlspecialchars($t['description'] ?: ''); ?></textarea>
                                </div>
                                <button type="submit" name="update_transaction" class="btn btn-primary">Güncelle</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Silme Modal -->
            <div class="modal fade" id="deleteModal<?php echo $t['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $t['id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel<?php echo $t['id']; ?>">Kasa İşlemi Sil</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Bu işlemi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.</p>
                            <form method="POST">
                                <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                <button type="submit" name="delete_transaction" class="btn btn-danger">Evet, Sil</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hayır</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </tbody>
</table>
<!-- Yeni İşlem Ekle Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionModalLabel">Yeni Kasa İşlemi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Kasa Hesabı</label>
                        <select name="account_id" class="form-select" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
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
                        <label for="type" class="form-label">İşlem Türü</label>
                        <select name="type" class="form-select" required>
                            <option value="income">Gelir</option>
                            <option value="expense">Gider</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transaction_date" class="form-label">İşlem Tarihi</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Kategori Seçin (Opsiyonel)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name'] . ' (' . ($category['type'] == 'income' ? 'Gelir' : 'Gider') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <button type="submit" name="add_transaction" class="btn btn-primary">Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Para Transferi Modal -->
<div class="modal fade" id="addTransferModal" tabindex="-1" aria-labelledby="addTransferModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransferModalLabel">Para Transferi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="from_account_id" class="form-label">Kaynak Kasa</label>
                        <select name="from_account_id" class="form-select" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="to_account_id" class="form-label">Hedef Kasa</label>
                        <select name="to_account_id" class="form-select" required>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
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
                        <label for="transaction_date" class="form-label">İşlem Tarihi</label>
                        <input type="date" name="transaction_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select name="category_id" class="form-select">
                            <option value="">Kategori Seçin (Opsiyonel)</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name'] . ' (' . ($category['type'] == 'income' ? 'Gelir' : 'Gider') . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <button type="submit" name="add_transfer" class="btn btn-primary">Transfer Yap</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>