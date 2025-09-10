<?php
require_once __DIR__ . '/currency.php'; // getExchangeRate fonksiyonunu dahil et
require_once 'config/db.php';

function getPersonnel($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM personnel ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        throw new Exception("Personel listesi alınırken hata oluştu: " . $e->getMessage());
    }
}


function updateAdvance($pdo, $advance_id, $personel_id, $amount, $currency, $description, $category_id = null) {
    try {
        // Personel kontrolü
        $stmt = $pdo->prepare("SELECT id FROM personnel WHERE id = ?");
        $stmt->execute([$personel_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Geçersiz personel ID: $personel_id");
        }

        // Mevcut avansı al
        $stmt = $pdo->prepare("SELECT id FROM advances WHERE id = ?");
        $stmt->execute([$advance_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Avans bulunamadı.");
        }

        // Para birimi kontrolü
        if (!in_array($currency, ['TRY', 'USD', 'EUR'])) {
            throw new Exception("Geçersiz para birimi: $currency");
        }

        // Kategori kontrolü
        if ($category_id !== null) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Geçersiz kategori ID: $category_id");
            }
        }

        // Döviz kuru ile amount_try hesaplama
        $rate = getExchangeRate($pdo, $currency);
        $amount_try = $amount * $rate;

        // Avansı güncelle
        $stmt = $pdo->prepare("UPDATE advances SET personel_id = ?, amount = ?, currency = ?, amount_try = ?, description = ?, category_id = ? WHERE id = ?");
        $stmt->execute([$personel_id, $amount, $currency, $amount_try, $description, $category_id, $advance_id]);

    } catch (Exception $e) {
        throw new Exception("Avans güncellenirken hata oluştu: " . $e->getMessage());
    }
}

function deleteAdvance($pdo, $advance_id) {
    try {
        // Avansı al
        $stmt = $pdo->prepare("SELECT id FROM advances WHERE id = ?");
        $stmt->execute([$advance_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Avans bulunamadı (ID: $advance_id).");
        }

        // Avansı sil
        $stmt = $pdo->prepare("DELETE FROM advances WHERE id = ?");
        $stmt->execute([$advance_id]);
    } catch (Exception $e) {
        throw new Exception("Avans silinirken hata oluştu: " . $e->getMessage());
    }
}

function addAdvance($pdo, $personel_id, $amount, $currency, $description, $category_id = null) {
    try {
        // Personel kontrolü
        $stmt = $pdo->prepare("SELECT id, monthly_salary FROM personnel WHERE id = ?");
        $stmt->execute([$personel_id]);
        $personel = $stmt->fetch();
        if (!$personel) {
            throw new Exception("Geçersiz personel ID: $personel_id");
        }

        // Para birimi kontrolü
        if (!in_array($currency, ['TRY', 'USD', 'EUR'])) {
            throw new Exception("Geçersiz para birimi: $currency");
        }

        // Kategori kontrolü
        if ($category_id !== null) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            if (!$stmt->fetch()) {
                throw new Exception("Geçersiz kategori ID: $category_id");
            }
        }

        // Döviz kuru ile amount_try hesaplama
        $rate = getExchangeRate($pdo, $currency);
        $amount_try = $amount * $rate;

        // Avans ekleme
        $stmt = $pdo->prepare("INSERT INTO advances (personel_id, amount, currency, amount_try, description, category_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$personel_id, $amount, $currency, $amount_try, $description, $category_id]);
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['add_advance', "Personel ID: $personel_id, Amount: $amount, Currency: $currency, Category ID: " . ($category_id ?? 'null')]);
    } catch (Exception $e) {
        throw new Exception("Avans eklenirken hata oluştu: " . $e->getMessage());
    }
}

function markAdvanceDeducted($pdo, $advance_id) {
    try {
        $stmt = $pdo->prepare("UPDATE personnel_advances SET status = 'deducted' WHERE id = ?");
        $stmt->execute([$advance_id]);
    } catch (Exception $e) {
        throw new Exception("Avans durumu güncellenirken hata oluştu: " . $e->getMessage());
    }
}
?>