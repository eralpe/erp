<?php
session_start();
require_once 'config/db.php';
$page_title = "Kategori Yönetimi";

ob_start();

// Hata ve başarı mesajları
$error = '';
$success = '';

// Kategori ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? 'both';

    if (empty($name)) {
        $error = "Kategori adı zorunludur.";
    } elseif (!in_array($type, ['income', 'expense', 'both'])) {
        $error = "Geçersiz kategori tipi.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, type) VALUES (?, ?)");
            $stmt->execute([$name, $type]);
            $success = "Kategori başarıyla eklendi.";
        } catch (PDOException $e) {
            $error = "Hata: " . $e->getMessage();
        }
    }
}

// Kategorileri çekme
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Kategoriler yüklenemedi: " . $e->getMessage();
}

?>

<div class="card">
    <div class="card-header">
        <h3>Kategoriler</h3>
    </div>
    <div class="card-body">
        <!-- Kategori Ekleme Formu -->
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="mb-3">
                <label for="name" class="form-label">Kategori Adı</label>
                <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
                <label for="type" class="form-label">Tip</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="income">Gelir</option>
                    <option value="expense">Gider</option>
                    <option value="both" selected>İkisi Birden</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </form>

        <!-- Kategori Listesi -->
        <table class="table table-striped mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Ad</th>
                    <th>Tip</th>
                    <th>Oluşturulma Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $row): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <?php
                            $types = ['income' => 'Gelir', 'expense' => 'Gider', 'both' => 'İkisi Birden'];
                            echo $types[$row['type']] ?? 'Bilinmeyen';
                            ?>
                        </td>
                        <td><?php echo date('d.m.Y H:i', strtotime($row['created_at'])); ?></td>
                        <td>
                            <a href="edit_category.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                            <a href="delete_category.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>