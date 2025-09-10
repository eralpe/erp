<?php
require_once __DIR__ . '/currency.php'; // Ensure currency.php is included

function getCashAccounts($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM cash_accounts ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kasa hesapları alınırken hata oluştu: " . $e->getMessage());
    }
}



function addCashTransaction($pdo, $account_id, $amount, $currency, $type, $category_id, $description, $transaction_date) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT balance, currency FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new Exception("Kasa hesabı bulunamadı.");

        $amount_try = convertToTRY($amount, $currency, $account['currency']);
        if ($type === 'expense') {
            if ($account['balance'] < $amount_try) throw new Exception("Yetersiz bakiye.");
            $new_balance = $account['balance'] - $amount_try;
        } elseif ($type === 'income') {
            $new_balance = $account['balance'] + $amount_try;
        } else {
            throw new Exception("Geçersiz işlem türü.");
        }

        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $account_id]);

        $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, amount, currency, amount_try, type, category_id, description, transaction_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$account_id, $amount, $currency, $amount_try, $type, $category_id, $description, $transaction_date]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getCashTransactions($pdo, $cash_account_id = null) {
    try {
        $query = "SELECT ct.*, ca.name 
                 FROM cash_transactions ct 
                 LEFT JOIN cash_accounts ca ON ct.cash_id = ca.id";
        if ($cash_account_id !== null) {
            $query .= " WHERE ct.cash_id = :cash_account_id";
        }
        $query .= " ORDER BY ct.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        if ($cash_account_id !== null) {
            $stmt->bindParam(':cash_account_id', $cash_account_id, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kasa hareketleri alınırken hata oluştu: " . $e->getMessage());
    }
}

function getCashBalance($pdo) {
    try {
        // Tablo yapısını kontrol et
        $stmt = $pdo->query("SHOW COLUMNS FROM cash_transactions LIKE 'transaction_type'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Tabloda 'transaction_type' sütunu bulunmuyor.");
        }
        
        $stmt = $pdo->query("
            SELECT SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE -amount END) AS balance
            FROM cash_transactions
        ");
        return $stmt->fetch(PDO::FETCH_ASSOC)['balance'] ?? 0;
    } catch (Exception $e) {
        throw new Exception("Kasa bakiyesi hesaplanırken hata oluştu: " . $e->getMessage());
    }
}

function getCashAccountById($pdo, $account_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kasa bilgisi alınırken hata oluştu: " . $e->getMessage());
    }
}

function addCashAccount($pdo, $account_name, $description) {
    try {
        if (empty($account_name)) {
            throw new Exception("Kasa adı zorunludur.");
        }
        $stmt = $pdo->prepare("
            INSERT INTO cash_accounts (name, description)
            VALUES (?, ?)
        ");
        $stmt->execute([$account_name, $description]);
    } catch (Exception $e) {
        throw new Exception("Kasa eklenirken hata oluştu: " . $e->getMessage());
    }
}

function updateCashAccount($pdo, $account_id, $account_name, $description) {
    try {
        if (empty($account_name)) {
            throw new Exception("Kasa adı zorunludur.");
        }
        $stmt = $pdo->prepare("
            UPDATE cash_accounts
            SET name = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([$account_name, $description, $account_id]);
    } catch (Exception $e) {
        throw new Exception("Kasa güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function getCashTransactionById($pdo, $transaction_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT ct.*, ca.name 
            FROM cash_transactions ct 
            LEFT JOIN cash_accounts ca ON ct.cash_id = ca.id 
            WHERE ct.id = ?
        ");
        $stmt->execute([$transaction_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Kasa işlemi alınırken hata oluştu: " . $e->getMessage());
    }
}

function updateCashTransaction($pdo, $transaction_id, $account_id, $amount, $currency, $type, $category_id, $description, $transaction_date) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT t.amount, t.currency, t.amount_try, t.type, a.balance FROM cash_transactions t JOIN cash_accounts a ON t.account_id = a.id WHERE t.id = ?");
        $stmt->execute([$transaction_id]);
        $old_transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$old_transaction) throw new Exception("İşlem bulunamadı.");

        $stmt = $pdo->prepare("SELECT balance, currency FROM cash_accounts WHERE id = ?");
        $stmt->execute([$account_id]);
        $new_account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$new_account) throw new Exception("Kasa hesabı bulunamadı.");

        $new_amount_try = convertToTRY($amount, $currency, $new_account['currency']);
        $balance_adjustment = 0;
        if ($old_transaction['account_id'] == $account_id) {
            $balance_adjustment = $old_transaction['type'] === 'income' ? $old_transaction['amount_try'] : ($old_transaction['type'] === 'expense' ? -$old_transaction['amount_try'] : 0);
        }

        if ($type === 'expense') {
            if ($new_account['balance'] - $balance_adjustment + $new_amount_try < 0) throw new Exception("Yetersiz bakiye.");
            $new_balance = $new_account['balance'] - $balance_adjustment - $new_amount_try;
        } elseif ($type === 'income') {
            $new_balance = $new_account['balance'] - $balance_adjustment + $new_amount_try;
        } else {
            throw new Exception("Geçersiz işlem türü.");
        }

        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $account_id]);

        $stmt = $pdo->prepare("UPDATE cash_transactions SET account_id = ?, amount = ?, currency = ?, amount_try = ?, type = ?, category_id = ?, description = ?, transaction_date = ? WHERE id = ?");
        $stmt->execute([$account_id, $amount, $currency, $new_amount_try, $type, $category_id, $description, $transaction_date, $transaction_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function addTransfer($pdo, $from_account_id, $to_account_id, $amount, $currency, $description, $category_id, $transaction_date) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT balance, currency FROM cash_accounts WHERE id = ?");
        $stmt->execute([$from_account_id]);
        $from_account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$from_account || $from_account['balance'] < convertToTRY($amount, $currency, $from_account['currency'])) throw new Exception("Kaynak kasa bakiyesi yetersiz.");

        $stmt->execute([$to_account_id]);
        $to_account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$to_account) throw new Exception("Hedef kasa bulunamadı.");

        $amount_try_from = convertToTRY($amount, $currency, $from_account['currency']);
        $amount_try_to = convertToTRY($amount, $currency, $to_account['currency']);
        $new_from_balance = $from_account['balance'] - $amount_try_from;
        $new_to_balance = $to_account['balance'] + $amount_try_to;

        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_from_balance, $from_account_id]);
        $stmt->execute([$new_to_balance, $to_account_id]);

        $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, amount, currency, amount_try, type, category_id, description, transaction_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$from_account_id, $amount, $currency, -$amount_try_from, 'transfer_out', $category_id, $description, $transaction_date]);
        $stmt->execute([$to_account_id, $amount, $currency, $amount_try_to, 'transfer_in', $category_id, $description, $transaction_date]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function deleteCashTransaction($pdo, $transaction_id) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT account_id, amount_try, type FROM cash_transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$transaction) throw new Exception("İşlem bulunamadı.");

        $stmt = $pdo->prepare("SELECT balance FROM cash_accounts WHERE id = ?");
        $stmt->execute([$transaction['account_id']]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$account) throw new Exception("Kasa hesabı bulunamadı.");

        $new_balance = $account['balance'];
        if ($transaction['type'] === 'income') $new_balance -= $transaction['amount_try'];
        elseif ($transaction['type'] === 'expense') $new_balance += $transaction['amount_try'];

        $stmt = $pdo->prepare("UPDATE cash_accounts SET balance = ? WHERE id = ?");
        $stmt->execute([$new_balance, $transaction['account_id']]);

        $stmt = $pdo->prepare("DELETE FROM cash_transactions WHERE id = ?");
        $stmt->execute([$transaction_id]);

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function convertToTRY($amount, $currency, $account_currency) {
    // Basit bir dönüşüm, gerçek kurlar için API kullanılmalı
    $rates = ['USD' => 34.5, 'EUR' => 37.0, 'TRY' => 1.0]; // Örnek kurlar
    $base_amount = $amount * $rates[$currency];
    if ($account_currency !== 'TRY') {
        $base_amount /= $rates[$account_currency];
    }
    return $base_amount;
}

?>