<?php
require_once __DIR__ . '/cash.php';
require_once __DIR__ . '/currency.php';

function addInvoicePayment($pdo, $invoice_id, $account_id, $amount, $currency, $payment_date, $description = null) {
    try {
        // Fatura kontrolü
        $stmt = $pdo->prepare("SELECT amount, currency, status, invoice_number FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            throw new Exception("Geçersiz fatura ID: $invoice_id");
        }

        // Kasa hesabı kontrolü
        $stmt = $pdo->prepare("SELECT id, currency, balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch();
        if (!$account) {
            throw new Exception("Geçersiz kasa hesabı ID: $account_id");
        }

        // Para birimi kontrolü
        if (!in_array($currency, ['TRY', 'USD', 'EUR'])) {
            throw new Exception("Geçersiz para birimi: $currency");
        }

        // Döviz kuru ile amount_try hesaplama
        $rate = getExchangeRate($pdo, $currency);
        $amount_try = $amount * $rate;

        // Bakiye kontrolü
        if ($account['balance'] < $amount_try) {
            throw new Exception("Yetersiz bakiye: $account_id, Gerekli: $amount_try TRY, Mevcut: {$account['balance']} TRY");
        }

        // Ödeme ekleme
        $stmt = $pdo->prepare("INSERT INTO invoice_payments (invoice_id, account_id, amount, currency, amount_try, payment_date, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $account_id, $amount, $currency, $amount_try, $payment_date, $description]);

        // Kasa işlemi ekleme (gider olarak)
        addCashTransaction($pdo, $account_id, $amount, $currency, 'expense', null, $description ?: "Fatura Ödemesi: INV{$invoice['invoice_number']}");

        // Fatura durumunu güncelleme
        $stmt = $pdo->prepare("SELECT SUM(amount_try) as total_paid FROM invoice_payments WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $total_paid = $stmt->fetchColumn();
        $invoice_rate = getExchangeRate($pdo, $invoice['currency']);
        $invoice_amount_try = $invoice['amount'] * $invoice_rate;

        $status = $total_paid >= $invoice_amount_try ? 'paid' : ($total_paid > 0 ? 'partially_paid' : 'pending');
        $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        $stmt->execute([$status, $invoice_id]);

        // Log ekleme
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['add_payment', "Invoice ID: $invoice_id, Amount: $amount $currency, Amount TRY: $amount_try, Account ID: $account_id, Payment Date: $payment_date"]);
    } catch (Exception $e) {
        // Hata loglama
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['add_payment_error', "Hata: {$e->getMessage()}, Invoice ID: $invoice_id, Account ID: $account_id"]);
        throw new Exception("Fatura ödemesi eklenirken hata oluştu: " . $e->getMessage());
    }
}

function addBulkPayment($pdo, $invoice_ids, $account_id, $payment_date, $description = null) {
    try {
        $pdo->beginTransaction();

        // Girdi doğrulaması
        if (empty($invoice_ids)) {
            throw new Exception("Hiçbir fatura seçilmedi.");
        }
        if (!is_numeric($account_id)) {
            throw new Exception("Geçersiz kasa hesabı ID: $account_id");
        }
        if (empty($payment_date) || !strtotime($payment_date)) {
            throw new Exception("Geçersiz ödeme tarihi: $payment_date");
        }

        // Kasa hesabı kontrolü
        $stmt = $pdo->prepare("SELECT id, currency, balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch();
        if (!$account) {
            throw new Exception("Kasa hesabı bulunamadı: $account_id");
        }

        $total_required_try = 0;
        $invoices_to_pay = [];

        // Her fatura için kalan tutarı hesapla
        foreach ($invoice_ids as $invoice_id) {
            if (!is_numeric($invoice_id)) {
                throw new Exception("Geçersiz fatura ID: $invoice_id");
            }

            $stmt = $pdo->prepare("SELECT amount, currency, status, invoice_number FROM invoices WHERE id = ?");
            $stmt->execute([$invoice_id]);
            $invoice = $stmt->fetch();
            if (!$invoice) {
                throw new Exception("Fatura bulunamadı: $invoice_id");
            }

            $stmt = $pdo->prepare("SELECT SUM(amount_try) as total_paid FROM invoice_payments WHERE invoice_id = ?");
            $stmt->execute([$invoice_id]);
            $total_paid = $stmt->fetchColumn() ?: 0;
            $invoice_rate = getExchangeRate($pdo, $invoice['currency']);
            $invoice_amount_try = $invoice['amount'] * $invoice_rate;
            $remaining_amount_try = $invoice_amount_try - $total_paid;

            if ($remaining_amount_try <= 0) {
                // Log: Fatura zaten ödenmiş
                $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
                $stmt->execute(['bulk_payment_skip', "Fatura zaten ödenmiş: $invoice_id"]);
                continue;
            }

            $remaining_amount = $remaining_amount_try / $invoice_rate; // Ödeme miktarı fatura para biriminde
            $invoices_to_pay[] = [
                'id' => $invoice_id,
                'amount' => $remaining_amount,
                'currency' => $invoice['currency'],
                'invoice_number' => $invoice['invoice_number']
            ];
            $total_required_try += $remaining_amount_try;
        }

        // Toplam bakiye kontrolü
        if (empty($invoices_to_pay)) {
            throw new Exception("Ödenecek fatura bulunamadı.");
        }
        if ($account['balance'] < $total_required_try) {
            throw new Exception("Yetersiz bakiye: Toplam gerekli tutar $total_required_try TRY, mevcut bakiye {$account['balance']} TRY");
        }

        // Her fatura için ödeme ekle
        foreach ($invoices_to_pay as $invoice) {
            addInvoicePayment($pdo, $invoice['id'], $account_id, $invoice['amount'], $invoice['currency'], $payment_date, $description ?: "Toplu Fatura Ödemesi: INV{$invoice['invoice_number']}");
        }

        // Log ekleme
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['bulk_payment', "Invoice IDs: " . implode(',', $invoice_ids) . ", Total Amount TRY: $total_required_try, Account ID: $account_id, Payment Date: $payment_date"]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        // Hata loglama
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['bulk_payment_error', "Hata: {$e->getMessage()}, Invoice IDs: " . implode(',', $invoice_ids) . ", Account ID: $account_id"]);
        throw new Exception("Toplu ödeme sırasında hata oluştu: " . $e->getMessage());
    }
}
?>