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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

// Fetch accounts for dropdowns
$accounts = $pdo->query("SELECT * FROM financial_accounts WHERE user_id = " . $_SESSION['user_id'])->fetchAll();

// Fetch categories for dropdowns
$categories = $pdo->query("SELECT * FROM transaction_categories WHERE user_id = " . $_SESSION['user_id'] . " OR is_default = TRUE")->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-exchange-alt me-2"></i>Transactions</h2>
        <div>
            <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                <i class="fas fa-plus-circle me-1"></i> New Transaction
            </button>
            <a href="recycle_bin.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-trash-restore me-1"></i> Recycle Bin
            </a>
            <a href="list.php" class="btn btn-outline-secondary">
                <i class="fas fa-chart-pie me-1"></i> Summary
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error_message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

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
                        <?php foreach ($accounts as $account): ?>
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
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-3 text-muted"></i>
                                    <h5>No transactions found</h5>
                                    <p class="text-muted">Start by adding your first transaction</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                                        <i class="fas fa-plus-circle me-1"></i> Add Transaction
                                    </button>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($transaction['transaction_date'])) ?></td>
                                <td><?= htmlspecialchars($transaction['description']) ?></td>
                                <td><?= htmlspecialchars($transaction['account_name']) ?></td>
                                <td><?= $transaction['category_name'] ? htmlspecialchars($transaction['category_name']) : '-' ?></td>
                                <td class="text-end <?= $transaction['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                    <?= $transaction['type'] === 'income' ? '+' : '-' ?> 
                                    R<?= number_format($transaction['amount'], 2) ?>
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
                                        <button class="btn btn-outline-danger delete-btn" 
                                                data-id="<?= $transaction['id'] ?>"
                                                data-description="<?= htmlspecialchars($transaction['description']) ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" data-type="<?= $category['type'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
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
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label">Category</label>
                            <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus-circle"></i> New Category
                            </button>
                        </div>
                        <select class="form-select" name="category_id" id="editCategory">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" data-type="<?= $category['type'] ?>">
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Move to Recycle Bin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="delete_transaction.php">
                <input type="hidden" name="delete_id" id="deleteId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-body">
                    <p>You are about to move this transaction to the recycle bin:</p>
                    <p><strong id="deleteDescription"></strong></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will adjust your account balance. You can restore it later from the recycle bin.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Move to Recycle Bin
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Category Modal (Same as before) -->

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
            
            // Trigger type change to filter categories
            filterCategoryOptions('editType', 'editCategory');
            
            editModal.show();
        });
    });

    // Delete Button Handler
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('deleteId').value = this.dataset.id;
            document.getElementById('deleteDescription').textContent = this.dataset.description;
            deleteModal.show();
        });
    });

    // Filter Functionality
    function applyFilters() {
        const type = document.getElementById('filterType').value.toLowerCase();
        const account = document.getElementById('filterAccount').value;
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value;
        
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.querySelector('td:first-child').colSpan) return; // Skip empty row
            
            const rowType = row.querySelector('td:nth-child(5)').classList.contains('text-success') ? 'income' : 'expense';
            const rowAccount = row.querySelector('td:nth-child(3)').textContent.trim();
            const rowDate = new Date(row.querySelector('td:first-child').textContent);
            
            const typeMatch = !type || rowType === type;
            const accountMatch = !account || rowAccount === document.querySelector(`#filterAccount option[value="${account}"]`).text;
            const dateMatch = (!startDate || rowDate >= new Date(startDate)) && 
                             (!endDate || rowDate <= new Date(endDate));
            
            row.style.display = typeMatch && accountMatch && dateMatch ? '' : 'none';
        });
    }
    
    // Category Filtering
    function filterCategoryOptions(typeSelectId, categorySelectId) {
        const typeSelect = document.getElementById(typeSelectId);
        const categorySelect = document.getElementById(categorySelectId);
        
        if (typeSelect && categorySelect) {
            const selectedType = typeSelect.value;
            const options = categorySelect.querySelectorAll('option');
            
            options.forEach(option => {
                if (option.value === '') return;
                
                const optionType = option.dataset.type;
                if (selectedType === 'income' && optionType === 'income') {
                    option.style.display = '';
                } else if (selectedType === 'expense' && optionType === 'expense') {
                    option.style.display = '';
                } else if (selectedType === 'transfer') {
                    option.style.display = 'none';
                } else {
                    option.style.display = 'none';
                }
            });
        }
    }
    
    // Initialize filtering for add modal
    document.getElementById('transactionType').addEventListener('change', function() {
        filterCategoryOptions('transactionType', 'transactionCategory');
    });
    
    // Add event listeners to filters
    document.getElementById('filterType').addEventListener('change', applyFilters);
    document.getElementById('filterAccount').addEventListener('change', applyFilters);
    document.getElementById('filterStartDate').addEventListener('change', applyFilters);
    document.getElementById('filterEndDate').addEventListener('change', applyFilters);
    
    // Initialize filters
    applyFilters();
    filterCategoryOptions('transactionType', 'transactionCategory');
});
</script>

<?php 
require_once '../includes/footer.php';
?>