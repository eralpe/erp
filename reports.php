<?php
session_start();
require_once 'config/db.php';
require_once 'functions/auth.php';

checkRoleAccess(['muhasebeci', 'yönetici', 'admin']);

require_once 'vendor/autoload.php'; // TCPDF için
$title = 'Raporlar';
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['personnel_token']) {
            throw new Exception("Geçersiz CSRF token.");
        }

        $report_type = $_POST['report_type'] ?? 'all';
        $start_date = $_POST['start_date'] ?? date('Y-m-01', strtotime('-1 month'));
        $end_date = $_POST['end_date'] ?? date('Y-m-d');
        $category = $_POST['category'] ?? 'all';
        $filter_id = $_POST['filter_id'] ?? null;

        if ($start_date > $end_date) {
            throw new Exception("Başlangıç tarihi bitiş tarihinden büyük olamaz.");
        }

        $where_clause = $filter_id ? "WHERE id = ?" : "";
        $params = $filter_id ? [$filter_id] : [];
        if ($start_date && $end_date) {
            $where_clause .= $filter_id ? " AND created_at BETWEEN ? AND ?" : "WHERE created_at BETWEEN ? AND ?";
            $params = array_merge($params, [$start_date, $end_date]);
        }

        $data = [];
        switch ($category) {
            case 'personnel':
                $stmt = $pdo->prepare("SELECT p.*, COALESCE(SUM(pa.amount), 0) as total_advances, COALESCE(SUM(po.overtime_earning), 0) as total_overtime
                                       FROM personnel p
                                       LEFT JOIN personnel_advances pa ON p.id = pa.personnel_id
                                       LEFT JOIN personnel_overtime po ON p.id = po.personnel_id
                                       $where_clause
                                       GROUP BY p.id");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'cash_accounts':
                $stmt = $pdo->prepare("SELECT * FROM cash_accounts ca $where_clause ORDER BY ca.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'cash_transactions':
                $stmt = $pdo->prepare("SELECT ct.*, ca.name as cash_name FROM cash_transactions ct LEFT JOIN cash_accounts ca ON ct.cash_id = ca.id $where_clause ORDER BY ct.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'credits':
                $stmt = $pdo->prepare("SELECT * FROM credits c $where_clause ORDER BY c.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'credit_installments':
                $stmt = $pdo->prepare("SELECT ci.*, c.bank_name FROM credit_installments ci JOIN credits c ON ci.credit_id = c.id $where_clause ORDER BY ci.due_date DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'customers':
                $stmt = $pdo->prepare("SELECT * FROM customers c $where_clause ORDER BY c.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'customer_transactions':
                $stmt = $pdo->prepare("SELECT ct.*, c.name as customer_name FROM customer_transactions ct JOIN customers c ON ct.customer_id = c.id $where_clause ORDER BY ct.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'products':
                $stmt = $pdo->prepare("SELECT * FROM products p $where_clause ORDER BY p.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'sales':
                $stmt = $pdo->prepare("SELECT s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id $where_clause ORDER BY s.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'sales_invoices':
                $stmt = $pdo->prepare("SELECT si.*, c.name as customer_name FROM sales_invoices si JOIN customers c ON si.customer_id = c.id $where_clause ORDER BY si.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'suppliers':
                $stmt = $pdo->prepare("SELECT * FROM suppliers s $where_clause ORDER BY s.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'warehouses':
                $stmt = $pdo->prepare("SELECT * FROM warehouses w $where_clause ORDER BY w.created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
            case 'all':
            default:
                $stmt = $pdo->prepare("SELECT 'personnel' as category, p.*, COALESCE(SUM(pa.amount), 0) as total_advances, COALESCE(SUM(po.overtime_earning), 0) as total_overtime
                                       FROM personnel p
                                       LEFT JOIN personnel_advances pa ON p.id = pa.personnel_id
                                       LEFT JOIN personnel_overtime po ON p.id = po.personnel_id
                                       " . ($where_clause ? str_replace('created_at', 'p.created_at', $where_clause) : "") . "
                                       GROUP BY p.id
                                       UNION
                                       SELECT 'cash_accounts' as category, * FROM cash_accounts ca
                                       " . ($where_clause ? str_replace('created_at', 'ca.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'cash_transactions' as category, ct.*, ca.name as cash_name FROM cash_transactions ct LEFT JOIN cash_accounts ca ON ct.cash_id = ca.id
                                       " . ($where_clause ? str_replace('created_at', 'ct.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'credits' as category, * FROM credits c
                                       " . ($where_clause ? str_replace('created_at', 'c.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'credit_installments' as category, ci.*, c.bank_name FROM credit_installments ci JOIN credits c ON ci.credit_id = c.id
                                       " . ($where_clause ? str_replace('created_at', 'ci.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'customers' as category, * FROM customers c
                                       " . ($where_clause ? str_replace('created_at', 'c.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'customer_transactions' as category, ct.*, c.name as customer_name FROM customer_transactions ct JOIN customers c ON ct.customer_id = c.id
                                       " . ($where_clause ? str_replace('created_at', 'ct.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'products' as category, * FROM products p
                                       " . ($where_clause ? str_replace('created_at', 'p.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'sales' as category, s.*, c.name as customer_name FROM sales s JOIN customers c ON s.customer_id = c.id
                                       " . ($where_clause ? str_replace('created_at', 's.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'sales_invoices' as category, si.*, c.name as customer_name FROM sales_invoices si JOIN customers c ON si.customer_id = c.id
                                       " . ($where_clause ? str_replace('created_at', 'si.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'suppliers' as category, * FROM suppliers s
                                       " . ($where_clause ? str_replace('created_at', 's.created_at', $where_clause) : "") . "
                                       UNION
                                       SELECT 'warehouses' as category, * FROM warehouses w
                                       " . ($where_clause ? str_replace('created_at', 'w.created_at', $where_clause) : "") . "
                                       ORDER BY created_at DESC");
                $stmt->execute($params);
                $data = $stmt->fetchAll();
                break;
        }

        if (empty($data)) {
            throw new Exception("Rapor için veri bulunamadı.");
        }

        $filename = "report_{$category}_" . date('Ymd_His');
        if ($_POST['export_type'] === 'pdf') {
            $pdf = new TCPDF();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('Your Company');
            $pdf->SetTitle(ucfirst($category) . ' Raporu');
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', '', 10);
            $html = '<h1>' . ucfirst($category) . ' Raporu</h1><p>Tarih Aralığı: ' . $start_date . ' - ' . $end_date . '</p><table border="1" cellpadding="5">';
            if ($category === 'all') {
                $html .= '<thead><tr><th>Kategori</th><th>ID</th><th>Ad</th><th>Tarih</th><th>Açıklama</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= "<tr><td>{$row['category']}</td><td>{$row['id']}</td><td>" . htmlspecialchars($row['name'] ?? '-') . "</td><td>{$row['created_at']}</td><td>" . htmlspecialchars($row['description'] ?? '-') . "</td></tr>";
                }
            } else {
                $html .= '<thead><tr><th>ID</th><th>Ad</th><th>Tarih</th><th>Açıklama</th></tr></thead><tbody>';
                foreach ($data as $row) {
                    $html .= "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['name'] ?? '-') . "</td><td>{$row['created_at']}</td><td>" . htmlspecialchars($row['description'] ?? '-') . "</td></tr>";
                }
            }
            $html .= '</tbody></table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($filename . '.pdf', 'D');
            exit;
        } elseif ($_POST['export_type'] === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            if ($category === 'all') {
                fputcsv($output, ['Kategori', 'ID', 'Ad', 'Tarih', 'Açıklama']);
                foreach ($data as $row) {
                    fputcsv($output, [$row['category'], $row['id'], $row['name'] ?? '-', $row['created_at'], $row['description'] ?? '-']);
                }
            } else {
                fputcsv($output, ['ID', 'Ad', 'Tarih', 'Açıklama']);
                foreach ($data as $row) {
                    fputcsv($output, [$row['id'], $row['name'] ?? '-', $row['created_at'], $row['description'] ?? '-']);
                }
            }
            fclose($output);
            exit;
        } elseif ($_POST['export_type'] === 'excel') {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
            $output = fopen('php://output', 'w');
            fputs($output, "\xEF\xBB\xBF");
            if ($category === 'all') {
                fputcsv($output, ['Kategori', 'ID', 'Ad', 'Tarih', 'Açıklama'], "\t");
                foreach ($data as $row) {
                    fputcsv($output, [$row['category'], $row['id'], $row['name'] ?? '-', $row['created_at'], $row['description'] ?? '-'], "\t");
                }
            } else {
                fputcsv($output, ['ID', 'Ad', 'Tarih', 'Açıklama'], "\t");
                foreach ($data as $row) {
                    fputcsv($output, [$row['id'], $row['name'] ?? '-', $row['created_at'], $row['description'] ?? '-'], "\t");
                }
            }
            fclose($output);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Entity listesini güncelle
$stmt = $pdo->query("SELECT id, name FROM personnel UNION SELECT id, name FROM customers UNION SELECT id, name FROM suppliers UNION SELECT id, name FROM users ORDER BY name ASC");
$entity_list = $stmt->fetchAll();

ob_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function exportReport(type) {
            document.getElementById('export_type').value = type;
            document.getElementById('reportForm').submit();
        }
    </script>
    <style>
        .chart-container {
            position: relative;
            margin: auto;
            height: 400px;
            width: 600px;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2>Raporlar</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" id="reportForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token'] ?? bin2hex(random_bytes(32)); ?>">
            <input type="hidden" name="export_type" id="export_type">
            <div class="mb-3">
                <label for="start_date" class="form-label">Başlangıç Tarihi</label>
                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-01', strtotime('-1 month')); ?>" required>
            </div>
            <div class="mb-3">
                <label for="end_date" class="form-label">Bitiş Tarihi</label>
                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="mb-3">
                <label for="category" class="form-label">Kategori</label>
                <select name="category" id="category" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo (!isset($_POST['category']) || $_POST['category'] === 'all') ? 'selected' : ''; ?>>Tümü</option>
                    <option value="personnel" <?php echo (isset($_POST['category']) && $_POST['category'] === 'personnel') ? 'selected' : ''; ?>>Personel</option>
                    <option value="cash_accounts" <?php echo (isset($_POST['category']) && $_POST['category'] === 'cash_accounts') ? 'selected' : ''; ?>>Kasa Hesapları</option>
                    <option value="cash_transactions" <?php echo (isset($_POST['category']) && $_POST['category'] === 'cash_transactions') ? 'selected' : ''; ?>>Kasa Hareketleri</option>
                    <option value="credits" <?php echo (isset($_POST['category']) && $_POST['category'] === 'credits') ? 'selected' : ''; ?>>Krediler</option>
                    <option value="credit_installments" <?php echo (isset($_POST['category']) && $_POST['category'] === 'credit_installments') ? 'selected' : ''; ?>>Kredi Taksitleri</option>
                    <option value="customers" <?php echo (isset($_POST['category']) && $_POST['category'] === 'customers') ? 'selected' : ''; ?>>Müşteriler</option>
                    <option value="customer_transactions" <?php echo (isset($_POST['category']) && $_POST['category'] === 'customer_transactions') ? 'selected' : ''; ?>>Müşteri Hareketleri</option>
                    <option value="products" <?php echo (isset($_POST['category']) && $_POST['category'] === 'products') ? 'selected' : ''; ?>>Ürünler</option>
                    <option value="sales" <?php echo (isset($_POST['category']) && $_POST['category'] === 'sales') ? 'selected' : ''; ?>>Satışlar</option>
                    <option value="sales_invoices" <?php echo (isset($_POST['category']) && $_POST['category'] === 'sales_invoices') ? 'selected' : ''; ?>>Satış Faturaları</option>
                    <option value="suppliers" <?php echo (isset($_POST['category']) && $_POST['category'] === 'suppliers') ? 'selected' : ''; ?>>Tedarikçiler</option>
                    <option value="warehouses" <?php echo (isset($_POST['category']) && $_POST['category'] === 'warehouses') ? 'selected' : ''; ?>>Depolar</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="filter_id" class="form-label">Filtre (ID veya Ad)</label>
                <select name="filter_id" id="filter_id" class="form-select">
                    <option value="">Tümü</option>
                    <?php foreach ($entity_list as $entity): ?>
                        <option value="<?php echo $entity['id']; ?>"><?php echo htmlspecialchars($entity['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="button" class="btn btn-primary" onclick="exportReport('pdf')">PDF Olarak Dışarı Aktar</button>
            <button type="button" class="btn btn-success" onclick="exportReport('csv')">CSV Olarak Dışarı Aktar</button>
            <button type="button" class="btn btn-warning" onclick="exportReport('excel')">Excel Olarak Dışarı Aktar</button>
        </form>

        <h3>Rapor Görselleştirmesi</h3>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
        <script>
            // Satışlar için grafik (örnek)
            <?php
            $stmt = $pdo->prepare("SELECT c.name, SUM(s.total_amount) as total_sales
                                   FROM sales s
                                   JOIN customers c ON s.customer_id = c.id
                                   WHERE s.created_at BETWEEN ? AND ?
                                   GROUP BY c.id, c.name");
            $stmt->execute([date('Y-m-01', strtotime('-1 month')), date('Y-m-d')]);
            $sales_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = array_column($sales_data, 'name');
            $sales = array_column($sales_data, 'total_sales');
            ?>
            var ctx = document.getElementById('salesChart').getContext('2d');
            var salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Toplam Satış (TRY)',
                        data: <?php echo json_encode($sales); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>