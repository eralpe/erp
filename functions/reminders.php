<?php
function addReminder($pdo, $title, $due_date, $type, $related_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO reminders (title, due_date, type, related_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $due_date, $type, $related_id]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        throw new Exception("Hatırlatıcı eklenirken hata oluştu: " . $e->getMessage());
    }
}
function getUpcomingReminders($pdo, $days) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                'Satın Alma Siparişi' AS title,
                COALESCE(s.name, 'Bilinmeyen Tedarikçi') AS related_name,
                po.order_number AS related_number,
                po.amount_try AS amount,
                po.delivery_date AS due_date
            FROM purchase_orders po
            LEFT JOIN suppliers s ON po.supplier_id = s.id
            WHERE po.delivery_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND po.delivery_date >= CURDATE()
            AND po.delivery_status = 'pending'
            UNION
            SELECT 
                'Satış Faturası' AS title,
                COALESCE(c.name, 'Bilinmeyen Müşteri') AS related_name,
                si.invoice_number AS related_number,
                si.amount_try AS amount,
                si.due_date AS due_date
            FROM sales_invoices si
            LEFT JOIN customers c ON si.customer_id = c.id
            WHERE si.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND si.due_date >= CURDATE()
            AND si.status = 'pending'
            ORDER BY due_date ASC
        ");
        $stmt->execute([$days, $days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Hatırlatıcılar alınırken hata oluştu: " . $e->getMessage());
    }
}

function markReminderAsSent($pdo, $reminder_id) {
    $stmt = $pdo->prepare("UPDATE reminders SET status = 'sent', notification_date = NOW() WHERE id = ?");
    $stmt->execute([$reminder_id]);
}

function markReminderAsDismissed($pdo, $reminder_id) {
    $stmt = $pdo->prepare("UPDATE reminders SET status = 'dismissed' WHERE id = ?");
    $stmt->execute([$reminder_id]);
}

function sendReminderEmail($pdo, $reminder_id) {
    $stmt = $pdo->prepare("
        SELECT r.*, 
               CASE 
                   WHEN r.type = 'credit' THEN c.bank_name 
                   WHEN r.type = 'invoice' THEN s.name 
               END as name,
               CASE 
                   WHEN r.type = 'credit' THEN c.amount 
                   WHEN r.type = 'invoice' THEN i.total_amount 
               END as amount,
               CASE 
                   WHEN r.type = 'credit' THEN c.currency 
                   WHEN r.type = 'invoice' THEN i.currency 
               END as currency
        FROM reminders r
        LEFT JOIN credits c ON r.type = 'credit' AND r.related_id = c.id
        LEFT JOIN invoices i ON r.type = 'invoice' AND r.related_id = i.id
        LEFT JOIN suppliers s ON i.type = 'purchase' AND i.entity_id = s.id
        WHERE r.id = ?
    ");
    $stmt->execute([$reminder_id]);
    $reminder = $stmt->fetch();

    if ($reminder) {
        $to = "your_email@example.com"; // Üretimde dinamik bir e-posta adresi kullanılmalı
        $subject = "Ödeme Hatırlatıcı: " . ($reminder['type'] == 'credit' ? 'Kredi' : 'Fatura');
        $message = "
            Merhaba,\n\n
            Vade tarihi yaklaşan bir ödeme bulunmaktadır:\n
            Tür: " . ($reminder['type'] == 'credit' ? 'Kredi' : 'Fatura') . "\n
            İsim: " . htmlspecialchars($reminder['name']) . "\n
            Tutar: " . number_format($reminder['amount'], 2) . " " . $reminder['currency'] . "\n
            Vade Tarihi: " . $reminder['due_date'] . "\n\n
            Lütfen ödeme işlemlerini kontrol ediniz.\n
            Ön Muhasebe Sistemi
        ";
        $headers = "From: no-reply@yourdomain.com\r\n";
        if (mail($to, $subject, $message, $headers)) {
            markReminderAsSent($pdo, $reminder_id);
            return true;
        }
        return false;
    }
    return false;
}
?>