<?php
session_start();

require_once 'config/db.php';
require_once 'functions/auth.php';
require_once 'vendor/autoload.php'; // TCPDF ve diğer bağımlılıklar için
require_once 'functions/currency.php';
require_once 'functions/cash.php';

checkRoleAccess(['müdür', 'yönetici', 'admin']);
$title = 'Personel Yönetimi';
$error = null;
$success = null;

$breadcrumbs = [
    ['title' => 'Ana Sayfa', 'url' => 'index.php'],
    ['title' => 'Personel Yönetimi', 'url' => '']
];

if (!isset($_SESSION['personnel_token'])) {
    $_SESSION['personnel_token'] = bin2hex(random_bytes(32));
}


$personnel = $pdo->query("SELECT * FROM personnel ORDER BY created_at DESC")->fetchAll();
$payments = $pdo->query("SELECT p.id, p.name, p.salary, pa.amount, pa.issue_date, pa.currency, pa.description
                         FROM personnel p
                         LEFT JOIN personnel_advances pa ON p.id = pa.personnel_id
                         WHERE pa.amount IS NOT NULL
                         ORDER BY pa.issue_date DESC")->fetchAll();
$assets = $pdo->query("SELECT pa.*, p.name FROM personnel_assets pa JOIN personnel p ON pa.personnel_id = p.id ORDER BY pa.created_at DESC")->fetchAll();
$leaves = $pdo->query("SELECT pl.*, p.name FROM personnel_leaves pl JOIN personnel p ON pl.personnel_id = p.id ORDER BY pl.created_at DESC")->fetchAll();
$overtime = $pdo->query("SELECT po.*, p.name FROM personnel_overtime po JOIN personnel p ON po.personnel_id = p.id ORDER BY po.created_at DESC")->fetchAll();
$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll();

$payments_by_personnel = [];
$assets_by_personnel = [];
$leaves_by_personnel = [];
$overtime_by_personnel = [];
foreach ($payments as $payment) {
    if ($payment['amount']) {
        $payments_by_personnel[$payment['id']][] = $payment;
    }
}
foreach ($assets as $asset) {
    $assets_by_personnel[$asset['personnel_id']][] = $asset;
}
foreach ($leaves as $leave) {
    $leaves_by_personnel[$leave['personnel_id']][] = $leave;
}
foreach ($overtime as $ot) {
    $overtime_by_personnel[$ot['personnel_id']][] = $ot;
}

$remaining_balances = [];
$overtime_earnings = [];
foreach ($personnel as $person) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_advances FROM personnel_advances WHERE personnel_id = ?");
    $stmt->execute([$person['id']]);
    $total_advances = $stmt->fetchColumn() ?: 0.00;
    $remaining_balances[$person['id']] = $person['salary'] - $total_advances;

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(overtime_earning), 0) as total_overtime FROM personnel_overtime WHERE personnel_id = ?");
    $stmt->execute([$person['id']]);
    $overtime_earnings[$person['id']] = $stmt->fetchColumn() ?: 0.00;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF token doğrulama ve hata ayıklama
        $sentToken = $_POST['csrf_token'] ?? 'none';
        if (!isset($_POST['csrf_token']) || $sentToken !== $_SESSION['personnel_token']) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Invalid CSRF token at " . date('Y-m-d H:i:s') . ": Sent: {$sentToken}, Expected: {$_SESSION['personnel_token']}, POST: " . json_encode($_POST)]);
            throw new Exception("Geçersiz CSRF token. Lütfen sayfayı yenileyin ve tekrar deneyin.");
        }
        $_SESSION['personnel_token'] = bin2hex(random_bytes(32)); // Token'ı her başarılı istekten sonra yenile

        if (!isset($_POST['type'])) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Undefined type in POST: " . json_encode($_POST)]);
            throw new Exception("Geçersiz işlem tipi. Lütfen tekrar deneyin.");
        }

        $type = $_POST['type'];

        if ($type === 'add_personnel') {
            $name = trim($_POST['name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $salary = filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $phone = trim($_POST['phone'] ?? '');
            if (!$name || $salary === false || !$email || !preg_match('/^\+?\d{10,15}$/', $phone)) {
                throw new Exception("Geçerli bir ad, pozitif maaş, e-posta ve telefon numarası girin.");
            }
            $stmt = $pdo->prepare("SELECT id FROM personnel WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Bu e-posta adresi zaten kullanılıyor.");
            }
            $stmt = $pdo->prepare("INSERT INTO personnel (name, position, salary, email, phone, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$name, $position, $salary, $email, $phone]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Personnel {$name} added with email: {$email}"]);
            $success = "Personel başarıyla eklendi.";
        } elseif ($type === 'edit_personnel') {
            $id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $name = trim($_POST['name'] ?? '');
            $position = trim($_POST['position'] ?? '');
            $salary = filter_var($_POST['salary'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
            $phone = trim($_POST['phone'] ?? '');
            if (!$id || !$name || $salary === false || !$email || !preg_match('/^\+?\d{10,15}$/', $phone)) {
                throw new Exception("Geçerli bir personel ID, ad, pozitif maaş, e-posta ve telefon numarası girin.");
            }
            $stmt = $pdo->prepare("SELECT id FROM personnel WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception("Bu e-posta başka bir personel tarafından kullanılıyor.");
            }
            $stmt = $pdo->prepare("UPDATE personnel SET name = ?, position = ?, salary = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $position, $salary, $email, $phone, $id]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Personnel ID {$id} updated: {$name}"]);
            $success = "Personel başarıyla güncellendi.";
        } elseif ($type === 'delete_personnel') {
            $id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception("Geçerli bir personel ID girin.");
            }
            $stmt = $pdo->prepare("DELETE FROM personnel WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Personnel ID {$id} deleted"]);
            $success = "Personel ve ilgili kayıtları başarıyla silindi.";
        } elseif ($type === 'add_payment') {
            $personnel_id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $payment_type = $_POST['payment_type'] ?? null;
            $amount = filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
            $payment_date = $_POST['payment_date'] ?? null;
            $currency = $_POST['currency'] ?? 'TRY';
            $account_id = filter_var($_POST['account_id'], FILTER_VALIDATE_INT);
            $description = trim($_POST['description'] ?? '');

            if (!$personnel_id || !in_array($payment_type, ['salary', 'advance']) || !$amount || !$payment_date || !$account_id) {
                throw new Exception("Geçerli bir personel, ödeme tipi, tutar, ödeme tarihi ve kasa hesabı girin.");
            }

            $stmt = $pdo->prepare("SELECT balance, currency AS account_currency FROM cash_accounts WHERE id = ?");
            $stmt->execute([$account_id]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) {
                throw new Exception("Seçilen kasa hesabı bulunamadı.");
            }

            $amount_try = convertToTRY($amount, $currency, $account['account_currency']);
            if ($account['balance'] < $amount_try) {
                throw new Exception("Yetersiz kasa bakiyesi. Mevcut bakiye: " . number_format($account['balance'], 2) . " " . $account['account_currency']);
            }

            $new_balance = $account['balance'] - $amount_try;
            $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = ? WHERE id = ?");
            $stmt->execute([$new_balance, $account_id]);

            if ($payment_type === 'advance') {
                $stmt = $pdo->prepare("SELECT salary, COALESCE(SUM(amount), 0) as total_advances FROM personnel p LEFT JOIN personnel_advances pa ON p.id = pa.personnel_id WHERE p.id = ? GROUP BY p.id");
                $stmt->execute([$personnel_id]);
                $result = $stmt->fetch();
                $remaining_balance = $result['salary'] - $result['total_advances'];
                if ($amount > $remaining_balance) {
                    throw new Exception("Avans miktarı kalan bakiyeden büyük olamaz. Kalan bakiye: " . number_format($remaining_balance, 2) . " TRY");
                }
                $stmt = $pdo->prepare("INSERT INTO personnel_advances (personnel_id, account_id, amount, issue_date, currency, description, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$personnel_id, $account_id, $amount, $payment_date, $currency, $description]);
            } elseif ($payment_type === 'salary') {
                $stmt = $pdo->prepare("INSERT INTO personnel_payments (personnel_id, account_id, amount, currency, amount_try, payment_type, description, payment_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$personnel_id, $account_id, $amount, $currency, $amount_try, $payment_type, $description, $payment_date]);
            }

            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Payment {$payment_type} of {$amount} {$currency} for personnel ID {$personnel_id} from account ID {$account_id}"]);
            $success = ucfirst($payment_type) . " ödemesi başarıyla eklendi.";
        } elseif ($type === 'add_asset') {
            $personnel_id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $asset_name = trim($_POST['asset_name'] ?? '');
            $assigned_date = $_POST['assigned_date'] ?? null;
            $description = trim($_POST['description'] ?? '');
            if (!$personnel_id || !$asset_name || !$assigned_date) {
                throw new Exception("Geçerli bir personel, varlık adı ve atama tarihi girin.");
            }
            $stmt = $pdo->prepare("INSERT INTO personnel_assets (personnel_id, asset_name, assigned_date, description, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$personnel_id, $asset_name, $assigned_date, $description]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Asset {$asset_name} assigned to personnel ID {$personnel_id}"]);
            $success = "Varlık başarıyla eklendi.";
        } elseif ($type === 'add_leave') {
            $personnel_id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;
            $reason = trim($_POST['reason'] ?? '');
            if (!$personnel_id || !$start_date || !$end_date || strtotime($start_date) > strtotime($end_date)) {
                throw new Exception("Geçerli bir personel, başlangıç ve bitiş tarihi girin (başlangıç bitişten önce olmalı).");
            }
            $stmt = $pdo->prepare("INSERT INTO personnel_leaves (personnel_id, start_date, end_date, reason, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$personnel_id, $start_date, $end_date, $reason]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Leave added for personnel ID {$personnel_id} from {$start_date} to {$end_date}"]);
            $success = "İzin başarıyla eklendi.";
        } elseif ($type === 'add_overtime') {
            $personnel_id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $work_date = $_POST['work_date'] ?? null;
            $start_time = $_POST['start_time'] ?? null;
            $end_time = $_POST['end_time'] ?? null;
            $description = trim($_POST['description'] ?? '');
            if (!$personnel_id || !$work_date || !$start_time || !$end_time || strtotime($start_time) >= strtotime($end_time)) {
                throw new Exception("Geçerli bir personel, çalışma tarihi, giriş ve çıkış saati girin (çıkış girişten sonra olmalı).");
            }
            $start = new DateTime($start_time);
            $end = new DateTime($end_time);
            $interval = $start->diff($end);
            $hours_worked = $interval->h + ($interval->i / 60);
            if ($hours_worked <= 0) {
                throw new Exception("Çıkış saati giriş saatinden önce olamaz.");
            }
            $stmt = $pdo->prepare("SELECT salary FROM personnel WHERE id = ?");
            $stmt->execute([$personnel_id]);
            $salary = $stmt->fetchColumn() ?: 0;
            $hourly_rate = $salary / 30 / 8; // 30 gün, 8 saat varsayımı
            $overtime_earning = $hours_worked * $hourly_rate;
            $stmt = $pdo->prepare("INSERT INTO personnel_overtime (personnel_id, work_date, start_time, end_time, hours_worked, overtime_earning, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$personnel_id, $work_date, $start_time, $end_time, $hours_worked, $overtime_earning, $description]);
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Overtime added for personnel ID {$personnel_id}: {$hours_worked} hours, earning {$overtime_earning} TRY"]);
            $success = "Mesai başarıyla eklendi.";
        } elseif ($type === 'export_pdf' || $type === 'export_csv') {
            $export_type = $_POST['export_type'] ?? 'all';
            $period = $_POST['period'] ?? 'all';
            $personnel_id = filter_var($_POST['personnel_id'], FILTER_VALIDATE_INT);
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;

            if ($period !== 'all' && (!$start_date || !$end_date || strtotime($start_date) > strtotime($end_date))) {
                throw new Exception("Geçerli bir tarih aralığı girin (başlangıç bitişten önce olmalı).");
            }

            if ($export_type === 'all') {
                $data = $personnel;
                $filename = "personnel_" . date('Ymd_His');
                if ($type === 'export_pdf') {
                    $pdf = new TCPDF();
                    $pdf->SetCreator(PDF_CREATOR);
                    $pdf->SetAuthor('Your Company');
                    $pdf->SetTitle('Personel Listesi');
                    $pdf->AddPage();
                    $pdf->SetFont('dejavusans', '', 10);
                    $html = '<h1>Personel Listesi</h1><table border="1" cellpadding="5"><thead><tr><th>ID</th><th>Ad Soyad</th><th>Pozisyon</th><th>Maaş</th><th>Kalan Bakiye</th><th>Mesai Kazancı</th><th>E-posta</th><th>Telefon</th><th>Kayıt Tarihi</th></tr></thead><tbody>';
                    foreach ($data as $row) {
                        $remaining_balance = $row['salary'] - $row['total_advances'];
                        $html .= "<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['name']) . "</td><td>" . htmlspecialchars($row['position'] ?? '-') . "</td><td>" . number_format($row['salary'], 2) . " TRY</td><td>" . number_format($remaining_balance, 2) . " TRY</td><td>" . number_format($row['total_overtime'], 2) . " TRY</td><td>" . htmlspecialchars($row['email']) . "</td><td>" . htmlspecialchars($row['phone']) . "</td><td>{$row['created_at']}</td></tr>";
                    }
                    $html .= '</tbody></table>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    $pdf->Output($filename . '.pdf', 'D');
                } else {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $output = fopen('php://output', 'w');
                    fputs($output, "\xEF\xBB\xBF");
                    fputcsv($output, ['ID', 'Ad Soyad', 'Pozisyon', 'Maaş (TRY)', 'Kalan Bakiye (TRY)', 'Mesai Kazancı (TRY)', 'E-posta', 'Telefon', 'Kayıt Tarihi']);
                    foreach ($data as $row) {
                        $remaining_balance = $row['salary'] - $row['total_advances'];
                        fputcsv($output, [$row['id'], $row['name'], $row['position'] ?? '-', number_format($row['salary'], 2), number_format($remaining_balance, 2), number_format($row['total_overtime'], 2), $row['email'], $row['phone'], $row['created_at']]);
                    }
                    fclose($output);
                }
                exit;
            } elseif ($export_type === 'details' && $personnel_id) {
                $stmt = $pdo->prepare("SELECT * FROM personnel WHERE id = ?");
                $stmt->execute([$personnel_id]);
                $person = $stmt->fetch();
                if (!$person) {
                    throw new Exception("Personel bulunamadı.");
                }
                $where_clause = $period === 'all' ? '' : " AND issue_date BETWEEN ? AND ?";
                $params = [$personnel_id];
                if ($period !== 'all' && $start_date && $end_date) {
                    $params[] = $start_date;
                    $params[] = $end_date;
                }
                $payments = $pdo->prepare("SELECT * FROM personnel_advances WHERE personnel_id = ? $where_clause ORDER BY issue_date DESC");
                $payments->execute($params);
                $payments_data = $payments->fetchAll();

                $where_clause = $period === 'all' ? '' : " AND assigned_date BETWEEN ? AND ?";
                $params = [$personnel_id];
                if ($period !== 'all' && $start_date && $end_date) {
                    $params[] = $start_date;
                    $params[] = $end_date;
                }
                $assets = $pdo->prepare("SELECT * FROM personnel_assets WHERE personnel_id = ? $where_clause ORDER BY assigned_date DESC");
                $assets->execute($params);
                $assets_data = $assets->fetchAll();

                $where_clause = $period === 'all' ? '' : " AND start_date BETWEEN ? AND ?";
                $params = [$personnel_id];
                if ($period !== 'all' && $start_date && $end_date) {
                    $params[] = $start_date;
                    $params[] = $end_date;
                }
                $leaves = $pdo->prepare("SELECT * FROM personnel_leaves WHERE personnel_id = ? $where_clause ORDER BY start_date DESC");
                $leaves->execute($params);
                $leaves_data = $leaves->fetchAll();

                $where_clause = $period === 'all' ? '' : " AND work_date BETWEEN ? AND ?";
                $params = [$personnel_id];
                if ($period !== 'all' && $start_date && $end_date) {
                    $params[] = $start_date;
                    $params[] = $end_date;
                }
                $overtime = $pdo->prepare("SELECT * FROM personnel_overtime WHERE personnel_id = ? $where_clause ORDER BY work_date DESC");
                $overtime->execute($params);
                $overtime_data = $overtime->fetchAll();

                $filename = "personnel_{$personnel_id}_details_" . date('Ymd_His');
                if ($type === 'export_pdf') {
                    $pdf = new TCPDF();
                    $pdf->SetCreator(PDF_CREATOR);
                    $pdf->SetAuthor('Your Company');
                    $pdf->SetTitle('Personel Detayları: ' . $person['name']);
                    $pdf->AddPage();
                    $pdf->SetFont('dejavusans', '', 10);
                    $html = '<h1>Personel Detayları: ' . htmlspecialchars($person['name']) . '</h1>';
                    $html .= '<p><strong>Pozisyon:</strong> ' . htmlspecialchars($person['position'] ?? '-') . '</p>';
                    $html .= '<p><strong>Maaş:</strong> ' . number_format($person['salary'], 2) . ' TRY</p>';
                    $html .= '<p><strong>Kalan Bakiye:</strong> ' . number_format($person['salary'] - array_sum(array_column($payments_data, 'amount')), 2) . ' TRY</p>';
                    $html .= '<p><strong>Toplam Mesai Kazancı:</strong> ' . number_format(array_sum(array_column($overtime_data, 'overtime_earning')), 2) . ' TRY</p>';
                    $html .= '<h2>Ödeme Geçmişi</h2><table border="1" cellpadding="5"><thead><tr><th>Tutar</th><th>Tarih</th><th>Para Birimi</th><th>Açıklama</th></tr></thead><tbody>';
                    foreach ($payments_data as $p) {
                        $html .= "<tr><td>" . number_format($p['amount'], 2) . "</td><td>{$p['issue_date']}</td><td>{$p['currency']}</td><td>" . htmlspecialchars($p['description'] ?? '-') . "</td></tr>";
                    }
                    $html .= '</tbody></table>';
                    $html .= '<h2>Zimmetler</h2><table border="1" cellpadding="5"><thead><tr><th>Varlık Adı</th><th>Atama Tarihi</th><th>İade Tarihi</th><th>Açıklama</th></tr></thead><tbody>';
                    foreach ($assets_data as $a) {
                        $html .= "<tr><td>" . htmlspecialchars($a['asset_name']) . "</td><td>{$a['assigned_date']}</td><td>" . ($a['return_date'] ?? '-') . "</td><td>" . htmlspecialchars($a['description'] ?? '-') . "</td></tr>";
                    }
                    $html .= '</tbody></table>';
                    $html .= '<h2>İzinler</h2><table border="1" cellpadding="5"><thead><tr><th>Başlangıç</th><th>Bitiş</th><th>Neden</th></tr></thead><tbody>';
                    foreach ($leaves_data as $l) {
                        $html .= "<tr><td>{$l['start_date']}</td><td>{$l['end_date']}</td><td>" . htmlspecialchars($l['reason'] ?? '-') . "</td></tr>";
                    }
                    $html .= '</tbody></table>';
                    $html .= '<h2>Mesai Geçmişi</h2><table border="1" cellpadding="5"><thead><tr><th>Tarih</th><th>Giriş</th><th>Çıkış</th><th>Saat</th><th>Hak Ediş</th><th>Açıklama</th></tr></thead><tbody>';
                    foreach ($overtime_data as $o) {
                        $html .= "<tr><td>{$o['work_date']}</td><td>{$o['start_time']}</td><td>{$o['end_time']}</td><td>" . number_format($o['hours_worked'], 2) . "</td><td>" . number_format($o['overtime_earning'], 2) . "</td><td>" . htmlspecialchars($o['description'] ?? '-') . "</td></tr>";
                    }
                    $html .= '</tbody></table>';
                    $pdf->writeHTML($html, true, false, true, false, '');
                    $pdf->Output($filename . '.pdf', 'D');
                } else {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
                    $output = fopen('php://output', 'w');
                    fputs($output, "\xEF\xBB\xBF");
                    fputcsv($output, ['Personel Detayları: ' . $person['name']]);
                    fputcsv($output, ['Pozisyon', $person['position'] ?? '-']);
                    fputcsv($output, ['Maaş', number_format($person['salary'], 2) . ' TRY']);
                    fputcsv($output, ['Kalan Bakiye', number_format($person['salary'] - array_sum(array_column($payments_data, 'amount')), 2) . ' TRY']);
                    fputcsv($output, ['Toplam Mesai Kazancı', number_format(array_sum(array_column($overtime_data, 'overtime_earning')), 2) . ' TRY']);
                    fputcsv($output, []);
                    fputcsv($output, ['Ödeme Geçmişi']);
                    fputcsv($output, ['Tutar', 'Tarih', 'Para Birimi', 'Açıklama']);
                    foreach ($payments_data as $p) {
                        fputcsv($output, [number_format($p['amount'], 2), $p['issue_date'], $p['currency'], $p['description'] ?? '-']);
                    }
                    fputcsv($output, []);
                    fputcsv($output, ['Zimmetler']);
                    fputcsv($output, ['Varlık Adı', 'Atama Tarihi', 'İade Tarihi', 'Açıklama']);
                    foreach ($assets_data as $a) {
                        fputcsv($output, [$a['asset_name'], $a['assigned_date'], $a['return_date'] ?? '-', $a['description'] ?? '-']);
                    }
                    fputcsv($output, []);
                    fputcsv($output, ['İzinler']);
                    fputcsv($output, ['Başlangıç', 'Bitiş', 'Neden']);
                    foreach ($leaves_data as $l) {
                        fputcsv($output, [$l['start_date'], $l['end_date'], $l['reason'] ?? '-']);
                    }
                    fputcsv($output, []);
                    fputcsv($output, ['Mesai Geçmişi']);
                    fputcsv($output, ['Tarih', 'Giriş', 'Çıkış', 'Saat', 'Hak Ediş', 'Açıklama']);
                    foreach ($overtime_data as $o) {
                        fputcsv($output, [$o['work_date'], $o['start_time'], $o['end_time'], number_format($o['hours_worked'], 2), number_format($o['overtime_earning'], 2), $o['description'] ?? '-']);
                    }
                    fclose($output);
                }
                exit;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Hata at " . date('Y-m-d H:i:s') . ": {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}
ob_start();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmPayment(id) {
            if (confirm('Ödemeyi onaylamak istediğinizden emin misiniz?')) {
                document.getElementById('paymentForm' + id).submit();
            }
        }
        function confirmAsset(id) {
            if (confirm('Varlığı eklemek istediğinizden emin misiniz?')) {
                document.getElementById('assetForm' + id).submit();
            }
        }
        function confirmLeave(id) {
            if (confirm('İzni eklemek istediğinizden emin misiniz?')) {
                document.getElementById('leaveForm' + id).submit();
            }
        }
        function confirmOvertime(id) {
            if (confirm('Mesaiyi eklemek istediğinizden emin misiniz?')) {
                document.getElementById('overtimeForm' + id).submit();
            }
        }
        function updatePrintForm(id) {
            const period = document.getElementById('period_' + id).value;
            const dateFields = document.getElementById('dateFields_' + id);
            if (period === 'all') {
                dateFields.style.display = 'none';
            } else {
                dateFields.style.display = 'block';
            }
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <h2>Personel Yönetimi</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="mb-4">
            <h5>Tüm Personeli Dışarı Aktar</h5>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                <input type="hidden" name="type" value="export_pdf">
                <input type="hidden" name="export_type" value="all">
                <button type="submit" class="btn btn-success">PDF Olarak Dışarı Aktar</button>
            </form>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                <input type="hidden" name="type" value="export_csv">
                <input type="hidden" name="export_type" value="all">
                <button type="submit" class="btn btn-success">CSV Olarak Dışarı Aktar</button>
            </form>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPersonnelModal">Yeni Personel Ekle</button>
        </div>
        <div class="modal fade" id="addPersonnelModal" tabindex="-1" aria-labelledby="addPersonnelModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addPersonnelModalLabel">Yeni Personel Ekle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                            <input type="hidden" name="type" value="add_personnel">
                            <div class="mb-3">
                                <label for="name" class="form-label">Ad Soyad</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="position" class="form-label">Pozisyon</label>
                                <input type="text" name="position" id="position" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label for="salary" class="form-label">Aylık Maaş</label>
                                <input type="number" step="0.01" name="salary" id="salary" class="form-control" value="0.00" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="text" name="phone" id="phone" class="form-control" pattern="\+?\d{10,15}" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Ekle</button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    </div>
                </div>
            </div>
        </div>
        <h3>Personel Listesi</h3>
        <?php if (empty($personnel)): ?>
            <div class="alert alert-info">Kayıtlı personel bulunmamaktadır.</div>
        <?php else: ?>
            <?php foreach ($personnel as $person): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($person['name']); ?></h5>
                        <p class="card-text">
                            <strong>Pozisyon:</strong> <?php echo htmlspecialchars($person['position'] ?? '-'); ?><br>
                            <strong>Aylık Maaş:</strong> <?php echo number_format($person['salary'], 2); ?> TRY<br>
                            <strong>Kalan Bakiye:</strong> <?php echo number_format($remaining_balances[$person['id']], 2); ?> TRY<br>
                            <strong>Toplam Mesai Kazancı:</strong> <?php echo number_format($overtime_earnings[$person['id']], 2); ?> TRY<br>
                            <strong>E-posta:</strong> <?php echo htmlspecialchars($person['email']); ?><br>
                            <strong>Telefon:</strong> <?php echo htmlspecialchars($person['phone']); ?><br>
                            <strong>Kayıt Tarihi:</strong> <?php echo $person['created_at']; ?>
                        </p>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $person['id']; ?>">Detaylar</button>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $person['id']; ?>">Düzenle</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu personeli ve ilgili kayıtlarını silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                            <input type="hidden" name="type" value="delete_personnel">
                            <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                            <button type="submit" class="btn btn-danger">Sil</button>
                        </form>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal<?php echo $person['id']; ?>">Ödeme Ekle</button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#assetModal<?php echo $person['id']; ?>">Varlık Ekle</button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#leaveModal<?php echo $person['id']; ?>">İzin Ekle</button>
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#overtimeModal<?php echo $person['id']; ?>">Mesai Ekle</button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#printModal<?php echo $person['id']; ?>">Yazdır</button>
                    </div>
                </div>

                <div class="modal fade" id="detailsModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="detailsModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="detailsModalLabel<?php echo $person['id']; ?>">Personel Detayları: <?php echo htmlspecialchars($person['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p><strong>Kalan Bakiye:</strong> <?php echo number_format($remaining_balances[$person['id']], 2); ?> TRY</p>
                                <p><strong>Toplam Mesai Kazancı:</strong> <?php echo number_format($overtime_earnings[$person['id']], 2); ?> TRY</p>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="export_pdf">
                                    <input type="hidden" name="export_type" value="details">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <button type="submit" class="btn btn-success mb-2">PDF Olarak Dışarı Aktar</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="export_csv">
                                    <input type="hidden" name="export_type" value="details">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <button type="submit" class="btn btn-success mb-2">CSV Olarak Dışarı Aktar</button>
                                </form>
                                <ul class="nav nav-tabs mb-4" id="detailsTabs<?php echo $person['id']; ?>" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="payments-tab-<?php echo $person['id']; ?>" data-bs-toggle="tab" data-bs-target="#payments-<?php echo $person['id']; ?>" type="button" role="tab" aria-controls="payments-<?php echo $person['id']; ?>" aria-selected="true">Ödeme Geçmişi</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="assets-tab-<?php echo $person['id']; ?>" data-bs-toggle="tab" data-bs-target="#assets-<?php echo $person['id']; ?>" type="button" role="tab" aria-controls="assets-<?php echo $person['id']; ?>" aria-selected="false">Varlıklar</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="leaves-tab-<?php echo $person['id']; ?>" data-bs-toggle="tab" data-bs-target="#leaves-<?php echo $person['id']; ?>" type="button" role="tab" aria-controls="leaves-<?php echo $person['id']; ?>" aria-selected="false">İzinler</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="overtime-tab-<?php echo $person['id']; ?>" data-bs-toggle="tab" data-bs-target="#overtime-<?php echo $person['id']; ?>" type="button" role="tab" aria-controls="overtime-<?php echo $person['id']; ?>" aria-selected="false">Mesai Geçmişi</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="detailsTabsContent<?php echo $person['id']; ?>">
                                    <div class="tab-pane fade show active" id="payments-<?php echo $person['id']; ?>" role="tabpanel" aria-labelledby="payments-tab-<?php echo $person['id']; ?>">
                                        <h6>Ödeme Geçmişi</h6>
                                        <?php
                                        $person_payments = $payments_by_personnel[$person['id']] ?? [];
                                        if (empty($person_payments)):
                                        ?>
                                            <div class="alert alert-info">Bu personel için ödeme kaydı bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Tutar</th>
                                                        <th>Ödeme Tarihi</th>
                                                        <th>Para Birimi</th>
                                                        <th>Açıklama</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($person_payments as $payment): ?>
                                                        <tr>
                                                            <td><?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?></td>
                                                            <td><?php echo $payment['issue_date']; ?></td>
                                                            <td><?php echo htmlspecialchars($payment['currency']); ?></td>
                                                            <td><?php echo htmlspecialchars($payment['description'] ?? '-'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="assets-<?php echo $person['id']; ?>" role="tabpanel" aria-labelledby="assets-tab-<?php echo $person['id']; ?>">
                                        <h6>Zimmetler</h6>
                                        <?php
                                        $person_assets = $assets_by_personnel[$person['id']] ?? [];
                                        if (empty($person_assets)):
                                        ?>
                                            <div class="alert alert-info">Bu personel için zimmet kaydı bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Varlık Adı</th>
                                                        <th>Atama Tarihi</th>
                                                        <th>İade Tarihi</th>
                                                        <th>Açıklama</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($person_assets as $asset): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($asset['asset_name']); ?></td>
                                                            <td><?php echo $asset['assigned_date']; ?></td>
                                                            <td><?php echo $asset['return_date'] ?? '-'; ?></td>
                                                            <td><?php echo htmlspecialchars($asset['description'] ?? '-'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="leaves-<?php echo $person['id']; ?>" role="tabpanel" aria-labelledby="leaves-tab-<?php echo $person['id']; ?>">
                                        <h6>İzinler</h6>
                                        <?php
                                        $person_leaves = $leaves_by_personnel[$person['id']] ?? [];
                                        if (empty($person_leaves)):
                                        ?>
                                            <div class="alert alert-info">Bu personel için izin kaydı bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Başlangıç Tarihi</th>
                                                        <th>Bitiş Tarihi</th>
                                                        <th>Neden</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($person_leaves as $leave): ?>
                                                        <tr>
                                                            <td><?php echo $leave['start_date']; ?></td>
                                                            <td><?php echo $leave['end_date']; ?></td>
                                                            <td><?php echo htmlspecialchars($leave['reason'] ?? '-'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tab-pane fade" id="overtime-<?php echo $person['id']; ?>" role="tabpanel" aria-labelledby="overtime-tab-<?php echo $person['id']; ?>">
                                        <h6>Mesai Geçmişi</h6>
                                        <?php
                                        $person_overtime = $overtime_by_personnel[$person['id']] ?? [];
                                        if (empty($person_overtime)):
                                        ?>
                                            <div class="alert alert-info">Bu personel için mesai kaydı bulunmamaktadır.</div>
                                        <?php else: ?>
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Tarih</th>
                                                        <th>Giriş Saati</th>
                                                        <th>Çıkış Saati</th>
                                                        <th>Çalışma Saati</th>
                                                        <th>Hak Ediş</th>
                                                        <th>Açıklama</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($person_overtime as $ot): ?>
                                                        <tr>
                                                            <td><?php echo $ot['work_date']; ?></td>
                                                            <td><?php echo $ot['start_time']; ?></td>
                                                            <td><?php echo $ot['end_time']; ?></td>
                                                            <td><?php echo number_format($ot['hours_worked'], 2); ?> saat</td>
                                                            <td><?php echo number_format($ot['overtime_earning'], 2); ?> TRY</td>
                                                            <td><?php echo htmlspecialchars($ot['description'] ?? '-'); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="editModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?php echo $person['id']; ?>">Personel Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="edit_personnel">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="name_<?php echo $person['id']; ?>" class="form-label">Ad Soyad</label>
                                        <input type="text" name="name" id="name_<?php echo $person['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($person['name']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="position_<?php echo $person['id']; ?>" class="form-label">Pozisyon</label>
                                        <input type="text" name="position" id="position_<?php echo $person['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($person['position'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="salary_<?php echo $person['id']; ?>" class="form-label">Aylık Maaş</label>
                                        <input type="number" step="0.01" name="salary" id="salary_<?php echo $person['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($person['salary']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email_<?php echo $person['id']; ?>" class="form-label">E-posta</label>
                                        <input type="email" name="email" id="email_<?php echo $person['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($person['email']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone_<?php echo $person['id']; ?>" class="form-label">Telefon</label>
                                        <input type="text" name="phone" id="phone_<?php echo $person['id']; ?>" class="form-control" pattern="\+?\d{10,15}" value="<?php echo htmlspecialchars($person['phone']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="paymentModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addPaymentModalLabel<?php echo $person['id']; ?>">Personel Ödeme Ekle: <?php echo htmlspecialchars($person['name'] ?: '-'); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="account_id_<?php echo $person['id']; ?>" class="form-label">Kasa Hesabı</label>
                                        <select name="account_id" class="form-select" required>
                                            <?php foreach ($accounts as $account): ?>
                                                <option value="<?php echo $account['id']; ?>">
                                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_type_<?php echo $person['id']; ?>" class="form-label">Ödeme Tipi</label>
                                        <select name="payment_type" class="form-select" required>
                                            <option value="salary">Maaş</option>
                                            <option value="advance">Avans</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="amount_<?php echo $person['id']; ?>" class="form-label">Tutar</label>
                                        <input type="number" step="0.01" name="amount" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="currency_<?php echo $person['id']; ?>" class="form-label">Para Birimi</label>
                                        <select name="currency" class="form-select">
                                            <option value="TRY">TRY</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="payment_date_<?php echo $person['id']; ?>" class="form-label">Ödeme Tarihi</label>
                                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_<?php echo $person['id']; ?>" class="form-label">Açıklama</label>
                                        <textarea name="description" class="form-control"></textarea>
                                    </div>
                                    <button type="submit" name="add_payment" class="btn btn-primary">Ödeme Ekle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="modal fade" id="assetModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="assetModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="assetModalLabel<?php echo $person['id']; ?>">Varlık Ekle: <?php echo htmlspecialchars($person['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="assetForm<?php echo $person['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="add_asset">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="asset_name_<?php echo $person['id']; ?>" class="form-label">Varlık Adı</label>
                                        <input type="text" name="asset_name" id="asset_name_<?php echo $person['id']; ?>" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="assigned_date_<?php echo $person['id']; ?>" class="form-label">Atama Tarihi</label>
                                        <input type="date" name="assigned_date" id="assigned_date_<?php echo $person['id']; ?>" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_<?php echo $person['id']; ?>" class="form-label">Açıklama</label>
                                        <textarea name="description" id="description_<?php echo $person['id']; ?>" class="form-control" placeholder="Varlık açıklamasını girin (opsiyonel)"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="button" class="btn btn-primary" onclick="confirmAsset(<?php echo $person['id']; ?>)">Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="leaveModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="leaveModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="leaveModalLabel<?php echo $person['id']; ?>">İzin Ekle: <?php echo htmlspecialchars($person['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="leaveForm<?php echo $person['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="add_leave">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="start_date_<?php echo $person['id']; ?>" class="form-label">Başlangıç Tarihi</label>
                                        <input type="date" name="start_date" id="start_date_<?php echo $person['id']; ?>" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="end_date_<?php echo $person['id']; ?>" class="form-label">Bitiş Tarihi</label>
                                        <input type="date" name="end_date" id="end_date_<?php echo $person['id']; ?>" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="reason_<?php echo $person['id']; ?>" class="form-label">Neden</label>
                                        <textarea name="reason" id="reason_<?php echo $person['id']; ?>" class="form-control" placeholder="İzin nedenini girin (opsiyonel)"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="button" class="btn btn-primary" onclick="confirmLeave(<?php echo $person['id']; ?>)">Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="overtimeModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="overtimeModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="overtimeModalLabel<?php echo $person['id']; ?>">Mesai Ekle: <?php echo htmlspecialchars($person['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST" id="overtimeForm<?php echo $person['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="add_overtime">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="work_date_<?php echo $person['id']; ?>" class="form-label">Çalışma Tarihi</label>
                                        <input type="date" name="work_date" id="work_date_<?php echo $person['id']; ?>" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="start_time_<?php echo $person['id']; ?>" class="form-label">Giriş Saati</label>
                                        <input type="time" name="start_time" id="start_time_<?php echo $person['id']; ?>" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="end_time_<?php echo $person['id']; ?>" class="form-label">Çıkış Saati</label>
                                        <input type="time" name="end_time" id="end_time_<?php echo $person['id']; ?>" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_<?php echo $person['id']; ?>" class="form-label">Açıklama</label>
                                        <textarea name="description" id="description_<?php echo $person['id']; ?>" class="form-control" placeholder="Mesai açıklamasını girin (opsiyonel)"></textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="button" class="btn btn-primary" onclick="confirmOvertime(<?php echo $person['id']; ?>)">Ekle</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="printModal<?php echo $person['id']; ?>" tabindex="-1" aria-labelledby="printModalLabel<?php echo $person['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="printModalLabel<?php echo $person['id']; ?>">Yazdır: <?php echo htmlspecialchars($person['name']); ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['personnel_token']; ?>">
                                    <input type="hidden" name="type" value="export_pdf">
                                    <input type="hidden" name="export_type" value="details">
                                    <input type="hidden" name="personnel_id" value="<?php echo $person['id']; ?>">
                                    <div class="mb-3">
                                        <label for="period_<?php echo $person['id']; ?>" class="form-label">Dönem Seçimi</label>
                                        <select name="period" id="period_<?php echo $person['id']; ?>" class="form-select" onchange="updatePrintForm(<?php echo $person['id']; ?>)" required>
                                            <option value="all">Tümü</option>
                                            <option value="monthly">Aylık</option>
                                            <option value="weekly">Haftalık</option>
                                        </select>
                                    </div>
                                    <div id="dateFields_<?php echo $person['id']; ?>" style="display:none;">
                                        <div class="mb-3">
                                            <label for="start_date_<?php echo $person['id']; ?>" class="form-label">Başlangıç Tarihi</label>
                                            <input type="date" name="start_date" id="start_date_<?php echo $person['id']; ?>" class="form-control">
                                        </div>
                                        <div class="mb-3">
                                            <label for="end_date_<?php echo $person['id']; ?>" class="form-label">Bitiş Tarihi</label>
                                            <input type="date" name="end_date" id="end_date_<?php echo $person['id']; ?>" class="form-control">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success">PDF Olarak Yazdır</button>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$content = ob_get_clean();
require_once 'template.php';
?>