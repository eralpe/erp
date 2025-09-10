<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

require_once 'config/db.php';
require_once 'functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$results = ['customers' => [], 'suppliers' => [], 'inventory' => [], 'sales' => [], 'personnel' => []];
$error = null;

if ($query) {
    try {
        $search_term = '%' . $query . '%';
        $user_id = $_SESSION['user_id'];

        // Müşteriler
        $results['customers'] = cacheQuery($pdo, "
            SELECT id, name, email, phone 
            FROM customers 
            WHERE created_by = ? AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
            LIMIT 10
        ", [$user_id, $search_term, $search_term, $search_term], 'search_customers_' . $user_id . '_' . md5($query));

        // Tedarikçiler
        $results['suppliers'] = cacheQuery($pdo, "
            SELECT id, name, contact_name, email 
            FROM suppliers 
            WHERE created_by = ? AND (name LIKE ? OR contact_name LIKE ? OR email LIKE ?)
            LIMIT 10
        ", [$user_id, $search_term, $search_term, $search_term], 'search_suppliers_' . $user_id . '_' . md5($query));

        // Stok
        $results['inventory'] = cacheQuery($pdo, "
            SELECT id, product_name, quantity 
            FROM inventory 
            WHERE created_by = ? AND product_name LIKE ?
            LIMIT 10
        ", [$user_id, $search_term], 'search_inventory_' . $user_id . '_' . md5($query));

        // Satışlar
        $results['sales'] = cacheQuery($pdo, "
            SELECT s.id, s.order_number, c.name as customer_name, s.total_amount, s.currency 
            FROM sales s 
            JOIN customers c ON s.customer_id = c.id 
            WHERE s.created_by = ? AND (s.order_number LIKE ? OR c.name LIKE ?)
            LIMIT 10
        ", [$user_id, $search_term, $search_term], 'search_sales_' . $user_id . '_' . md5($query));

        // Personel
        $results['personnel'] = cacheQuery($pdo, "
            SELECT id, name, email, phone, position 
            FROM personnel 
            WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? OR position LIKE ?
            LIMIT 10
        ", [$search_term, $search_term, $search_term, $search_term], 'search_personnel_' . md5($query));
    } catch (PDOException $e) {
        $error = "Arama hatası: " . $e->getMessage();
    }
}

// AJAX isteği için JSON döndür
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['results' => $results, 'error' => isset($error) ? $error : null]);
    exit;
}

// Breadcrumbs
$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index'],
    ['title' => 'Arama', 'url' => '']
];

$page_title = "Global Arama";
$content = ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Global Arama</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="q" id="search-input" class="form-control" placeholder="Müşteri, tedarikçi, ürün, sipariş no veya personel ara..." value="<?php echo htmlspecialchars($query); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i>Ara</button>
            </div>
        </form>
        <div id="search-results">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif ($query): ?>
                <!-- Müşteriler -->
                <?php if (!empty($results['customers'])): ?>
                    <h6>Müşteriler</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($results['customers'] as $customer): ?>
                            <li class="list-group-item">
                                <a href="customers_details?id=<?php echo $customer['id']; ?>">
                                    <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($customer['name']); ?>
                                    <small class="text-muted"><?php echo $customer['email'] ?: $customer['phone'] ?: ''; ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- Tedarikçiler -->
                <?php if (!empty($results['suppliers'])): ?>
                    <h6>Tedarikçiler</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($results['suppliers'] as $supplier): ?>
                            <li class="list-group-item">
                                <a href="supplier_details?id=<?php echo $supplier['id']; ?>">
                                    <i class="fas fa-truck me-2"></i><?php echo htmlspecialchars($supplier['name']); ?>
                                    <small class="text-muted"><?php echo $supplier['contact_name'] ?: $supplier['email'] ?: ''; ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- Stok -->
                <?php if (!empty($results['inventory'])): ?>
                    <h6>Ürünler</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($results['inventory'] as $product): ?>
                            <li class="list-group-item">
                                <a href="stock?id=<?php echo $product['id']; ?>">
                                    <i class="fas fa-box me-2"></i><?php echo htmlspecialchars($product['product_name']); ?>
                                    <small class="text-muted">Miktar: <?php echo $product['quantity']; ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- Satışlar -->
                <?php if (!empty($results['sales'])): ?>
                    <h6>Satışlar</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($results['sales'] as $sale): ?>
                            <li class="list-group-item">
                                <a href="sales?id=<?php echo $sale['id']; ?>">
                                    <i class="fas fa-shopping-cart me-2"></i><?php echo htmlspecialchars($sale['order_number']); ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($sale['customer_name']); ?> - <?php echo number_format($sale['total_amount'], 2) . ' ' . $sale['currency']; ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <!-- Personel -->
                <?php if (!empty($results['personnel'])): ?>
                    <h6>Personel</h6>
                    <ul class="list-group mb-3">
                        <?php foreach ($results['personnel'] as $person): ?>
                            <li class="list-group-item">
                                <a href="personnel_details?id=<?php echo $person['id']; ?>">
                                    <i class="fas fa-user-tie me-2"></i><?php echo htmlspecialchars($person['name']); ?>
                                    <small class="text-muted"><?php echo htmlspecialchars($person['position'] ?: $person['email'] ?: $person['phone'] ?: ''); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <?php if (empty($results['customers']) && empty($results['suppliers']) && empty($results['inventory']) && empty($results['sales']) && empty($results['personnel'])): ?>
                    <div class="alert alert-info">Sonuç bulunamadı.</div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('search-input');
    let timeout = null;

    input.addEventListener('input', () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            fetch(`search?q=${encodeURIComponent(input.value)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                const resultsDiv = document.getElementById('search-results');
                if (data.error) {
                    resultsDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    return;
                }
                let html = '';
                if (data.results.customers.length) {
                    html += '<h6>Müşteriler</h6><ul class="list-group mb-3">';
                    data.results.customers.forEach(c => {
                        html += `
                            <li class="list-group-item">
                                <a href="customers_details?id=${c.id}">
                                    <i class="fas fa-user me-2"></i>${c.name}
                                    <small class="text-muted">${c.email || c.phone || ''}</small>
                                </a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                if (data.results.suppliers.length) {
                    html += '<h6>Tedarikçiler</h6><ul class="list-group mb-3">';
                    data.results.suppliers.forEach(s => {
                        html += `
                            <li class="list-group-item">
                                <a href="supplier_details?id=${s.id}">
                                    <i class="fas fa-truck me-2"></i>${s.name}
                                    <small class="text-muted">${s.contact_name || s.email || ''}</small>
                                </a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                if (data.results.inventory.length) {
                    html += '<h6>Ürünler</h6><ul class="list-group mb-3">';
                    data.results.inventory.forEach(p => {
                        html += `
                            <li class="list-group-item">
                                <a href="stock?id=${p.id}">
                                    <i class="fas fa-box me-2"></i>${p.product_name}
                                    <small class="text-muted">Miktar: ${p.quantity}</small>
                                </a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                if (data.results.sales.length) {
                    html += '<h6>Satışlar</h6><ul class="list-group mb-3">';
                    data.results.sales.forEach(s => {
                        html += `
                            <li class="list-group-item">
                                <a href="sales?id=${s.id}">
                                    <i class="fas fa-shopping-cart me-2"></i>${s.order_number}
                                    <small class="text-muted">${s.customer_name} - ${parseFloat(s.total_amount).toFixed(2)} ${s.currency}</small>
                                </a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                if (data.results.personnel.length) {
                    html += '<h6>Personel</h6><ul class="list-group mb-3">';
                    data.results.personnel.forEach(p => {
                        html += `
                            <li class="list-group-item">
                                <a href="personnel_details?id=${p.id}">
                                    <i class="fas fa-user-tie me-2"></i>${p.name}
                                    <small class="text-muted">${p.position || p.email || p.phone || ''}</small>
                                </a>
                            </li>
                        `;
                    });
                    html += '</ul>';
                }
                if (!html) {
                    html = '<div class="alert alert-info">Sonuç bulunamadı.</div>';
                }
                resultsDiv.innerHTML = html;
            })
            .catch(error => {
                document.getElementById('search-results').innerHTML = '<div class="alert alert-danger">Arama hatası: ' + error.message + '</div>';
            });
        }, 300);
    });
});
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>