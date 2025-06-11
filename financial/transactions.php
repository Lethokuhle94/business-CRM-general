<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Fetch transactions with account and category names
$query = "SELECT t.*, a.account_name, c.name as category_name 
          FROM transactions t
          LEFT JOIN financial_accounts a ON t.account_id = a.id
          LEFT JOIN transaction_categories c ON t.category_id = c.id
          WHERE t.user_id = ?
          ORDER BY t.transaction_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$transactions = $stmt->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-exchange-alt me-2"></i>Transactions</h2>
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-plus-circle me-1"></i> New Transaction
            </button>
            <a href="list.php" class="btn btn-outline-secondary">
                <i class="fas fa-chart-pie me-1"></i> Financial Summary
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Transaction Filters -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <select class="form-select" id="filterType">
                        <option value="">All Types</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                        <option value="transfer">Transfer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="filterAccount">
                        <option value="">All Accounts</option>
                        <?php 
                        $accounts = $pdo->query("SELECT * FROM financial_accounts WHERE user_id = " . $_SESSION['user_id'])->fetchAll();
                        foreach ($accounts as $account): ?>
                        <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="filterStartDate" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="filterEndDate" placeholder="To Date">
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Account</th>
                            <th>Category</th>
                            <th class="text-end">Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?= date('M j, Y', strtotime($transaction['transaction_date'])) ?></td>
                            <td><?= htmlspecialchars($transaction['description']) ?></td>
                            <td><?= htmlspecialchars($transaction['account_name']) ?></td>
                            <td><?= $transaction['category_name'] ? htmlspecialchars($transaction['category_name']) : '-' ?></td>
                            <td class="text-end <?= $transaction['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                <?= $transaction['type'] === 'income' ? '+' : '-' ?> 
                                R<?= number_format($transaction['amount'], 2, ',', ' ') ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary edit-btn"
                                            data-id="<?= $transaction['id'] ?>"
                                            data-date="<?= date('Y-m-d', strtotime($transaction['transaction_date'])) ?>"
                                            data-type="<?= $transaction['type'] ?>"
                                            data-account="<?= $transaction['account_id'] ?>"
                                            data-category="<?= $transaction['category_id'] ?>"
                                            data-amount="<?= $transaction['amount'] ?>"
                                            data-description="<?= htmlspecialchars($transaction['description']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="delete_transaction.php?id=<?= $transaction['id'] ?>" 
                                       class="btn btn-outline-danger"
                                       onclick="return confirm('Are you sure you want to delete this transaction?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_transaction.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="transaction_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account</label>
                        <select class="form-select" name="account_id" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (R)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php 
                            $categories = $pdo->query("SELECT * FROM transaction_categories WHERE user_id = " . $_SESSION['user_id'] . " OR is_default = TRUE")->fetchAll();
                            foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Transaction</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_transaction.php" method="POST">
                <input type="hidden" name="edit_id" id="editId">
                <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" id="editType" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-control" name="transaction_date" id="editDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Account</label>
                        <select class="form-select" name="account_id" id="editAccount" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (R)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="editAmount" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" id="editCategory">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" name="description" id="editDescription">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Transaction Modal Handler
    const editModal = new bootstrap.Modal(document.getElementById('editTransactionModal'));
    
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('editDate').value = this.dataset.date;
            document.getElementById('editType').value = this.dataset.type;
            document.getElementById('editAccount').value = this.dataset.account;
            document.getElementById('editAmount').value = this.dataset.amount;
            document.getElementById('editDescription').value = this.dataset.description;
            document.getElementById('editCategory').value = this.dataset.category || '';
            
            editModal.show();
        });
    });

    // Filter Functionality
    function applyFilters() {
        const type = document.getElementById('filterType').value.toLowerCase();
        const account = document.getElementById('filterAccount').value;
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value;
        
        document.querySelectorAll('tbody tr').forEach(row => {
            const rowType = row.querySelector('td:nth-child(5)').classList.contains('text-success') ? 'income' : 'expense';
            const rowAccount = row.querySelector('td:nth-child(3)').textContent.trim();
            const rowDate = new Date(row.querySelector('td:nth-child(1)').textContent);
            
            const typeMatch = !type || rowType === type;
            const accountMatch = !account || rowAccount === account;
            const dateMatch = (!startDate || rowDate >= new Date(startDate)) && 
                             (!endDate || rowDate <= new Date(endDate));
            
            row.style.display = typeMatch && accountMatch && dateMatch ? '' : 'none';
        });
    }
    
    // Add event listeners to filters
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterAccount').addEventListener('change', applyFilters);
    document.getElementById('filterStartDate').addEventListener('change', applyFilters);
    document.getElementById('filterEndDate').addEventListener('change', applyFilters);
});
</script>

<?php 
require_once '../includes/footer.php';
?>