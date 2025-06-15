<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Password verification for restore
define('RECYCLE_BIN_PASSWORD', 'Binary@123');

// Handle restore if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_id'])) {
    require_once 'restore_quotation.php';
}

// Get deleted quotations
$stmt = $pdo->prepare("
    SELECT rb.id, rb.deleted_at, 
           JSON_EXTRACT(rb.quotation_data, '$.quotation_number') as quotation_number,
           JSON_EXTRACT(rb.quotation_data, '$.client_id') as client_id,
           JSON_EXTRACT(rb.quotation_data, '$.date') as date,
           JSON_EXTRACT(rb.quotation_data, '$.expiry_date') as expiry_date,
           c.name as client_name
    FROM quotation_recycle_bin rb
    LEFT JOIN customers c ON JSON_EXTRACT(rb.quotation_data, '$.client_id') = c.id
    WHERE rb.deleted_by = ?
    ORDER BY rb.deleted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deletedQuotations = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-restore me-2"></i>Quotations Recycle Bin</h2>
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Quotations
        </a>
    </div>

    <?php if (empty($deletedQuotations)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Your quotations recycle bin is empty
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Deleted Date</th>
                            <th>Quotation #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedQuotations as $quotation): ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($quotation['deleted_at'])) ?></td>
                            <td><?= trim($quotation['quotation_number'], '"') ?></td>
                            <td><?= htmlspecialchars($quotation['client_name'] ?: 'Deleted Client') ?></td>
                            <td><?= date('M j, Y', strtotime(trim($quotation['date'], '"'))) ?></td>
                            <td><?= date('M j, Y', strtotime(trim($quotation['expiry_date'], '"'))) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary restore-btn" 
                                        data-id="<?= $quotation['id'] ?>"
                                        data-quotation="<?= htmlspecialchars(trim($quotation['quotation_number'], '"')) ?>">
                                    <i class="fas fa-trash-restore"></i> Restore
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restore Quotation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="recycle_bin_quotations.php">
                <input type="hidden" name="restore_id" id="restoreId">
                <div class="modal-body">
                    <p>You are about to restore quotation: <strong id="restoreQuotationNumber"></strong></p>
                    <div class="mb-3">
                        <label for="restorePassword" class="form-label">Recycle Bin Password</label>
                        <input type="password" class="form-control" id="restorePassword" name="password" required>
                        <small class="text-muted">Enter "Binary@123" to confirm restoration</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Restore button handler
    document.querySelectorAll('.restore-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('restoreId').value = this.dataset.id;
            document.getElementById('restoreQuotationNumber').textContent = this.dataset.quotation;
            new bootstrap.Modal(document.getElementById('restoreModal')).show();
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>