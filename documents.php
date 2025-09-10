<?php
session_start();
require_once 'config/db.php';
require_once 'functions/currency.php'; // For currency formatting, if needed

$title = 'Döküman Yönetimi';
$error = null;
$success = null;

// CSRF token for form submissions
if (!isset($_SESSION['document_token'])) {
    $_SESSION['document_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['document_token']) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Geçersiz CSRF token (documents), Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['document_token']]);
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['document_token'] = bin2hex(random_bytes(32)); // Refresh token

        // Check for 'type' key
        if (!isset($_POST['type'])) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Undefined array key 'type' in documents.php, POST: " . json_encode($_POST)]);
            throw new Exception("Geçersiz işlem tipi.");
        }

        $type = $_POST['type'];

        if ($type === 'add_document') {
            $related_table = $_POST['related_table'] ?? null;
            $related_id = $_POST['related_id'] ?? null;
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                throw new Exception("Lütfen bir dosya yükleyin.");
            }
            if (!$related_table || !in_array($related_table, ['invoices', 'sales_invoices', 'purchase_orders']) || !$related_id) {
                throw new Exception("Geçerli bir ilgili tablo ve ID seçin.");
            }
            // Validate file
            $file = $_FILES['file'];
            $allowed_types = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Sadece PDF, JPEG veya PNG dosyaları yüklenebilir.");
            }
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception("Dosya boyutu 5MB'ı aşamaz.");
            }
            // Check if related_id exists
            $table_map = [
                'invoices' => 'invoices',
                'sales_invoices' => 'sales_invoices',
                'purchase_orders' => 'purchase_orders'
            ];
            $stmt = $pdo->prepare("SELECT id FROM {$table_map[$related_table]} WHERE id = ?");
            $stmt->execute([$related_id]);
            if (!$stmt->fetch()) {
                throw new Exception("İlgili kayıt bulunamadı: $related_table ID $related_id");
            }
            // Handle file upload
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = time() . '_' . basename($file['name']);
            $file_path = $upload_dir . $file_name;
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                throw new Exception("Dosya yükleme başarısız.");
            }
            // Insert document
            $stmt = $pdo->prepare("INSERT INTO documents (file_name, file_path, related_table, related_id, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$file_name, $file_path, $related_table, $related_id, $description]);
            $document_id = $pdo->lastInsertId();
            // Log document creation
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Document $document_id created: $file_name, Related: $related_table#$related_id"]);
            $success = "Döküman eklendi.";
        } elseif ($type === 'edit_document') {
            $id = $_POST['document_id'] ?? null;
            $related_table = $_POST['related_table'] ?? null;
            $related_id = $_POST['related_id'] ?? null;
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            if (!$id || !$related_table || !in_array($related_table, ['invoices', 'sales_invoices', 'purchase_orders']) || !$related_id) {
                throw new Exception("Geçerli bir döküman ID, ilgili tablo ve ID girin.");
            }
            // Check if related_id exists
            $table_map = [
                'invoices' => 'invoices',
                'sales_invoices' => 'sales_invoices',
                'purchase_orders' => 'purchase_orders'
            ];
            $stmt = $pdo->prepare("SELECT id FROM {$table_map[$related_table]} WHERE id = ?");
            $stmt->execute([$related_id]);
            if (!$stmt->fetch()) {
                throw new Exception("İlgili kayıt bulunamadı: $related_table ID $related_id");
            }
            // Update document
            $stmt = $pdo->prepare("UPDATE documents SET related_table = ?, related_id = ?, description = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$related_table, $related_id, $description, $id]);
            // Log document update
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Document $id updated: Related: $related_table#$related_id"]);
            $success = "Döküman güncellendi.";
        } elseif ($type === 'delete_document') {
            $id = $_POST['document_id'] ?? null;
            if (!$id) {
                throw new Exception("Geçerli bir döküman ID girin.");
            }
            // Fetch file path to delete file
            $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            $document = $stmt->fetch();
            if ($document && file_exists($document['file_path'])) {
                unlink($document['file_path']);
            }
            // Delete document
            $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
            $stmt->execute([$id]);
            // Log document deletion
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Document $id deleted"]);
            $success = "Döküman silindi.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Hata: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Fetch documents with related numbers
$documents = $pdo->query("SELECT d.*, 
    CASE 
        WHEN d.related_table = 'invoices' THEN i.invoice_number
        WHEN d.related_table = 'sales_invoices' THEN si.invoice_number
        WHEN d.related_table = 'purchase_orders' THEN po.order_number
    END as related_number
    FROM documents d
    LEFT JOIN invoices i ON d.related_table = 'invoices' AND d.related_id = i.id
    LEFT JOIN sales_invoices si ON d.related_table = 'sales_invoices' AND d.related_id = si.id
    LEFT JOIN purchase_orders po ON d.related_table = 'purchase_orders' AND d.related_id = po.id
    ORDER BY d.created_at DESC")->fetchAll();

// Log fetched data
$stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
$stmt->execute(['info', "Fetched " . count($documents) . " documents"]);

// Fetch available related items for dropdowns
$invoices = $pdo->query("SELECT id, invoice_number FROM invoices ORDER BY invoice_number")->fetchAll();
$sales_invoices = $pdo->query("SELECT id, invoice_number FROM sales_invoices ORDER BY invoice_number")->fetchAll();
$purchase_orders = $pdo->query("SELECT id, order_number FROM purchase_orders ORDER BY order_number")->fetchAll();

// Template content
ob_start();
?>

<h2>Döküman Yönetimi</h2>

<!-- Error and Success Messages -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Add Document Button -->
<div class="mb-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDocumentModal">Yeni Döküman Ekle</button>
</div>

<!-- Documents List -->
<h3>Döküman Listesi</h3>
<?php if (empty($documents)): ?>
    <div class="alert alert-info">Kayıtlı döküman bulunmamaktadır.</div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Dosya Adı</th>
                        <th>İlgili Kayıt</th>
                        <th>Açıklama</th>
                        <th>Oluşturulma Tarihi</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $document): ?>
                        <tr>
                            <td><?php echo $document['id']; ?></td>
                            <td><a href="<?php echo htmlspecialchars($document['file_path']); ?>" target="_blank"><?php echo htmlspecialchars($document['file_name']); ?></a></td>
                            <td>
                                <?php
                                $related = $document['related_table'] . ' #' . ($document['related_number'] ?? $document['related_id']);
                                echo htmlspecialchars($related);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($document['description'] ?? '-'); ?></td>
                            <td><?php echo $document['created_at']; ?></td>
                            <td>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $document['id']; ?>">Düzenle</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bu dökümanı silmek istediğinizden emin misiniz?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['document_token']; ?>">
                                    <input type="hidden" name="type" value="delete_document">
                                    <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Sil</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Add Document Modal -->
<div class="modal fade" id="addDocumentModal" tabindex="-1" aria-labelledby="addDocumentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addDocumentModalLabel">Yeni Döküman Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addDocumentForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['document_token']; ?>">
                    <input type="hidden" name="type" value="add_document">
                    <div class="mb-3">
                        <label for="file" class="form-label">Dosya (PDF, JPEG, PNG)</label>
                        <input type="file" name="file" id="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="mb-3">
                        <label for="related_table" class="form-label">İlgili Tablo</label>
                        <select name="related_table" id="related_table" class="form-select" required onchange="updateRelatedIdOptions()">
                            <option value="" disabled selected>Tablo seçin</option>
                            <option value="invoices">Faturalar</option>
                            <option value="sales_invoices">Satış Faturaları</option>
                            <option value="purchase_orders">Satın Alma Siparişleri</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="related_id" class="form-label">İlgili Kayıt</label>
                        <select name="related_id" id="related_id" class="form-select" required>
                            <option value="" disabled selected>Önce tablo seçin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" class="form-control" placeholder="Döküman açıklamasını girin (opsiyonel)"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Document Modals -->
<?php foreach ($documents as $document): ?>
    <div class="modal fade" id="editModal<?php echo $document['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $document['id']; ?>" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel<?php echo $document['id']; ?>">Döküman Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['document_token']; ?>">
                        <input type="hidden" name="type" value="edit_document">
                        <input type="hidden" name="document_id" value="<?php echo $document['id']; ?>">
                        <div class="mb-3">
                            <label for="related_table_<?php echo $document['id']; ?>" class="form-label">İlgili Tablo</label>
                            <select name="related_table" id="related_table_<?php echo $document['id']; ?>" class="form-select" required onchange="updateRelatedIdOptions(<?php echo $document['id']; ?>)">
                                <option value="invoices" <?php echo $document['related_table'] === 'invoices' ? 'selected' : ''; ?>>Faturalar</option>
                                <option value="sales_invoices" <?php echo $document['related_table'] === 'sales_invoices' ? 'selected' : ''; ?>>Satış Faturaları</option>
                                <option value="purchase_orders" <?php echo $document['related_table'] === 'purchase_orders' ? 'selected' : ''; ?>>Satın Alma Siparişleri</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="related_id_<?php echo $document['id']; ?>" class="form-label">İlgili Kayıt</label>
                            <select name="related_id" id="related_id_<?php echo $document['id']; ?>" class="form-select" required>
                                <?php
                                $options = [];
                                if ($document['related_table'] === 'invoices') {
                                    $options = $invoices;
                                    $number_field = 'invoice_number';
                                } elseif ($document['related_table'] === 'sales_invoices') {
                                    $options = $sales_invoices;
                                    $number_field = 'invoice_number';
                                } elseif ($document['related_table'] === 'purchase_orders') {
                                    $options = $purchase_orders;
                                    $number_field = 'order_number';
                                }
                                foreach ($options as $option) {
                                    $selected = $option['id'] == $document['related_id'] ? 'selected' : '';
                                    echo "<option value='{$option['id']}' $selected>" . htmlspecialchars($option[$number_field]) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description_<?php echo $document['id']; ?>" class="form-label">Açıklama</label>
                            <textarea name="description" id="description_<?php echo $document['id']; ?>" class="form-control"><?php echo htmlspecialchars($document['description'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
    // Reset add document form when modal is closed
    document.getElementById('addDocumentModal').addEventListener('hidden.bs.modal', function () {
        console.log('Add document modal closed, resetting form');
        const form = document.getElementById('addDocumentForm');
        form.reset();
        form.querySelector('#related_id').innerHTML = '<option value="" disabled selected>Önce tablo seçin</option>';
    });

    // Log modal open for debugging
    document.getElementById('addDocumentModal').addEventListener('show.bs.modal', function () {
        console.log('Add document modal opened');
    });

    // Update related_id options based on selected related_table
    function updateRelatedIdOptions(documentId = null) {
        const selectId = documentId ? `related_id_${documentId}` : 'related_id';
        const tableSelectId = documentId ? `related_table_${documentId}` : 'related_table';
        const relatedTable = document.getElementById(tableSelectId).value;
        const relatedIdSelect = document.getElementById(selectId);
        relatedIdSelect.innerHTML = '<option value="" disabled selected>Kayıt seçin</option>';

        const optionsData = {
            'invoices': <?php echo json_encode($invoices); ?>,
            'sales_invoices': <?php echo json_encode($sales_invoices); ?>,
            'purchase_orders': <?php echo json_encode($purchase_orders); ?>
        };

        if (optionsData[relatedTable]) {
            optionsData[relatedTable].forEach(option => {
                const numberField = relatedTable === 'purchase_orders' ? 'order_number' : 'invoice_number';
                const opt = document.createElement('option');
                opt.value = option.id;
                opt.textContent = option[numberField];
                relatedIdSelect.appendChild(opt);
            });
        }
        console.log(`Updated related_id options for table: ${relatedTable}, documentId: ${documentId || 'new'}`);
    }
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>