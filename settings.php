<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

require_once 'config/db.php';

// Bildirim tercihlerini çekme
try {
    $stmt = $pdo->prepare("SELECT notification_type, is_enabled FROM user_notification_preferences WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $preferences = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $preferences[$row['notification_type']] = $row['is_enabled'];
    }
} catch (PDOException $e) {
    $error = "Tercihler yüklenemedi: " . $e->getMessage();
}

// Tercihleri güncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_preferences'])) {
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM user_notification_preferences WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        $types = ['stock', 'finance', 'chat', 'general'];
        foreach ($types as $type) {
            $is_enabled = isset($_POST['notification_types']) && in_array($type, $_POST['notification_types']) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO user_notification_preferences (user_id, notification_type, is_enabled) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $type, $is_enabled]);
        }
        $pdo->commit();
        $success = "Bildirim tercihleri güncellendi.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Tercihler güncellenemedi: " . $e->getMessage();
    }
}

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index'],
    ['title' => 'Ayarlar', 'url' => '']
];

$page_title = "Ayarlar";
$content = ob_start();
?>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo !isset($_GET['tab']) || $_GET['tab'] === 'general' ? 'active' : ''; ?>" href="settings?tab=general">Genel</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo isset($_GET['tab']) && $_GET['tab'] === 'notifications' ? 'active' : ''; ?>" href="settings?tab=notifications">Bildirim Tercihleri</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <?php if (isset($_GET['tab']) && $_GET['tab'] === 'notifications'): ?>
            <h5>Bildirim Tercihleri</h5>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="save_preferences" value="1">
                <div class="mb-3">
                    <label class="form-label">Almak İstediğiniz Bildirim Türleri:</label>
                    <?php
                    $types = [
                        'stock' => 'Stok Bildirimleri',
                        'finance' => 'Finans Bildirimleri',
                        'chat' => 'Mesaj Bildirimleri',
                        'general' => 'Genel Bildirimler'
                    ];
                    foreach ($types as $type => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="notification_types[]" value="<?php echo $type; ?>" 
                                   id="type_<?php echo $type; ?>" <?php echo isset($preferences[$type]) && $preferences[$type] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="type_<?php echo $type; ?>"><?php echo $label; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary">Kaydet</button>
            </form>
        <?php else: ?>
            <h5>Genel Ayarlar</h5>
            <p>Genel ayarlar burada yer alacak (ör. profil düzenleme, tema seçimi).</p>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>