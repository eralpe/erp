<?php
session_start();
require_once 'config/db.php';
require_once 'functions/invoices.php'; // For addInvoicePayment, adjust if needed
require_once 'functions/currency.php'; // For currency conversion

$title = 'Kredi Detayları';
$error = null;
$success = null;

// CSRF token for form submissions
if (!isset($_SESSION['credit_token'])) {
    $_SESSION['credit_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['credit_token']) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Geçersiz CSRF token (credits), Sent: " . ($_POST['csrf_token'] ?? 'none') . ", Expected: " . $_SESSION['credit_token']]);
            throw new Exception("Geçersiz CSRF token.");
        }
        $_SESSION['credit_token'] = bin2hex(random_bytes(32)); // Refresh token

        // Check for 'type' key
        if (!isset($_POST['type'])) {
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['error', "Undefined array key 'type' in credits.php, POST: " . json_encode($_POST)]);
            throw new Exception("Geçersiz işlem tipi.");
        }

        $type = $_POST['type'];

        if ($type === 'add_credit') {
            $amount = $_POST['amount'] ?? null;
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            $installment_count = $_POST['installment_count'] ?? 1;
            if (!$amount || $amount <= 0 || $installment_count < 1) {
                throw new Exception("Geçerli bir tutar ve taksit sayısı girin.");
            }
            // Insert credit
            $stmt = $pdo->prepare("INSERT INTO credits (amount, description, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$amount, $description]);
            $credit_id = $pdo->lastInsertId();
            // Insert installments
            $installment_amount = $amount / $installment_count;
            $due_date = new DateTime();
            for ($i = 0; $i < $installment_count; $i++) {
                $due_date->modify('+1 month');
                $stmt = $pdo->prepare("INSERT INTO credit_installments (credit_id, amount, due_date, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$credit_id, $installment_amount, $due_date->format('Y-m-d')]);
            }
            // Log installment creation
            $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
            $stmt->execute(['info', "Credit $credit_id created with $installment_count installments"]);
            $success = "Kredi ve taksitler eklendi.";
        } elseif ($type === 'edit_credit') {
            $id = $_POST['credit_id'] ?? null;
            $amount = $_POST['amount'] ?? null;
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            if (!$id || !$amount || $amount <= 0) {
                throw new Exception("Geçerli bir kredi ID ve tutar girin.");
            }
            $stmt = $pdo->prepare("UPDATE credits SET amount = ?, description = ? WHERE id = ?");
            $stmt->execute([$amount, $description, $id]);
            $success = "Kredi güncellendi.";
        } elseif ($type === 'delete_credit') {
            $id = $_POST['credit_id'] ?? null;
            if (!$id) {
                throw new Exception("Geçerli bir kredi ID girin.");
            }
            $stmt = $pdo->prepare("DELETE FROM credits WHERE id = ?");
            $stmt->execute([$id]);
            // Note: Installments are deleted via ON DELETE CASCADE
            $success = "Kredi ve taksitleri silindi.";
        } elseif ($type === 'pay_installments') {
            $installment_ids = $_POST['installment_ids'] ?? [];
            $account_id = $_POST['account_id'] ?? null;
            $payment_date = $_POST['payment_date'] ?? null;
            $description = !empty($_POST['description']) ? trim($_POST['description']) : null;
            if (empty($installment_ids)) {
                throw new Exception("Lütfen en az bir taksit seçin.");
            }
            if (!$account_id || !$payment_date) {
                throw new Exception("Kasa hesabı ve ödeme tarihi zorunludur.");
            }
            foreach ($installment_ids as $installment_id) {
                $stmt = $pdo->prepare("SELECT credit_id, amount FROM credit_installments WHERE id = ? AND status = 'pending'");
                $stmt->execute([$installment_id]);
                $installment = $stmt->fetch();
                if (!$installment) {
                    continue; // Skip if already paid or invalid
                }
                $amount_try = $installment['amount']; // Assuming TRY for simplicity
                // Update installment
                $stmt = $pdo->prepare("UPDATE credit_installments SET status = 'paid', paid_amount = ?, paid_date = ? WHERE id = ?");
                $stmt->execute([$installment['amount'], $payment_date, $installment_id]);
                // Record transaction
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, amount, type, description, created_at) VALUES (?, ?, 'debit', ?, ?)");
                $stmt->execute([$account_id, $amount_try, "Kredi taksit ödemesi (ID: $installment_id)", $payment_date]);
                // Log payment
                $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
                $stmt->execute(['info', "Installment $installment_id paid for credit {$installment['credit_id']}"]);
            }
            $success = "Seçilen taksitler ödendi.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
        $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
        $stmt->execute(['error', "Hata: {$e->getMessage()}, POST: " . json_encode($_POST)]);
    }
}

// Fetch active credits (with pending installments)
$credits = $pdo->query("SELECT DISTINCT c.* 
                         FROM credits c
                         JOIN credit_installments ci ON c.id = ci.credit_id
                         WHERE ci.status = 'pending'
                         ORDER BY c.created_at DESC")->fetchAll();

// Fetch all installments
$installments = $pdo->query("SELECT ci.*, c.description AS credit_description
                            FROM credit_installments ci
                            JOIN credits c ON ci.credit_id = c.id
                            ORDER BY ci.due_date ASC")->fetchAll();

// Calculate remaining balances
$remaining_balances = [];
foreach ($credits as $credit) {
    $stmt = $pdo->prepare("SELECT SUM(amount) as remaining_balance FROM credit_installments WHERE credit_id = ? AND status = 'pending'");
    $stmt->execute([$credit['id']]);
    $remaining_balances[$credit['id']] = $stmt->fetchColumn() ?: 0.00;
    // Log remaining balance for debugging
    $stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
    $stmt->execute(['info', "Remaining balance for credit {$credit['id']}: " . $remaining_balances[$credit['id']]]);
}

// Fetch payment history (paid installments)
$payment_history = $pdo->query("SELECT ci.*, c.description AS credit_description, ca.name AS account_name
                               FROM credit_installments ci
                               JOIN credits c ON ci.credit_id = c.id
                               JOIN cash_transactions ct ON ct.description LIKE CONCAT('Kredi taksit ödemesi (ID: ', ci.id, ')')
                               JOIN cash_accounts ca ON ct.account_id = ca.id
                               WHERE ci.status = 'paid'
                               ORDER BY ci.paid_date DESC")->fetchAll();

// Log fetched data for debugging
$stmt = $pdo->prepare("INSERT INTO logs (action, details) VALUES (?, ?)");
$stmt->execute(['info', "Fetched " . count($credits) . " active credits, " . count($installments) . " installments, and " . count($payment_history) . " payment history records"]);

// Group installments by credit_id
$installments_by_credit = [];
foreach ($installments as $installment) {
    $installments_by_credit[$installment['credit_id']][] = $installment;
}

// Calculate reminders
$today = new DateTime();
$in_7_days = (new DateTime())->modify('+7 days');
$reminders = [];
foreach ($installments as $installment) {
    $due_date = new DateTime($installment['due_date']);
    if ($installment['status'] == 'pending') {
        if ($due_date < $today) {
            $reminders[$installment['id']] = 'Vadesi geçmiş';
        } elseif ($due_date <= $in_7_days) {
            $reminders[$installment['id']] = 'Yaklaşan vade';
        }
    }
}

// Fetch cash accounts
$accounts = $pdo->query("SELECT * FROM cash_accounts")->fetchAll();

// Template content
ob_start();
?>

<h2>Kredi Detayları</h2>

<!-- Error and Success Messages -->
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Tabs for Active Credits and Payment History -->
<ul class="nav nav-tabs mb-4" id="creditTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="active-credits-tab" data-bs-toggle="tab" data-bs-target="#active-credits" type="button" role="tab" aria-controls="active-credits" aria-selected="true">Aktif Krediler</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="payment-history-tab" data-bs-toggle="tab" data-bs-target="#payment-history" type="button" role="tab" aria-controls="payment-history" aria-selected="false">Ödeme Geçmişi</button>
    </li>
</ul>

<div class="tab-content" id="creditTabsContent">
    <!-- Active Credits Tab -->
    <div class="tab-pane fade show active" id="active-credits" role="tabpanel" aria-labelledby="active-credits-tab">
        <!-- Add Credit Form -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Yeni Kredi Ekle</h5>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['credit_token']; ?>">
                    <input type="hidden" name="type" value="add_credit">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Tutar</label>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="installment_count" class="form-label">Taksit Sayısı</label>
                        <input type="number" min="1" name="installment_count" id="installment_count" class="form-control" value="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Ekle</button>
                </form>
            </div>
        </div>

        <!-- Active Credits List -->
        <h3>Aktif Krediler</h3>
        <?php if (empty($credits)): ?>
            <div class="alert alert-info">Aktif kredi bulunmamaktadır.</div>
        <?php else: ?>
            <?php foreach ($credits as $credit): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Kredi: <?php echo htmlspecialchars($credit['description'] ?? 'Kredi #' . $credit['id']); ?></h5>
                        <p class="card-text">
                            <strong>Tutar:</strong> <?php echo number_format($credit['amount'], 2); ?> TRY<br>
                            <strong>Kalan Bakiye:</strong> <?php echo number_format($remaining_balances[$credit['id']], 2); ?> TRY<br>
                            <strong>Oluşturulma Tarihi:</strong> <?php echo $credit['created_at']; ?>
                        </p>
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $credit['id']; ?>">Düzenle</button>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Bu krediyi ve taksitlerini silmek istediğinizden emin misiniz?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['credit_token']; ?>">
                            <input type="hidden" name="type" value="delete_credit">
                            <input type="hidden" name="credit_id" value="<?php echo $credit['id']; ?>">
                            <button type="submit" class="btn btn-danger">Sil</button>
                        </form>

                        <!-- Installments Table -->
                        <h6 class="mt-3">Taksitler</h6>
                        <?php
                        $installment_list = $installments_by_credit[$credit['id']] ?? [];
                        if (empty($installment_list)):
                        ?>
                            <div class="alert alert-warning">Bu kredi için taksit bulunmamaktadır.</div>
                        <?php else: ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Taksit No</th>
                                        <th>Tutar</th>
                                        <th>Vade Tarihi</th>
                                        <th>Durum</th>
                                        <th>Hatırlatma</th>
                                        <th>İşlem</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($installment_list as $index => $installment): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo number_format($installment['amount'], 2); ?> TRY</td>
                                            <td><?php echo $installment['due_date']; ?></td>
                                            <td><?php echo $installment['status'] == 'pending' ? 'Ödenmedi' : 'Ödendi'; ?></td>
                                            <td>
                                                <?php if (isset($reminders[$installment['id']])): ?>
                                                    <span class="badge <?php echo $reminders[$installment['id']] == 'Vadesi geçmiş' ? 'bg-danger' : 'bg-warning'; ?>">
                                                        <?php echo $reminders[$installment['id']]; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($installment['status'] == 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary" onclick="showInstallmentPaymentSummary(<?php echo $credit['id']; ?>, <?php echo $installment['id']; ?>, <?php echo $index + 1; ?>, <?php echo $installment['amount']; ?>, '<?php echo $installment['due_date']; ?>')">Öde</button>
                                                <?php else: ?>
                                                    <span class="text-muted">Ödendi</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Edit Credit Modal -->
                <div class="modal fade" id="editModal<?php echo $credit['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $credit['id']; ?>" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editModalLabel<?php echo $credit['id']; ?>">Kredi Düzenle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['credit_token']; ?>">
                                    <input type="hidden" name="type" value="edit_credit">
                                    <input type="hidden" name="credit_id" value="<?php echo $credit['id']; ?>">
                                    <div class="mb-3">
                                        <label for="amount_<?php echo $credit['id']; ?>" class="form-label">Tutar</label>
                                        <input type="number" step="0.01" name="amount" id="amount_<?php echo $credit['id']; ?>" class="form-control" value="<?php echo htmlspecialchars($credit['amount']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description_<?php echo $credit['id']; ?>" class="form-label">Açıklama</label>
                                        <textarea name="description" id="description_<?php echo $credit['id']; ?>" class="form-control"><?php echo htmlspecialchars($credit['description'] ?? ''); ?></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Güncelle</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Payment History Tab -->
    <div class="tab-pane fade" id="payment-history" role="tabpanel" aria-labelledby="payment-history-tab">
        <h3>Ödeme Geçmişi</h3>
        <?php if (empty($payment_history)): ?>
            <div class="alert alert-info">Ödeme geçmişi bulunmamaktadır.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Kredi</th>
                        <th>Taksit No</th>
                        <th>Tutar</th>
                        <th>Vade Tarihi</th>
                        <th>Ödeme Tarihi</th>
                        <th>Kasa Hesabı</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $index => $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['credit_description'] ?? 'Kredi #' . $payment['credit_id']); ?></td>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo number_format($payment['paid_amount'], 2); ?> TRY</td>
                            <td><?php echo $payment['due_date']; ?></td>
                            <td><?php echo $payment['paid_date']; ?></td>
                            <td><?php echo htmlspecialchars($payment['account_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Summary Modal -->
<div class="modal fade" id="paymentSummaryModal" tabindex="-1" aria-labelledby="paymentSummaryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentSummaryModalLabel">Taksit Ödeme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['credit_token']; ?>">
                    <input type="hidden" name="type" value="pay_installments">
                    <input type="hidden" name="installment_ids[]" id="installment_id">
                    <div id="paymentSummaryContent"></div>
                    <div class="mb-3">
                        <label for="account_id" class="form-label">Kasa Hesabı</label>
                        <select name="account_id" id="account_id" class="form-select" required>
                            <option value="" disabled selected>Bir kasa hesabı seçin</option>
                            <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['id']; ?>">
                                    <?php echo htmlspecialchars($account['name'] . ' (' . $account['currency'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Ödeme Tarihi</label>
                        <input type="date" name="payment_date" id="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Açıklama</label>
                        <textarea name="description" id="description" class="form-control" placeholder="Ödeme açıklamasını girin (opsiyonel)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="confirmInstallmentPayment">Ödemeyi Onayla</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Payment Summary -->
<script>
    let currentForm = null;

    function showInstallmentPaymentSummary(creditId, installmentId, installmentNumber, amount, dueDate) {
        console.log('Showing payment summary for credit:', creditId, 'installment:', installmentId);
        const form = document.getElementById('paymentForm');
        if (!form) {
            console.error('Payment form not found');
            alert('Ödeme formu bulunamadı, lütfen sayfayı yenileyin.');
            return;
        }

        const installmentInput = form.querySelector('#installment_id');
        const accountSelect = form.querySelector('#account_id');
        const paymentDate = form.querySelector('#payment_date');
        const description = form.querySelector('#description');
        const csrfToken = form.querySelector('input[name="csrf_token"]').value;

        if (!csrfToken) {
            console.error('Missing CSRF token');
            alert('CSRF token bulunamadı, lütfen sayfayı yenileyin.');
            return;
        }

        // Set installment ID
        installmentInput.value = installmentId;

        // Generate summary
        let summary = '<h6 class="mb-3">Ödeme Özeti</h6>';
        summary += '<table class="table table-sm">';
        summary += '<thead><tr><th>Taksit No</th><th>Tutar</th><th>Vade Tarihi</th></tr></thead>';
        summary += '<tbody>';
        summary += `<tr><td>${installmentNumber}</td><td>${parseFloat(amount).toFixed(2)} TRY</td><td>${dueDate}</td></tr>`;
        summary += '</tbody></table>';
        summary += `<p class="mt-3"><strong>Toplam Tutar:</strong> ${parseFloat(amount).toFixed(2)} TRY</p>`;
        summary += `<p class="text-warning">Bu ödemeyi onayladığınızda, seçilen taksit ödenmiş olarak işaretlenecek ve kasa hesabından ödeme kaydedilecektir.</p>`;

        document.getElementById('paymentSummaryContent').innerHTML = summary;
        currentForm = form;
        console.log('Payment form set:', form, 'CSRF Token:', csrfToken);

        // Reset form fields
        accountSelect.value = '';
        paymentDate.value = '<?php echo date('Y-m-d'); ?>';
        description.value = '';

        try {
            const modal = new bootstrap.Modal(document.getElementById('paymentSummaryModal'));
            modal.show();
            console.log('Payment summary modal opened for installment:', installmentId);
        } catch (error) {
            console.error('Error opening payment summary modal:', error);
            alert('Ödeme özeti modalı açılırken hata oluştu: ' + error.message);
            currentForm = null;
        }
    }

    document.getElementById('confirmInstallmentPayment').addEventListener('click', function() {
        console.log('Confirm payment clicked, current form:', currentForm);
        if (!currentForm) {
            console.error('No form set for submission');
            alert('Form bulunamadı. Lütfen ödeme özetini tekrar açın ve işlemi tamamlayın.');
            return;
        }

        const accountSelect = currentForm.querySelector('#account_id');
        const paymentDate = currentForm.querySelector('#payment_date').value;
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

        if (!confirm('Bu ödemeyi onaylamak istediğinizden emin misiniz?')) {
            console.log('Payment confirmation cancelled by user');
            return;
        }

        const formData = new FormData(currentForm);
        console.log('Form data:', Object.fromEntries(formData));
        currentForm.dataset.submitted = 'true';
        currentForm.submit();
        console.log('Form submitted:', currentForm);
    });

    // Modal checks
    document.getElementById('paymentSummaryModal').addEventListener('show.bs.modal', function() {
        console.log('Payment summary modal showing, current form:', currentForm);
        if (!currentForm) {
            console.warn('No form set when opening payment summary modal');
            alert('Ödeme formu bulunamadı, lütfen işlemi tekrar başlatın.');
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentSummaryModal'));
            modal.hide();
        }
    });

    document.getElementById('paymentSummaryModal').addEventListener('hide.bs.modal', function(event) {
        console.log('Modal closed, current form before reset:', currentForm);
        if (currentForm && !currentForm.dataset.submitted && !confirm('Ödeme işlemini iptal etmek istediğinizden emin misiniz?')) {
            event.preventDefault();
            console.log('Modal close prevented due to user cancellation');
        } else if (currentForm && !currentForm.dataset.submitted) {
            currentForm = null;
            console.log('currentForm reset to null');
        }
    });
</script>

<?php
$content = ob_get_clean();
require_once 'template.php';
?>