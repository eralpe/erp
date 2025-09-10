<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Badge verileri için sorgular
require_once 'config/db.php';
$badge_data = [
    'low_stock' => 0,
    'overdue_invoices' => 0,
    'reminders' => 0,
    'category_count' => 0
];
$notification_count = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
} catch (PDOException $e) {
    // Hata loglama (üretimde aktif et)
}
try {
    $low_stock_items = $pdo->query("
        SELECT COUNT(*) as count 
        FROM inventory 
        WHERE stock_quantity < min_stock_level
    ")->fetch(PDO::FETCH_ASSOC);
    $badge_data['low_stock'] = $low_stock_items['count'];

    $overdue_invoices = $pdo->query("
        SELECT COUNT(*) as count 
        FROM sales_invoices 
        WHERE status = 'pending' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
        AND due_date >= CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);
    $badge_data['overdue_invoices'] = $overdue_invoices['count'];

    $reminders = $pdo->query("
        SELECT COUNT(*) as count 
        FROM reminders 
        WHERE due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
        AND due_date >= CURDATE()
    ")->fetch(PDO::FETCH_ASSOC);
    $badge_data['reminders'] = $reminders['count'];

    $category_count = $pdo->query("
        SELECT COUNT(*) as count 
        FROM categories
    ")->fetch(PDO::FETCH_ASSOC);
    $badge_data['category_count'] = $category_count['count'];
} catch (Exception $e) {
    // Hata durumunda badge'ler 0 gösterir
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Firma Yönetim Paneli'; ?></title>
    <link href="plugins/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="plugins/font-awesome/css/all.min.css" rel="stylesheet">
    <style>
        @font-face{
            font-Family: Roboto;
            src: url('plugins/fonts/roboto/Roboto-Regular.ttf');
        }
    </style>
    <script src="plugins/QRCode/js/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            transition: background-color 0.3s, color 0.3s;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 5px 10px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background-color: #007bff;
        }
        .sidebar .sub-menu {
            background-color: #495057;
            border-radius: 5px;
            margin: 5px 10px;
        }
        .sidebar .sub-menu .nav-link {
            padding-left: 40px;
            font-size: 0.9em;
        }
        .sidebar .sub-menu-toggle {
            cursor: pointer;
        }
        .sidebar .sub-menu-toggle .fa-chevron-down {
            transition: transform 0.3s;
        }
        .sidebar .sub-menu-toggle[aria-expanded="true"] .fa-chevron-down {
            transform: rotate(180deg);
        }
        .sidebar .badge {
            font-size: 0.75em;
            padding: 4px 8px;
            margin-left: 5px;
        }
        .navbar-brand {
            font-weight: 700;
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            z-index: 10;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
        }
        .theme-toggle {
            cursor: pointer;
        }
        body.dark-mode {
            background-color: #212529;
            color: #f8f9fa;
        }
        body.dark-mode .card, body.dark-mode .table {
            background-color: #343a40;
            color: #f8f9fa;
        }
        body.dark-mode .sidebar {
            background-color: #1a1d21;
        }
        body.dark-mode .sub-menu {
            background-color: #2c3034;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                z-index: 1000;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.collapsed {
                display: none;
            }
            .sidebar:not(.collapsed) {
                display: block;
            }
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        .notification-dropdown {
            max-height: 400px;
            overflow-y: auto;
        }
        .toast-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1055;
        }
        .breadcrumbs {
            margin-left: 00px;
            padding: 10px 20px;
            z-index: 1010;
        }
        body.dark-mode .breadcrumbs {
            background-color: #343a40;
            border-bottom: 1px solid #495057;
        }
        @media (max-width: 768px) {
            .breadcrumbs {
                margin-left: 0;
            }
        }
        /* Modal Z-Index Düzeltmesi (Tüm Modallar için) */
        .modal {
            z-index: 1070 !important;
        }
        .modal-backdrop.show {
            display:none;
        }
    </style>
</head>
<body class="<?php echo isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark' ? 'dark-mode' : ''; ?>">
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Firma ERP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <span class="me-3">Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Kullanıcı'); ?></span>
                <i class="fas fa-moon theme-toggle me-3" id="theme-toggle"></i>
                <div class="dropdown me-3">
                    <a class="nav-link" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu notification-dropdown">
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT id, type, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                            $stmt->execute([$_SESSION['user_id']]);
                            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (empty($notifications)) {
                                echo '<li class="dropdown-item">Yeni bildirim yok.</li>';
                            } else {
                                foreach ($notifications as $notification) {
                                    $icon = $notification['type'] === 'stock' ? 'fa-box' : ($notification['type'] === 'chat' ? 'fa-comment' : 'fa-info-circle');
                                    $bg_class = $notification['is_read'] ? '' : 'bg-light';
                                    echo "<li class='dropdown-item $bg_class'>";
                                    echo "<i class='fas $icon me-2'></i>";
                                    echo htmlspecialchars($notification['message']);
                                    echo "<small class='d-block text-muted'>" . date('d.m.Y H:i', strtotime($notification['created_at'])) . "</small>";
                                    echo "</li>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo '<li class="dropdown-item">Bildirimler yüklenemedi.</li>';
                        }
                        ?>
                    </ul>
                </div>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">Çıkış Yap</a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 sidebar collapse show" id="sidebarMenu">
                <div class="nav flex-column">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="fas fa-home me-2"></i> Ana Sayfa
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>" href="search.php">
                        <i class="fas fa-search me-2"></i> Arama
                    </a>
                    <!-- Yönetim Sub Menü -->
                    <div class="nav-item">
                        <a class="nav-link sub-menu-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'categories.php', 'personnel.php', 'users.php']) ? 'active' : ''; ?>" href="#managementSubMenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="fas fa-cogs me-2"></i> Yönetim <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse sub-menu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['inventory.php', 'categories.php', 'personnel.php', 'users.php']) ? 'show' : ''; ?>" id="managementSubMenu">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'inventory.php' ? 'active' : ''; ?>" href="inventory.php">
                                <i class="fas fa-warehouse me-2"></i> Stok Yönetimi
                                <?php if ($badge_data['low_stock'] > 0): ?>
                                    <span class="badge bg-danger"><?php echo $badge_data['low_stock']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                                <i class="fas fa-list-alt me-2"></i> Kategoriler
                                <?php if ($badge_data['category_count'] > 0): ?>
                                    <span class="badge bg-primary"><?php echo $badge_data['category_count']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'personnel.php' ? 'active' : ''; ?>" href="personnel.php">
                                <i class="fas fa-users me-2"></i> Personel Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : ''; ?>" href="users.php">
                                <i class="fas fa-user-cog me-2"></i> Kullanıcı Yönetimi
                            </a>
                        </div>
                    </div>
                    <!-- Finans Sub Menü -->
                    <div class="nav-item">
                        <a class="nav-link sub-menu-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['cash.php', 'cash_accounts.php', 'credits.php']) ? 'active' : ''; ?>" href="#financeSubMenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="fas fa-wallet me-2"></i> Finans <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse sub-menu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['cash.php', 'cash_accounts.php', 'credits.php']) ? 'show' : ''; ?>" id="financeSubMenu">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cash.php' ? 'active' : ''; ?>" href="cash.php">
                                <i class="fas fa-cash-register me-2"></i> Kasa Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'cash_accounts.php' ? 'active' : ''; ?>" href="cash_accounts.php">
                                <i class="fas fa-wallet me-2"></i> Kasa Hesapları
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'credits.php' ? 'active' : ''; ?>" href="credits.php">
                                <i class="fas fa-credit-card me-2"></i> Kredi Yönetimi
                            </a>
                        </div>
                    </div>
                    <!-- Satış ve Satın Alma Sub Menü -->
                    <div class="nav-item">
                        <a class="nav-link sub-menu-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['sales.php', 'purchase_orders.php', 'customers.php', 'suppliers.php']) ? 'active' : ''; ?>" href="#transactionsSubMenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="fas fa-exchange-alt me-2"></i> Satış ve Satın Alma <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse sub-menu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['sales.php', 'purchase_orders.php', 'customers.php', 'suppliers.php']) ? 'show' : ''; ?>" id="transactionsSubMenu">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'sales.php' ? 'active' : ''; ?>" href="sales.php">
                                <i class="fas fa-chart-line me-2"></i> Satış Yönetimi
                                <?php if ($badge_data['overdue_invoices'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $badge_data['overdue_invoices']; ?></span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'purchase_orders.php' ? 'active' : ''; ?>" href="purchase_orders.php">
                                <i class="fas fa-shopping-cart me-2"></i> Satın Alma Siparişleri
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                                <i class="fas fa-user-friends me-2"></i> Müşteri Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'suppliers.php' ? 'active' : ''; ?>" href="suppliers.php">
                                <i class="fas fa-truck me-2"></i> Tedarikçi Yönetimi
                            </a>
                        </div>
                    </div>
                    <!-- Depo ve Ürün Yönetimi Sub Menü -->
                    <div class="nav-item">
                        <a class="nav-link sub-menu-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['warehouse.php', 'products.php', 'production.php', 'recipes.php']) ? 'active' : ''; ?>" href="#warehouseSubMenu" data-bs-toggle="collapse" aria-expanded="false">
                            <i class="fas fa-warehouse me-2"></i> Depo ve Ürün Yönetimi <i class="fas fa-chevron-down ms-auto"></i>
                        </a>
                        <div class="collapse sub-menu <?php echo in_array(basename($_SERVER['PHP_SELF']), ['warehouse.php', 'products.php', 'production.php', 'recipes.php']) ? 'show' : ''; ?>" id="warehouseSubMenu">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'warehouse.php' ? 'active' : ''; ?>" href="warehouse.php">
                                <i class="fas fa-boxes me-2"></i> Depo Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>" href="products.php">
                                <i class="fas fa-box me-2"></i> Ürün Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'production.php' ? 'active' : ''; ?>" href="production.php">
                                <i class="fas fa-industry me-2"></i> Üretim Yönetimi
                            </a>
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'recipes.php' ? 'active' : ''; ?>" href="recipes.php">
                                <i class="fas fa-file-alt me-2"></i> Reçete Yönetimi
                            </a>
                        </div>
                    </div>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'documents.php' ? 'active' : ''; ?>" href="documents.php">
                        <i class="fas fa-file-alt me-2"></i> Doküman Yönetimi
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-chart-bar me-2"></i> Raporlar
                        <?php if ($badge_data['reminders'] > 0): ?>
                            <span class="badge bg-info"><?php echo $badge_data['reminders']; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                        <i class="fas fa-bell me-2"></i> Bildirimler
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-primary"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'chat.php' ? 'active' : ''; ?>" href="chat.php">
                        <i class="fas fa-comment me-2"></i> Mesajlar
                        <?php if ($notification_count > 0): ?>
                            <span class="badge bg-primary"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>" href="settings.php?tab=notifications">
                        <i class="fas fa-cog me-2"></i> Ayarlar
                    </a>
                </div>
            </nav>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <!-- Breadcrumbs -->
                <nav aria-label="breadcrumb" class="breadcrumbs">
                    <ol class="breadcrumb">
                        <?php
                        $breadcrumbs = isset($breadcrumbs) ? $breadcrumbs : [['title' => 'Ana Sayfa', 'url' => 'index.php']];
                        foreach ($breadcrumbs as $crumb) {
                            if ($crumb['url']) {
                                echo "<li class='breadcrumb-item'><a href='{$crumb['url']}'>" . htmlspecialchars($crumb['title']) . "</a></li>";
                            } else {
                                echo "<li class='breadcrumb-item active' aria-current='page'>" . htmlspecialchars($crumb['title']) . "</li>";
                            }
                        }
                        ?>
                    </ol>
                </nav>

                <!-- Toast Container -->
                <div class="toast-container">
                    <!-- Toast'lar JavaScript ile dinamik eklenecek -->
                </div>

                <?php if (isset($error) && $error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($success) && $success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php echo isset($content) ? $content : ''; ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="text-center py-3 bg-light" >
        <p>&copy; <?php echo date('Y'); ?> Firma ERP. Tüm hakları saklıdır.</p>
    </footer>

    <!-- JavaScript -->
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tema değiştirme
        const themeToggle = document.getElementById('theme-toggle');
        themeToggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
            fetch('set_theme.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'theme=' + theme
            });
            themeToggle.classList.toggle('fa-moon');
            themeToggle.classList.toggle('fa-sun');
        });

        // Sidebar toggle için ek kontrol
        const toggler = document.querySelector('.navbar-toggler');
        const sidebar = document.querySelector('#sidebarMenu');
        toggler.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        // Modal Açılış Kontrolü
        document.addEventListener('show.bs.modal', function (event) {
            const modal = event.target;
            modal.style.zIndex = 1070;
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.style.zIndex = 1060;
        });

        // Yeni bildirimleri kontrol et
        function checkNotifications() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.notifications && data.notifications.length > 0) {
                        data.notifications.forEach(notification => {
                            showToast(notification.message, notification.type);
                        });
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.textContent = data.count;
                            badge.style.display = data.count > 0 ? 'inline' : 'none';
                        }
                    }
                })
                .catch(error => console.error('Bildirim kontrol hatası:', error));
        }

        // Toast bildirimi göster
        function showToast(message, type) {
            const icon = type === 'stock' ? 'fa-box' : (type === 'chat' ? 'fa-comment' : 'fa-info-circle');
            const toastHtml = `
                <div class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="5000">
                    <div class="toast-header">
                        <i class="fas ${icon} me-2"></i>
                        <strong class="me-auto">Bildirim</strong>
                        <small>Şimdi</small>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">${message}</div>
                </div>
            `;
            const container = document.querySelector('.toast-container');
            container.insertAdjacentHTML('beforeend', toastHtml);
            const toast = container.lastElementChild;
            new bootstrap.Toast(toast).show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        // 10 saniyede bir bildirim kontrol et
        setInterval(checkNotifications, 10000);
        checkNotifications();
    </script>
</body>
</html>