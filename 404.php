<?php
session_start();
$page_title = "404 - Sayfa Bulunamadı";

ob_start();
?>

<div class="text-center py-5">
    <h1 class="display-1 fw-bold text-danger">404</h1>
    <h2 class="mb-4">Sayfa Bulunamadı</h2>
    <p class="lead mb-4">Üzgünüz, aradığınız sayfa mevcut değil veya taşınmış olabilir.</p>
    <a href="index.php" class="btn btn-primary btn-lg"><i class="fas fa-home me-2"></i> Ana Sayfaya Dön</a>
</div>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>