<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Password verification constant
define('RECYCLE_BIN_PASSWORD', 'Binary@123');

// Handle actions (restore or permanent delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../includes/recycle_bin_actions.php';
}

// Get all deleted items from different bins
$deletedItems = [];

// 1. Get deleted transactions
$stmt = $pdo->prepare("
    SELECT 
        rb.id,
        'transaction' AS type,
        JSON_EXTRACT(rb.transaction_data, '$.description') AS description,
        JSON_EXTRACT(rb.transaction_data, '$.amount') AS amount,
        JSON_EXTRACT(rb.transaction_data, '$.type') AS item_type,
        rb.deleted_at,
        NULL AS client_name,
        NULL AS number
    FROM transaction_recycle_bin rb
    WHERE rb.deleted_by = ?
    ORDER BY rb.deleted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deletedItems = array_merge($deletedItems, $stmt->fetchAll());

// 2. Get deleted quotations
$stmt = $pdo->prepare("
    SELECT 
        rb.id,
        'quotation' AS type,
        JSON_EXTRACT(rb.quotation_data, '$.notes') AS description,
        NULL AS amount,
        NULL AS item_type,
        rb.deleted_at,
        c.name AS client_name,
        JSON_EXTRACT(rb.quotation_data, '$.quotation_number') AS number
    FROM quotation_recycle_bin rb
    LEFT JOIN customers c ON JSON_EXTRACT(rb.quotation_data, '$.client_id') = c.id
    WHERE rb.deleted_by = ?
    ORDER BY rb.deleted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deletedItems = array_merge($deletedItems, $stmt->fetchAll());

// Sort all items by deletion date
usort($deletedItems, function($a, $b) {
    return strtotime($b['deleted_at']) - strtotime($a['deleted_at']);
});
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-restore me-2"></i>Recycle Bin Management</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary">
                <i class="fas fa-home me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if (empty($deletedItems)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Your recycle bins are empty
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="recycleBinTable">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Deleted Date</th>
                            <th>Identifier</th>
                            <th>Description</th>
                            <th>Details</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedItems as $item): ?>
                        <tr>
                            <td>
                                <span class="badge bg-<?= $item['type'] === 'transaction' ? 'primary' : 'info' ?>">
                                    <?= ucfirst($item['type']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y H:i', strtotime($item['deleted_at'])) ?></td>
                            <td>
                                <?php if ($item['type'] === 'quotation'): ?>
                                    <?= trim($item['number'], '"') ?>
                                <?php else: ?>
                                    Transaction
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['type'] === 'transaction'): ?>
                                    <?= htmlspecialchars(trim($item['description'], '"')) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars(trim($item['description'], '"')) ?: 'Quotation' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['type'] === 'transaction'): ?>
                                    <span class="<?= trim($item['item_type'], '"') === 'income' ? 'text-success' : 'text-danger' ?>">
                                        <?= trim($item['item_type'], '"') === 'income' ? '+' : '-' ?>
                                        R<?= number_format(trim($item['amount'], '"'), 2) ?>
                                    </span>
                                <?php else: ?>
                                    <?= $item['client_name'] ? 'Client: ' . htmlspecialchars($item['client_name']) : '' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary restore-btn" 
                                            data-id="<?= $item['id'] ?>"
                                            data-type="<?= $item['type'] ?>"
                                            data-description="<?= htmlspecialchars(
                                                $item['type'] === 'transaction' ? 
                                                trim($item['description'], '"') : 
                                                trim($item['number'], '"')
                                            ) ?>">
                                        <i class="fas fa-trash-restore"></i> Restore
                                    </button>
                                    <button class="btn btn-outline-danger delete-btn" 
                                            data-id="<?= $item['id'] ?>"
                                            data-type="<?= $item['type'] ?>"
                                            data-description="<?= htmlspecialchars(
                                                $item['type'] === 'transaction' ? 
                                                trim($item['description'], '"') : 
                                                trim($item['number'], '"')
                                            ) ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </div>
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

<!-- Action Modals -->
<div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Confirm Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="recycle_bin.php">
                <input type="hidden" name="item_id" id="itemId">
                <input type="hidden" name="item_type" id="itemType">
                <input type="hidden" name="action" id="actionType">
                <div class="modal-body">
                    <p id="modalDescription">You are about to perform an action on this item.</p>
                    <div class="mb-3" id="passwordField">
                        <label for="actionPassword" class="form-label">Recycle Bin Password</label>
                        <input type="password" class="form-control" id="actionPassword" name="password" required>
                        <small class="text-muted">Enter "Binary@123" to confirm</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" id="confirmButton">Confirm</button>
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
            document.getElementById('itemId').value = this.dataset.id;
            document.getElementById('itemType').value = this.dataset.type;
            document.getElementById('actionType').value = 'restore';
            document.getElementById('modalTitle').textContent = 'Restore Item';
            document.getElementById('modalDescription').innerHTML = 
                `You are about to restore <strong>${this.dataset.description}</strong> from the recycle bin.`;
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('confirmButton').className = 'btn btn-primary';
            document.getElementById('confirmButton').innerHTML = '<i class="fas fa-trash-restore me-1"></i> Restore';
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        });
    });

    // Delete button handler
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('itemId').value = this.dataset.id;
            document.getElementById('itemType').value = this.dataset.type;
            document.getElementById('actionType').value = 'delete';
            document.getElementById('modalTitle').textContent = 'Permanently Delete Item';
            document.getElementById('modalDescription').innerHTML = 
                `You are about to permanently delete <strong>${this.dataset.description}</strong>. This cannot be undone.`;
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('confirmButton').className = 'btn btn-danger';
            document.getElementById('confirmButton').innerHTML = '<i class="fas fa-trash-alt me-1"></i> Delete';
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>