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
    if ($_POST['password'] !== RECYCLE_BIN_PASSWORD) {
        $_SESSION['error_message'] = "Incorrect recycle bin password";
        header('Location: recycle_bin.php');
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // 1. Get transaction from bin
        $stmt = $pdo->prepare("SELECT * FROM transaction_recycle_bin WHERE id = ? AND deleted_by = ?");
        $stmt->execute([$_POST['restore_id'], $_SESSION['user_id']]);
        $binItem = $stmt->fetch();
        
        if (!$binItem) {
            throw new Exception("Transaction not found in recycle bin");
        }
        
        $transaction = json_decode($binItem['transaction_data'], true);
        
        // 2. Restore to transactions table
        $insert = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, account_id, transaction_date, amount, type, category_id, description, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            $_SESSION['user_id'],
            $transaction['account_id'],
            $transaction['transaction_date'],
            $transaction['amount'],
            $transaction['type'],
            $transaction['category_id'],
            $transaction['description'],
            $transaction['created_at']
        ]);
        
        // 3. Adjust account balance
        if ($transaction['type'] === 'income') {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
        } else {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
        }
        $update->execute([$transaction['amount'], $transaction['account_id'], $_SESSION['user_id']]);
        
        // 4. Remove from bin
        $delete = $pdo->prepare("DELETE FROM transaction_recycle_bin WHERE id = ?");
        $delete->execute([$_POST['restore_id']]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Transaction restored successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Restore Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error restoring transaction";
    }
    
    header('Location: recycle_bin.php');
    exit;
}

// Get deleted transactions
$stmt = $pdo->prepare("
    SELECT rb.id, rb.deleted_at, 
           JSON_EXTRACT(rb.transaction_data, '$.description') as description,
           JSON_EXTRACT(rb.transaction_data, '$.amount') as amount,
           JSON_EXTRACT(rb.transaction_data, '$.type') as type,
           JSON_EXTRACT(rb.transaction_data, '$.transaction_date') as transaction_date
    FROM transaction_recycle_bin rb
    WHERE rb.deleted_by = ?
    ORDER BY rb.deleted_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deletedTransactions = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-trash-restore me-2"></i>Recycle Bin</h2>
        <a href="transactions.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Transactions
        </a>
    </div>

    <?php if (empty($deletedTransactions)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i> Your recycle bin is empty
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Deleted Date</th>
                            <th>Original Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletedTransactions as $transaction): ?>
                        <tr>
                            <td><?= date('M j, Y H:i', strtotime($transaction['deleted_at'])) ?></td>
                            <td><?= date('M j, Y', strtotime(trim($transaction['transaction_date'], '"'))) ?></td>
                            <td><?= htmlspecialchars(trim($transaction['description'], '"')) ?></td>
                            <td class="<?= trim($transaction['type'], '"') === 'income' ? 'text-success' : 'text-danger' ?>">
                                <?= trim($transaction['type'], '"') === 'income' ? '+' : '-' ?>
                                R<?= number_format(trim($transaction['amount'], '"'), 2) ?>
                            </td>
                            <td><?= ucfirst(trim($transaction['type'], '"')) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary restore-btn" 
                                        data-id="<?= $transaction['id'] ?>"
                                        data-description="<?= htmlspecialchars(trim($transaction['description'], '"')) ?>">
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
                <h5 class="modal-title">Restore Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="recycle_bin.php">
                <input type="hidden" name="restore_id" id="restoreId">
                <div class="modal-body">
                    <p>You are about to restore: <strong id="restoreDescription"></strong></p>
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
            document.getElementById('restoreDescription').textContent = this.dataset.description;
            new bootstrap.Modal(document.getElementById('restoreModal')).show();
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>