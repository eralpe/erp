<?php
require_once __DIR__ . '/cash.php';
require_once __DIR__ . '/categories.php';

function addSalaryAdvance($pdo, $personnel_id, $cash_id, $amount, $currency, $description) {
    try {
        // Get Maaş Avansı category ID
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Maaş Avansı' AND type = 'expense'");
        $stmt->execute();
        $category_id = $stmt->fetchColumn();
        if (!$category_id) {
            $category_id = addCategory($pdo, 'Maaş Avansı', 'expense');
        }

        // Add cash transaction
        $transaction_id = addCashTransaction($pdo, $cash_id, $amount, $currency, 'out', $category_id, $description);

        // Add salary advance record
        $stmt = $pdo->prepare("INSERT INTO salary_advances (personnel_id, transaction_id, amount, currency, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$personnel_id, $transaction_id, $amount, $currency, $description]);

        return $pdo->lastInsertId();
    } catch (Exception $e) {
        throw new Exception("Maaş avansı eklenirken hata oluştu: " . $e->getMessage());
    }
}

function updateSalaryAdvance($pdo, $advance_id, $cash_id, $amount, $currency, $description) {
    try {
        // Get existing advance details
        $stmt = $pdo->prepare("SELECT personnel_id, transaction_id FROM salary_advances WHERE id = ?");
        $stmt->execute([$advance_id]);
        $advance = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$advance) {
            throw new Exception("Maaş avansı bulunamadı.");
        }

        // Get Maaş Avansı category ID
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = 'Maaş Avansı' AND type = 'expense'");
        $stmt->execute();
        $category_id = $stmt->fetchColumn();

        // Update cash transaction
        updateCashTransaction($pdo, $advance['transaction_id'], $cash_id, $amount, $currency, 'out', $category_id, $description);

        // Update salary advance record
        $stmt = $pdo->prepare("UPDATE salary_advances SET amount = ?, currency = ?, description = ? WHERE id = ?");
        $stmt->execute([$amount, $currency, $description, $advance_id]);
    } catch (Exception $e) {
        throw new Exception("Maaş avansı güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function deleteSalaryAdvance($pdo, $advance_id) {
    try {
        // Get transaction_id from salary advance
        $stmt = $pdo->prepare("SELECT transaction_id FROM salary_advances WHERE id = ?");
        $stmt->execute([$advance_id]);
        $advance = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$advance) {
            throw new Exception("Maaş avansı bulunamadı.");
        }

        // Delete cash transaction
        deleteCashTransaction($pdo, $advance['transaction_id']);

        // Delete salary advance record
        $stmt = $pdo->prepare("DELETE FROM salary_advances WHERE id = ?");
        $stmt->execute([$advance_id]);
    } catch (Exception $e) {
        throw new Exception("Maaş avansı silinirken hata oluştu: " . $e->getMessage());
    }
}
?>