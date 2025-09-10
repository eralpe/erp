<?php
require_once 'config/db.php';
require_once 'vendor/autoload.php';
use TCPDF;

$date = date('Y-m-d', strtotime('05:22 PM +03')); // Bugün 09.09.2025 17:22
$start_date = date('Y-m-01', strtotime('-1 month', strtotime($date)));
$end_date = date('Y-m-t', strtotime('-1 month', strtotime($date)));

$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Company');
$pdf->SetTitle('Tüm Kategoriler Raporu');
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

$categories = ['personnel', 'cash_movements', 'products', 'stocks', 'warehouses', 'users', 'logs', 'debts', 'credits', 'sales', 'purchases', 'suppliers', 'customers', 'campaigns'];
foreach ($categories as $category) {
    $stmt = $pdo->prepare("SELECT * FROM $category WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
    $stmt->execute([$start_date, $end_date]);
    $data = $stmt->fetchAll();

    if ($data) {
        $pdf->AddPage();
        $pdf->writeHTML('<h1>' . ucfirst($category) . ' Raporu</h1><p>Tarih Aralığı: ' . $start_date . ' - ' . $end_date . '</p><table border="1" cellpadding="5">
                         <thead><tr><th>ID</th><th>Ad</th><th>Tarih</th><th>Açıklama</th></tr></thead><tbody>', true, false, true, false, '');
        foreach ($data as $row) {
            $pdf->writeHTML("<tr><td>{$row['id']}</td><td>" . htmlspecialchars($row['name'] ?? '-') . "</td><td>{$row['created_at']}</td><td>" . htmlspecialchars($row['description'] ?? '-') . "</td></tr>", true, false, true, false, '');
        }
        $pdf->writeHTML('</tbody></table>', true, false, true, false, '');
    }
}

$pdf->Output('full_report_' . $date . '.pdf', 'F');

// E-posta gönderimi için örnek (PHPMailer ile)
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
$mail = new PHPMailer();
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your_email@gmail.com';
$mail->Password = 'your_password';
$mail->SMTPSecure = 'tls';
$mail->Port = 587;
$mail->setFrom('your_email@gmail.com', 'Rapor Sistemi');
$mail->addAddress('recipient@example.com');
$mail->Subject = 'Aylık Rapor - ' . $date;
$mail->addAttachment('full_report_' . $date . '.pdf');
$mail->Body = 'Ekli dosyada aylık rapor bulunmaktadır.';
$mail->send();
?>