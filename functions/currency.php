<?php
function getExchangeRate($pdo, $currency_code) {
    try {
        if (empty($currency_code)) {
            throw new Exception("Para birimi belirtilmedi.");
        }
        if ($currency_code === 'TRY') {
            return 1.0; // TRY için kur 1
        }
        if (!in_array($currency_code, ['USD', 'EUR'])) {
            throw new Exception("Desteklenmeyen para birimi: $currency_code");
        }

        $stmt = $pdo->prepare("SELECT rate FROM exchange_rates WHERE currency_code = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->execute([$currency_code]);
        $rate = $stmt->fetchColumn();

        if ($rate === false) {
            throw new Exception("Döviz kuru bulunamadı: $currency_code");
        }

        return (float)$rate;
    } catch (Exception $e) {
        throw new Exception("Döviz kuru alınırken hata oluştu: " . $e->getMessage());
    }
}
?>