<?php
require_once __DIR__ . '/cash.php';
require_once __DIR__ . '/categories.php';

function addInvoice($pdo, $supplier_id, $invoice_number, $amount, $currency, $issue_date, $due_date, $description) {
    try {
        $rate = getExchangeRate($pdo, $currency);
        $amount_try = $amount * $rate;

        $stmt = $pdo->prepare("INSERT INTO invoices (supplier_id, invoice_number, amount, currency, amount_try, issue_date, due_date, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$supplier_id, $invoice_number, $amount, $currency, $amount_try, $issue_date, $due_date, $description]);

        $invoice_id = $pdo->lastInsertId();

        // Update supplier balance
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount_try, $supplier_id]);

        // Add reminder for due date
        $stmt = $pdo->prepare("INSERT INTO reminders (title, due_date, type, related_id) VALUES (?, ?, 'invoice', ?)");
        $stmt->execute(["Fatura Ödemesi: $invoice_number", $due_date, $invoice_id]);

        return $invoice_id;
    } catch (Exception $e) {
        throw new Exception("Fatura eklenirken hata oluştu: " . $e->getMessage());
    }
}

function addInvoicePayment($pdo, $invoice_id, $cash_id, $amount, $currency, $description) {
    try {
        // Check remaining balance
        $stmt = $pdo->prepare("SELECT amount_try - COALESCE(SUM(ip.amount * er.rate), 0) as remaining
                               FROM invoices i
                               LEFT JOIN invoice_payments ip ON i.id = ip.invoice_id
                               LEFT JOIN exchange_rates er ON ip.currency = er.currency_code
                               WHERE i.id = ?");
        $stmt->execute([$invoice_id]);
        $remaining = $stmt->fetchColumn();
        $payment_amount_try = $amount * getExchangeRate($pdo, $currency);
        if ($payment_amount_try > $remaining) {
            throw new Exception("Ödeme miktarı kalan fatura tutarını aşıyor: " . number_format($remaining, 2) . " TRY");
        }

        // Check cash account balance
        $stmt = $pdo->prepare("SELECT balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$cash_id]);
        $cash_balance = $stmt->fetchColumn();
        if ($cash_balance < $payment_amount_try) {
            throw new Exception("Kasa bakiyesi yetersiz: " . number_format($cash_balance, 2) . " TRY");
        }

        // Get Fatura Ödemesi category ID
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Fatura Ödemesi' AND type = 'expense'");
        $stmt->execute();
        $category_id = $stmt->fetchColumn();
        if (!$category_id) {
            $category_id = addCategory($pdo, 'Fatura Ödemesi', 'expense');
        }

        // Get supplier_id from invoice
        $stmt = $pdo->prepare("SELECT supplier_id, amount_try FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) {
            throw new Exception("Fatura bulunamadı.");
        }

        // Add cash transaction
        $transaction_id = addCashTransaction($pdo, $cash_id, $amount, $currency, 'out', $category_id, $description);

        // Add invoice payment record
        $stmt = $pdo->prepare("INSERT INTO invoice_payments (invoice_id, transaction_id, amount, currency, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_id, $transaction_id, $amount, $currency, $description]);

        // Update supplier balance
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$payment_amount_try, $invoice['supplier_id']]);

        return $pdo->lastInsertId();
    } catch (Exception $e) {
        throw new Exception("Fatura ödemesi eklenirken hata oluştu: " . $e->getMessage());
    }
}

function payFullInvoice($pdo, $invoice_id, $cash_id) {
    try {
        // Get invoice details
        $stmt = $pdo->prepare("SELECT supplier_id, amount, currency, description, amount_try - COALESCE(SUM(ip.amount * er.rate), 0) as remaining
                               FROM invoices i
                               LEFT JOIN invoice_payments ip ON i.id = ip.invoice_id
                               LEFT JOIN exchange_rates er ON ip.currency = er.currency_code
                               WHERE i.id = ?
                               GROUP BY i.id");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$invoice) {
            throw new Exception("Fatura bulunamadı.");
        }

        if ($invoice['remaining'] <= 0) {
            throw new Exception("Fatura zaten tamamen ödenmiş.");
        }

        // Add payment for remaining amount
        return addInvoicePayment($pdo, $invoice_id, $cash_id, $invoice['remaining'] / getExchangeRate($pdo, $invoice['currency']), $invoice['currency'], $invoice['description']);
    } catch (Exception $e) {
        throw new Exception("Fatura tam ödeme sırasında hata oluştu: " . $e->getMessage());
    }
}

function payMultipleInvoices($pdo, $invoice_ids, $cash_id) {
    try {
        $pdo->beginTransaction();
        foreach ($invoice_ids as $invoice_id) {
            payFullInvoice($pdo, $invoice_id, $cash_id);
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw new Exception("Toplu ödeme sırasında hata oluştu: " . $e->getMessage());
    }
}

function updateInvoicePayment($pdo, $payment_id, $cash_id, $amount, $currency, $description) {
    try {
        // Get existing payment details
        $stmt = $pdo->prepare("SELECT ip.invoice_id, ip.transaction_id, ip.amount, ip.currency, i.supplier_id FROM invoice_payments ip JOIN invoices i ON ip.invoice_id = i.id WHERE ip.id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            throw new Exception("Fatura ödemesi bulunamadı.");
        }

        // Check remaining balance excluding this payment
        $stmt = $pdo->prepare("SELECT amount_try - COALESCE(SUM(ip2.amount * er.rate), 0) as remaining
                               FROM invoices i
                               LEFT JOIN invoice_payments ip2 ON i.id = ip2.invoice_id AND ip2.id != ?
                               LEFT JOIN exchange_rates er ON ip2.currency = er.currency_code
                               WHERE i.id = ?");
        $stmt->execute([$payment_id, $payment['invoice_id']]);
        $remaining = $stmt->fetchColumn();
        $payment_amount_try = $amount * getExchangeRate($pdo, $currency);
        if ($payment_amount_try > $remaining) {
            throw new Exception("Ödeme miktarı kalan fatura tutarını aşıyor: " . number_format($remaining, 2) . " TRY");
        }

        // Check cash account balance
        $stmt = $pdo->prepare("SELECT balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$cash_id]);
        $cash_balance = $stmt->fetchColumn();
        if ($cash_balance < $payment_amount_try) {
            throw new Exception("Kasa bakiyesi yetersiz: " . number_format($cash_balance, 2) . " TRY");
        }

        // Get Fatura Ödemesi category ID
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Fatura Ödemesi' AND type = 'expense'");
        $stmt->execute();
        $category_id = $stmt->fetchColumn();

        // Revert old supplier balance
        $old_rate = getExchangeRate($pdo, $payment['currency']);
        $old_amount_try = $payment['amount'] * $old_rate;
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$old_amount_try, $payment['supplier_id']]);

        // Update cash transaction
        updateCashTransaction($pdo, $payment['transaction_id'], $cash_id, $amount, $currency, 'out', $category_id, $description);

        // Update invoice payment record
        $stmt = $pdo->prepare("UPDATE invoice_payments SET amount = ?, currency = ?, description = ? WHERE id = ?");
        $stmt->execute([$amount, $currency, $description, $payment_id]);

        // Update supplier balance
        $new_rate = getExchangeRate($pdo, $currency);
        $new_amount_try = $amount * $new_rate;
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance - ? WHERE id = ?");
        $stmt->execute([$new_amount_try, $payment['supplier_id']]);
    } catch (Exception $e) {
        throw new Exception("Fatura ödemesi güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function deleteInvoicePayment($pdo, $payment_id) {
    try {
        // Get payment details
        $stmt = $pdo->prepare("SELECT ip.transaction_id, ip.amount, ip.currency, i.supplier_id FROM invoice_payments ip JOIN invoices i ON ip.invoice_id = i.id WHERE ip.id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$payment) {
            throw new Exception("Fatura ödemesi bulunamadı.");
        }

        // Revert supplier balance
        $rate = getExchangeRate($pdo, $payment['currency']);
        $amount_try = $payment['amount'] * $rate;
        $stmt = $pdo->prepare("UPDATE suppliers SET balance = balance + ? WHERE id = ?");
        $stmt->execute([$amount_try, $payment['supplier_id']]);

        // Delete cash transaction
        deleteCashTransaction($pdo, $payment['transaction_id']);

        // Delete invoice payment record
        $stmt = $pdo->prepare("DELETE FROM invoice_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
    } catch (Exception $e) {
        throw new Exception("Fatura ödemesi silinirken hata oluştu: " . $e->getMessage());
    }
}
?>