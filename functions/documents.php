<?php
function uploadDocument($pdo, $title, $file, $category, $related_id, $related_table) {
    try {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Dosya yükleme hatası: " . $file['error']);
        }

        $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Sadece PDF, JPG veya PNG dosyaları yüklenebilir.");
        }

        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_path = $upload_dir . uniqid() . '_' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            throw new Exception("Dosya yüklenemedi.");
        }

        $stmt = $pdo->prepare("INSERT INTO documents (title, file_path, category, related_id, related_table) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $file_path, $category, $related_id, $related_table]);

        return $pdo->lastInsertId();
    } catch (Exception $e) {
        throw new Exception("Belge yüklenirken hata oluştu: " . $e->getMessage());
    }
}
?>