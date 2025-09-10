<?php
session_start();
require_once 'config/db.php';
require_once 'functions/invoices.php';

// Oturum kontrolü ve CSRF token'ları oluştur
if (!isset($_SESSION['single_payment_tokens'])) {
    $_SESSION['single_payment_tokens'] = [];
}
if (!isset($_SESSION['bulk_payment_token'])) {
    $_SESSION['bulk_payment_token'] = bin2hex(random_bytes(32));
}

$title = 'Tedarikçi Detayları';
$error = null;
$success = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = "Geçersiz tedarikçi ID.";
    $supplier = null;
} else {
    $supplier_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->execute([$supplier_id]);
    $supplier = $stmt->fetch();
    if (!$supplier) {
        $error = "Tedarikçi bulunamadı.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (isset($_POST['add_payment'])) {
            $invoice_id = $_POST['invoice_id'];
            if (!isset($_POST['csrf_token']) || !isset($_SESSION['single_payment_tokens'][$invoice_id]) || $_POST['csrf_token'] !== $_SESSION['single_payment_tokens'][$invoice_id]) {
                // Hata loglama
                $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
                $stmt->execute(['error', "Geçersiz CSRF token (tek ödeme), Invoice ID: $invoice_id, Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . ($_SESSION['single_payment_tokens'][$invoice_id] ?? 'none')]);
                throw new Exception("Geçersiz CSRF token (tek ödeme).");
            }
            unset($_SESSION['single_payment_tokens'][$invoice_id]); // Token'ı tüket
        } elseif (isset($_POST['bulk_payment'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['bulk_payment_token']) {
                $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
                $stmt->execute(['error', "Geçersiz CSRF token (toplu ödeme), Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['bulk_payment_token']]);
                throw new Exception("Geçersiz CSRF token (toplu ödeme).");
            }
            $_SESSION['bulk_payment_token'] = bin2hex(random_bytes(32)); // Yeni token oluştur
        } elseif (isset($_POST['update_supplier']) || isset($_POST['delete_supplier'])) {
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['bulk_payment_token']) {
                $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
                $stmt->execute(['error', "Geçersiz CSRF token (tedarikçi işlemi), Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['bulk_payment_token']]);
                throw new Exception("Geçersiz CSRF token (tedarikçi işlemi).");
            }
        }

        if (isset($_POST['update_supplier'])) {
            $id = $_POST['supplier_id'];
            $name = trim($_POST['name']);
            $contact = !empty($_POST['contact']) ? trim($_POST['contact']) : null;
            $address = !empty($_POST['address']) ? trim($_POST['address']) : null;
            $title = !empty($_POST['title']) ? trim($_POST['title']) : null;

            if (empty($name)) {
                throw new Exception("Tedarikçi adı zorunludur.");
            }

            $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact = ?, address = ?, title = ? WHERE id = ?");
            $stmt->execute([$name, $contact, $address, $title, $id]);
            $success = "Tedarikçi güncellendi.";
            header('Location: supplier_details.php?id=' . $id);
            exit;
        } elseif (isset($_POST['delete_supplier'])) {
            $id = $_POST['supplier_id'];
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE supplier_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception("Bu tedarikçiye ait faturalar olduğu için silinemez.");
            }
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Tedarikçi silindi.";
            header('Location: suppliers.php');
            exit;
        } elseif (isset($_POST['add_payment'])) {
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            addInvoicePayment($pdo, $_POST['invoice_id'], $_POST['account_id'], $_POST['amount'], $_POST['currency'], $_POST['payment_date'], $description);
            $success = "Ödeme eklendi.";
            header('Location: supplier_details.php?id=' . $supplier_id);
            exit;
        } elseif (isset($_POST['bulk_payment'])) {
            $invoice_ids = $_POST['invoice_ids'] ?? [];
            if (empty($invoice_ids)) {
                throw new Exception("Lütfen en az bir fatura seçin.");
            }
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            addBulkPayment($pdo, $invoice_ids, $_POST['account_id'], $_POST['payment_date'], $description);
            $success = "Toplu ödeme tamamlandı.";
            header('Location: supplier_details.php?id=' . $supplier_id);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        // Hata loglama
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Hata: {$e->getMessage()}, Supplier ID: $supplier_id, POST: " . json_encode($_POST)]);
    }
}

// Tedarikçi faturalarını al
$invoices = [];
if ($supplier) {
    $invoices = $pdo->prepare("SELECT i.id, i.invoice_number, i.amount AS invoice_amount, i.currency AS invoice_currency, i.issue_date, i.due_date, i.status, 
                              (SELECT SUM(ip.amount_try) FROM invoice_payments ip WHERE ip.invoice_id = i.id) AS total_paid_try
                              FROM invoices i
                              WHERE i.supplier_id = ?
                              ORDER BY i.due_date ASC");
    $invoices->execute([$supplier_id]);
    $invoices = $invoices->fetchAll();
}

$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll();

// Her fatura için tek ödeme CSRF token'ı oluştur veya yenile
foreach ($invoices as $invoice) {
    $_SESSION['single_payment_tokens'][$invoice['id']] = bin2hex(random_bytes(32));
    // Token yenileme logu
    $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
    $stmt->execute(['generate_csrf_token', "New CSRF token for invoice {$invoice['id']}: {$_SESSION['single_payment_tokens'][$invoice['id']]}"]);
}

// Şablon içeriğini oluştur
ob_start();
?>

<h2>Tedarikçi Detayları</h2>

<!-- Hata ve Başarı Mesajları -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php elseif (!$supplier): ?>
    <div class="alert alert-warning">Tedarikçi bulunamadı.</div>
<?php else: ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!-- Tedarikçi Bilgileri -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($supplier['name']); ?></h5>
            <p class="card-text">
                <strong>Unvan:</strong> <?php echo htmlspecialchars($supplier['title'] ?? 'Bilinmiyor'); ?><br>
                <strong>İletişim:</strong> <?php echo htmlspecialchars($supplier['contact'] ?? 'Bilinmiyor'); ?><br>
                <strong>Adres:</strong> <?php echo htmlspecialchars($supplier['address'] ?? '-'); ?><br>
                <strong>Oluşturulma Tarihi:</strong> <?php echo $supplier['created_at']; ?>
            </p>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal">Düzenle</button>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Bu tedarikçiyi silmek istediğinizden emin misiniz?');">
                <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['bulk_payment_token']; ?>">
                <button type="submit" name="delete_supplier" class="btn btn-danger">Sil</button>
            </form>
        </div>
    </div>

    <!-- Düzenleme Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Tedarikçi Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['bulk_payment_token']; ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Tedarikçi Adı</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="title" class="form-label">Unvan</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($supplier['title'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="contact" class="form-label">İletişim Bilgisi</label>
                            <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($supplier['contact'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Adres</label>
                            <textarea name="address" class="form-control"><?php echo htmlspecialchars($supplier['address'] ?? ''); ?></textarea>
                        </div>
                        <button type="submit" name="update_supplier" class="btn btn-primary">Güncelle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Tedarikçi Faturaları -->
    <h3>Tedarikçi Faturaları</h3>
    <form method="POST" id="bulkPaymentForm" action="supplier_details.php?id=<?php echo $supplier_id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['bulk_payment_token']; ?>">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Seç</th>
                    <th>Fatura No</th>
                    <th>Tutar</th>
                    <th>Para Birimi</th>
                    <th>Ödenen (TRY)</th>
                    <th>Durum</th>
                    <th>Vade Tarihi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><input type="checkbox" name="invoice_ids[]" value="<?php echo $invoice['id']; ?>" <?php echo $invoice['status'] == 'paid' ? 'disabled' : ''; ?>></td>
                        <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                        <td><?php echo number_format($invoice['invoice_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($invoice['invoice_currency']); ?></td>
                        <td><?php echo number_format($invoice['total_paid_try'] ?? 0, 2); ?></td>
                        <td><?php echo $invoice['status'] == 'pending' ? 'Ödenmedi' : ($invoice['status'] == 'paid' ? 'Ödendi' : 'Kısmen Ödendi'); ?></td>
                        <td><?php echo $invoice['due_date']; ?></td>                                               
                        <td>
                            <?php echo $invoice['status'] == 'pending' ? '<button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#paymentModal'.$invoice['id'].'">Ödeme Ekle</button>' : ($invoice['status'] == 'paid' ? '<button type="button" class="btn btn-sm btn-success" disabled>Ödendi</button>' : '<button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#paymentModal'.$invoice['id'].'">Ödeme Ekle</button>'); ?>                            
                        </td>
                    </tr>

                    <!-- Ödeme Ekle Modal -->
                    <div class="modal fade" id="paymentModal<?php echo $invoice['id']; ?>" tabindex="-1" aria-labelledby="paymentModalLabel<?php echo $invoice['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="paymentModalLabel<?php echo $invoice['id']; ?>">Fatura Ödemesi Ekle</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" id="paymentForm<?php echo $invoice['id']; ?>" action="supplier_details.php?id=<?php echo $supplier_id; ?>" data-type="single">
                                        <input type="hidden" name="csrf_token" id="csrf_token_<?php echo $invoice['id']; ?>" value="<?php echo $_SESSION['single_payment_tokens'][$invoice['id']]; ?>">
                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                        <input type="hidden" name="add_payment" value="1">
                                        <div class="mb-3">
                                            <label for="account_id_<?php echo $invoice['id']; ?>" class="form-label">Kasa Hesabı</label>
                                            <select name="account_id" id="account_id_<?php echo $invoice['id']; ?>" class="form-select" required>
                                                <?php foreach ($accounts as $account): ?>
                                                    <option value="<?php echo $account['id']; ?>">
                                                        <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="amount_<?php echo $invoice['id']; ?>" class="form-label">Tutar</label>
                                            <input type="number" step="0.01" name="amount" id="amount_<?php echo $invoice['id']; ?>" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="currency_<?php echo $invoice['id']; ?>" class="form-label">Para Birimi</label>
                                            <select name="currency" id="currency_<?php echo $invoice['id']; ?>" class="form-select">
                                                <option value="TRY">TRY</option>
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="payment_date_<?php echo $invoice['id']; ?>" class="form-label">Ödeme Tarihi</label>
                                            <input type="date" name="payment_date" id="payment_date_<?php echo $invoice['id']; ?>" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="description_<?php echo $invoice['id']; ?>" class="form-label">Açıklama</label>
                                            <textarea name="description" id="description_<?php echo $invoice['id']; ?>" class="form-control"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="showSinglePaymentSummary(<?php echo $invoice['id']; ?>, '<?php echo htmlspecialchars($invoice['invoice_number']); ?>', <?php echo $invoice['invoice_amount']; ?>, '<?php echo $invoice['invoice_currency']; ?>')">Ödeme Özetini Göster</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Toplu Ödeme -->
        <h3>Toplu Ödeme</h3>
        <div class="mb-3">
            <label for="account_id_bulk" class="form-label">Kasa Hesabı</label>
            <select name="account_id" id="account_id_bulk" class="form-select" required>
                <?php foreach ($accounts as $account): ?>
                    <option value="<?php echo $account['id']; ?>">
                        <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="payment_date_bulk" class="form-label">Ödeme Tarihi</label>
            <input type="date" name="payment_date" id="payment_date_bulk" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="mb-3">
            <label for="description_bulk" class="form-label">Açıklama</label>
            <textarea name="description" id="description_bulk" class="form-control"></textarea>
        </div>
        <input type="hidden" name="bulk_payment" value="1">
        <button type="button" class="btn btn-primary" onclick="showBulkPaymentSummary()">Toplu Ödeme Özetini Göster</button>
    </form>

    <!-- Özet Modal -->
    <div class="modal fade" id="summaryModal" tabindex="-1" aria-labelledby="summaryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="summaryModalLabel">Ödeme Özeti</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="summaryContent">
                    <!-- Özet içeriği JavaScript ile doldurulacak -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary" id="confirmPayment">Ödemeyi Onayla</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Özet Fonksiyonları -->
    <script>
        let currentForm = null;

        function showSinglePaymentSummary(invoiceId, invoiceNumber, invoiceAmount, invoiceCurrency) {
            console.log('Showing single payment summary for invoice:', invoiceId);
            const form = document.getElementById(`paymentForm${invoiceId}`);
            if (!form) {
                console.error('Payment form not found for invoice:', invoiceId);
                alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
                return;
            }

            const accountSelect = document.getElementById(`account_id_${invoiceId}`);
            const amount = document.getElementById(`amount_${invoiceId}`).value;
            const currency = document.getElementById(`currency_${invoiceId}`).value;
            const paymentDate = document.getElementById(`payment_date_${invoiceId}`).value;
            const description = document.getElementById(`description_${invoiceId}`).value;
            const csrfToken = document.getElementById(`csrf_token_${invoiceId}`).value;

            if (!amount || amount <= 0) {
                alert('Lütfen geçerli bir tutar girin.');
                console.error('Invalid amount:', amount);
                return;
            }
            if (!paymentDate) {
                alert('Lütfen bir ödeme tarihi seçin.');
                console.error('Invalid payment date:', paymentDate);
                return;
            }
            if (!accountSelect.value) {
                alert('Lütfen bir kasa hesabı seçin.');
                console.error('Invalid account ID:', accountSelect.value);
                return;
            }
            if (!csrfToken) {
                alert('CSRF token bulunamadı, lütfen sayfayı yenileyin.');
                console.error('Missing CSRF token for invoice:', invoiceId);
                return;
            }

            const summary = `
                <p><strong>Fatura No:</strong> ${invoiceNumber}</p>
                <p><strong>Fatura Tutarı:</strong> ${parseFloat(invoiceAmount).toFixed(2)} ${invoiceCurrency}</p>
                <p><strong>Ödeme Tutarı:</strong> ${parseFloat(amount).toFixed(2)} ${currency}</p>
                <p><strong>Kasa Hesabı:</strong> ${accountSelect.options[accountSelect.selectedIndex].text}</p>
                <p><strong>Ödeme Tarihi:</strong> ${paymentDate}</p>
                <p><strong>Açıklama:</strong> ${description || '-'}</p>
                <p><strong>CSRF Token:</strong> ${csrfToken}</p>
            `;
            document.getElementById('summaryContent').innerHTML = summary;
            document.getElementById('summaryModalLabel').textContent = 'Tek Fatura Ödeme Özeti';
            currentForm = form;
            console.log('Single payment form set:', form, 'CSRF Token:', csrfToken);

            try {
                const modal = new bootstrap.Modal(document.getElementById('summaryModal'));
                modal.show();
                console.log('Summary modal opened for invoice:', invoiceId);
            } catch (error) {
                console.error('Error opening summary modal:', error);
                alert('Özet modalı açılırken hata oluştu: ' + error.message);
                currentForm = null;
            }
        }

        function showBulkPaymentSummary() {
            console.log('Showing bulk payment summary');
            const form = document.getElementById('bulkPaymentForm');
            if (!form) {
                console.error('Bulk payment form not found');
                alert('Toplu ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
                return;
            }

            const selectedInvoices = document.querySelectorAll('input[name="invoice_ids[]"]:checked');
            const accountSelect = document.getElementById('account_id_bulk');
            const paymentDate = document.getElementById('payment_date_bulk').value;
            const description = document.getElementById('description_bulk').value;
            const csrfToken = form.querySelector('input[name="csrf_token"]').value;

            if (selectedInvoices.length === 0) {
                alert('Lütfen en az bir fatura seçin.');
                console.error('No invoices selected');
                return;
            }
            if (!accountSelect.value) {
                alert('Lütfen bir kasa hesabı seçin.');
                console.error('Invalid account ID:', accountSelect.value);
                return;
            }
            if (!paymentDate) {
                alert('Lütfen bir ödeme tarihi seçin.');
                console.error('Invalid payment date:', paymentDate);
                return;
            }
            if (!csrfToken) {
                alert('CSRF token bulunamadı, lütfen sayfayı yenileyin.');
                console.error('Missing CSRF token');
                return;
            }

            let summary = '<h5>Seçilen Faturalar:</h5><ul>';
            selectedInvoices.forEach((checkbox) => {
                const row = checkbox.closest('tr');
                const invoiceNumber = row.cells[1].textContent;
                const amount = row.cells[2].textContent;
                const currency = row.cells[3].textContent;
                summary += `<li>${invoiceNumber}: ${amount} ${currency}</li>`;
            });
            summary += '</ul>';
            summary += `<p><strong>Kasa Hesabı:</strong> ${accountSelect.options[accountSelect.selectedIndex].text}</p>`;
            summary += `<p><strong>Ödeme Tarihi:</strong> ${paymentDate}</p>`;
            summary += `<p><strong>Açıklama:</strong> ${description || '-'}</p>`;
            summary += `<p><strong>CSRF Token:</strong> ${csrfToken}</p>`;

            document.getElementById('summaryContent').innerHTML = summary;
            document.getElementById('summaryModalLabel').textContent = 'Toplu Ödeme Özeti';
            currentForm = form;
            console.log('Bulk payment form set:', form, 'CSRF Token:', csrfToken);

            try {
                const modal = new bootstrap.Modal(document.getElementById('summaryModal'));
                modal.show();
                console.log('Bulk payment summary modal opened');
            } catch (error) {
                console.error('Error opening bulk payment summary modal:', error);
                alert('Toplu ödeme özeti modalı açılırken hata oluştu: ' + error.message);
                currentForm = null;
            }
        }

        document.getElementById('confirmPayment').addEventListener('click', function() {
            console.log('Confirm payment clicked, current form:', currentForm);
            if (!currentForm) {
                console.error('No form set for submission');
                alert('Form bulunamadı. Lütfen ödeme özetini tekrar açın ve işlemi tamamlayın.');
                return;
            }

            const formData = new FormData(currentForm);
            console.log('Form data:', Object.fromEntries(formData));
            currentForm.dataset.submitted = 'true';
            currentForm.submit();
            console.log('Form submitted:', currentForm);
        });

        // Modal açıldığında currentForm'u kontrol et
        document.getElementById('summaryModal').addEventListener('show.bs.modal', function() {
            console.log('Summary modal showing, current form:', currentForm);
            if (!currentForm) {
                console.warn('No form set when opening summary modal');
                alert('Ödeme formu bulunamadı, lütfen işlemi tekrar başlatın.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('summaryModal'));
                modal.hide();
            }
        });

        // Modal kapandığında currentForm'u sıfırla, ancak yalnızca ödeme tamamlanmadıysa
        document.getElementById('summaryModal').addEventListener('hidden.bs.modal', function() {
            console.log('Modal closed, current form before reset:', currentForm);
            if (currentForm && !currentForm.dataset.submitted) {
                currentForm = null;
                console.log('currentForm reset to null');
            }
        });
    </script>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>