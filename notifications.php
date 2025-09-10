<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

require_once 'config/db.php';

// Sekme ve filtreleme parametreleri
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$sort_priority = isset($_GET['sort_priority']) ? $_GET['sort_priority'] : 'none';

$query = "SELECT id, type, message, created_at, is_read, is_archived, priority 
          FROM notifications 
          WHERE user_id = ? AND is_archived = ?";
$params = [$_SESSION['user_id'], $tab === 'archived' ? 1 : 0];

if ($filter_type !== 'all') {
    $query .= " AND type = ?";
    $params[] = $filter_type;
}
if ($filter_status === 'read') {
    $query .= " AND is_read = 1";
} elseif ($filter_status === 'unread') {
    $query .= " AND is_read = 0";
}

if ($sort_priority !== 'none') {
    $query .= " ORDER BY FIELD(priority, 'high', 'medium', 'low') " . ($sort_priority === 'asc' ? 'ASC' : 'DESC') . ", created_at DESC";
} else {
    $query .= " ORDER BY created_at DESC";
}

$query .= " LIMIT 50";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Bildirimler yüklenemedi: " . $e->getMessage();
}

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index'],
    ['title' => 'Bildirimler', 'url' => '']
];

$page_title = "Bildirim Paneli";
$content = ob_start();
?>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'active' ? 'active' : ''; ?>" href="notifications?tab=active">Aktif Bildirimler</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $tab === 'archived' ? 'active' : ''; ?>" href="notifications?tab=archived">Arşivlenmiş Bildirimler</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5><?php echo $tab === 'archived' ? 'Arşivlenmiş Bildirimler' : 'Aktif Bildirimler'; ?></h5>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                <select name="type" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_type === 'all' ? 'selected' : ''; ?>>Tüm Türler</option>
                    <option value="stock" <?php echo $filter_type === 'stock' ? 'selected' : ''; ?>>Stok</option>
                    <option value="finance" <?php echo $filter_type === 'finance' ? 'selected' : ''; ?>>Finans</option>
                    <option value="chat" <?php echo $filter_type === 'chat' ? 'selected' : ''; ?>>Mesaj</option>
                    <option value="general" <?php echo $filter_type === 'general' ? 'selected' : ''; ?>>Genel</option>
                </select>
                <select name="status" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>Tüm Durumlar</option>
                    <option value="read" <?php echo $filter_status === 'read' ? 'selected' : ''; ?>>Okunmuş</option>
                    <option value="unread" <?php echo $filter_status === 'unread' ? 'selected' : ''; ?>>Okunmamış</option>
                </select>
                <select name="sort_priority" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="none" <?php echo $sort_priority === 'none' ? 'selected' : ''; ?>>Sıralama Yok</option>
                    <option value="asc" <?php echo $sort_priority === 'asc' ? 'selected' : ''; ?>>Önem: Düşük → Yüksek</option>
                    <option value="desc" <?php echo $sort_priority === 'desc' ? 'selected' : ''; ?>>Önem: Yüksek → Düşük</option>
                </select>
            </form>
        </div>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($notifications)): ?>
            <div class="alert alert-info">Gösterilecek bildirim yok.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>Tür</th>
                            <th>Mesaj</th>
                            <th>Önem</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th>İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $notification): ?>
                            <tr class="<?php echo $notification['is_read'] ? '' : 'table-light'; ?>">
                                <td><input type="checkbox" name="notification_ids[]" value="<?php echo $notification['id']; ?>"></td>
                                <td>
                                    <i class="fas <?php echo $notification['type'] === 'stock' ? 'fa-box' : ($notification['type'] === 'finance' ? 'fa-wallet' : ($notification['type'] === 'chat' ? 'fa-comment' : 'fa-info-circle')); ?> me-2"></i>
                                    <?php echo htmlspecialchars($notification['type']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($notification['message']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $notification['priority'] === 'high' ? 'danger' : ($notification['priority'] === 'medium' ? 'warning' : 'success'); ?>">
                                        <?php echo $notification['priority'] === 'high' ? 'Yüksek' : ($notification['priority'] === 'medium' ? 'Orta' : 'Düşük'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></td>
                                <td><?php echo $notification['is_read'] ? 'Okunmuş' : 'Okunmamış'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-<?php echo $notification['is_read'] ? 'primary' : 'success'; ?> mark-notification" data-id="<?php echo $notification['id']; ?>" data-action="<?php echo $notification['is_read'] ? 'unread' : 'read'; ?>">
                                        <?php echo $notification['is_read'] ? 'Okunmadı Yap' : 'Okundu Yap'; ?>
                                    </button>
                                    <button class="btn btn-sm btn-outline-<?php echo $tab === 'archived' ? 'info' : 'danger'; ?> <?php echo $tab === 'archived' ? 'unarchive-notification' : 'archive-notification'; ?>" data-id="<?php echo $notification['id']; ?>">
                                        <?php echo $tab === 'archived' ? 'Geri Yükle' : 'Arşivle'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <button class="btn btn-primary" id="bulk-mark-read">Seçilenleri Okundu Yap</button>
                <button class="btn btn-<?php echo $tab === 'archived' ? 'info' : 'danger'; ?>" id="bulk-<?php echo $tab === 'archived' ? 'unarchive' : 'archive'; ?>">
                    Seçilenleri <?php echo $tab === 'archived' ? 'Geri Yükle' : 'Arşivle'; ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('input[name="notification_ids[]"]');
    const markButtons = document.querySelectorAll('.mark-notification');
    const archiveButtons = document.querySelectorAll('.archive-notification, .unarchive-notification');
    const bulkMarkRead = document.getElementById('bulk-mark-read');
    const bulkArchive = document.getElementById('bulk-archive') || document.getElementById('bulk-unarchive');

    // Tümünü seç/çıkar
    selectAll.addEventListener('change', () => {
        checkboxes.forEach(cb => cb.checked = selectAll.checked);
    });

    // Tekil okundu/okunmadı işaretleme
    markButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const action = btn.dataset.action;
            fetch('mark_notification', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: `id=${id}&action=${action}`
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.error);
                }
            });
        });
    });

    // Tekil arşivleme/geri yükleme
    archiveButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const action = btn.classList.contains('archive-notification') ? 'archive' : 'unarchive';
            if (confirm(`Bu bildirimi ${action === 'archive' ? 'arşivlemek' : 'geri yüklemek'} istediğinizden emin misiniz?`)) {
                fetch('mark_notification', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                    },
                    body: `id=${btn.dataset.id}&action=${action}`
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Hata: ' + data.error);
                    }
                });
            }
        });
    });

    // Toplu okundu işaretleme
    bulkMarkRead.addEventListener('click', () => {
        const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
        if (selected.length === 0) {
            alert('Lütfen en az bir bildirim seçin.');
            return;
        }
        fetch('mark_notification', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/x-www-form-urlencoded',
                'CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
            },
            body: `ids=${selected.join(',')}&action=read`
        }).then(response => response.json()).then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Hata: ' + data.error);
            }
        });
    });

    // Toplu arşivleme/geri yükleme
    bulkArchive.addEventListener('click', () => {
        const action = bulkArchive.id === 'bulk-archive' ? 'archive' : 'unarchive';
        if (confirm(`Seçilen bildirimleri ${action === 'archive' ? 'arşivlemek' : 'geri yüklemek'} istediğinizden emin misiniz?`)) {
            const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length === 0) {
                alert('Lütfen en az bir bildirim seçin.');
                return;
            }
            fetch('mark_notification', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'CSRF-Token': '<?php echo $_SESSION['csrf_token']; ?>'
                },
                body: `ids=${selected.join(',')}&action=${action}`
            }).then(response => response.json()).then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Hata: ' + data.error);
                }
            });w
        }
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>w