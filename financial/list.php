<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Safe function to escape output
function safe_output($data) {
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

try {
    // Get all accounts for the user
    $accounts = $pdo->prepare("SELECT * FROM financial_accounts WHERE user_id = ? ORDER BY account_name");
    $accounts->execute([$_SESSION['user_id']]);
    $accounts = $accounts->fetchAll();

    // Get all transactions for the user (last 30 days)
    $transactions = $pdo->prepare("
        SELECT t.*, a.account_name, c.name as category_name 
        FROM transactions t
        LEFT JOIN financial_accounts a ON t.account_id = a.id
        LEFT JOIN transaction_categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY t.transaction_date DESC
    ");
    $transactions->execute([$_SESSION['user_id']]);
    $transactions = $transactions->fetchAll();

    // Get all categories for the user
    $categories = $pdo->prepare("SELECT * FROM transaction_categories WHERE user_id = ? OR is_default = TRUE ORDER BY type, name");
    $categories->execute([$_SESSION['user_id']]);
    $categories = $categories->fetchAll();

    // Calculate totals
    $incomeTotal = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'income' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $incomeTotal->execute([$_SESSION['user_id']]);
    $incomeTotal = $incomeTotal->fetch()['total'] ?? 0;

    $expenseTotal = $pdo->prepare("SELECT SUM(amount) as total FROM transactions WHERE user_id = ? AND type = 'expense' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $expenseTotal->execute([$_SESSION['user_id']]);
    $expenseTotal = $expenseTotal->fetch()['total'] ?? 0;

    $netTotal = $incomeTotal - $expenseTotal;
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error loading financial data";
    header('Location: /dashboard.php');
    exit;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Financial Management</h2>
                <div>
                    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="fas fa-plus me-1"></i> Add Transaction
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="fas fa-piggy-bank me-1"></i> Add Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Income</h6>
                    <h4 class="text-success">R <?= number_format($incomeTotal, 2) ?></h4>
                    <small class="text-muted">Last 30 days</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Total Expenses</h6>
                    <h4 class="text-danger">R <?= number_format($expenseTotal, 2) ?></h4>
                    <small class="text-muted">Last 30 days</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-muted">Net Total</h6>
                    <h4 class="<?= $netTotal >= 0 ? 'text-success' : 'text-danger' ?>">R <?= number_format($netTotal, 2) ?></h4>
                    <small class="text-muted">Last 30 days</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Accounts List -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Accounts</h5>
                    <span class="badge bg-primary"><?= count($accounts) ?></span>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($accounts as $account): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?= safe_output($account['account_name']) ?></h6>
                                <small class="text-muted"><?= safe_output(ucfirst(str_replace('_', ' ', $account['account_type']))) ?></small>
                            </div>
                            <span class="fw-bold">R <?= number_format($account['current_balance'], 2) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Transactions</h5>
                    <div>
                        <a href="transactions.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Account</th>
                                    <th>Category</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= date('d M Y', strtotime($transaction['transaction_date'])) ?></td>
                                    <td><?= safe_output($transaction['description'] ?? '') ?></td>
                                    <td><?= safe_output($transaction['account_name'] ?? '') ?></td>
                                    <td><?= safe_output($transaction['category_name'] ?? '-') ?></td>
                                    <td class="text-end <?= $transaction['type'] === 'income' ? 'text-success' : 'text-danger' ?>">
                                        <?= $transaction['type'] === 'income' ? '+' : '-' ?> R <?= number_format($transaction['amount'], 2) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Create New Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="process_category.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="categoryType" class="form-label">Type</label>
                            <select class="form-select" id="categoryType" name="type" required>
                                <option value="income">Income</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="categoryColor" class="form-label">Color (Hex Code)</label>
                            <input type="color" class="form-control form-control-color" id="categoryColor" name="color" value="#6c757d">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionModalLabel">Add New Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_transaction.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <div class="mb-3">
                        <label for="transactionType" class="form-label">Type</label>
                        <select class="form-select" id="transactionType" name="type" required>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                            <option value="transfer">Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transactionDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="transactionDate" name="transaction_date" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="transactionAccount" class="form-label">Account</label>
                        <select class="form-select" id="transactionAccount" name="account_id" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= safe_output($account['account_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transactionAmount" class="form-label">Amount (R)</label>
                        <input type="number" step="0.01" class="form-control" id="transactionAmount" name="amount" required>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <label for="transactionCategory" class="form-label">Category</label>
                            <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus-circle"></i> New Category
                            </button>
                        </div>
                        <select class="form-select" id="transactionCategory" name="category_id">
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" data-type="<?= safe_output($category['type']) ?>">
                                <?= safe_output($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transactionDescription" class="form-label">Description</label>
                        <input type="text" class="form-control" id="transactionDescription" name="description">
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

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAccountModalLabel">Add New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="process_account.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                    <div class="mb-3">
                        <label for="accountName" class="form-label">Account Name</label>
                        <input type="text" class="form-control" id="accountName" name="account_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountType" class="form-label">Account Type</label>
                        <select class="form-select" id="accountType" name="account_type" required>
                            <option value="bank">Bank Account</option>
                            <option value="cash">Cash</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="investment">Investment</option>
                            <option value="loan">Loan</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="accountNumber" class="form-label">Account Number (Optional)</label>
                        <input type="text" class="form-control" id="accountNumber" name="account_number">
                    </div>
                    <div class="mb-3">
                        <label for="bankName" class="form-label">Bank Name (Optional)</label>
                        <input type="text" class="form-control" id="bankName" name="bank_name">
                    </div>
                    <div class="mb-3">
                        <label for="openingBalance" class="form-label">Opening Balance (R)</label>
                        <input type="number" step="0.01" class="form-control" id="openingBalance" name="opening_balance" value="0.00" required>
                    </div>
                    <div class="mb-3">
                        <label for="accountDescription" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="accountDescription" name="description" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Update category options based on transaction type
    const typeSelect = document.getElementById('transactionType');
    const categorySelect = document.getElementById('transactionCategory');
    
    typeSelect.addEventListener('change', function() {
        const selectedType = this.value;
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
        
        // Reset to default option
        categorySelect.value = '';
    });
});

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Existing type-category filtering code
    const typeSelect = document.getElementById('transactionType');
    const categorySelect = document.getElementById('transactionCategory');
    
    typeSelect.addEventListener('change', function() {
        // ... existing type filtering code ...
    });

    // ===== ADD THIS NEW CODE BELOW =====
    
    // Refresh categories after creating a new one
    const categoryModal = document.getElementById('addCategoryModal');
    if (categoryModal) {
        categoryModal.addEventListener('hidden.bs.modal', function() {
            fetchCategories();
        });
    }

    async function fetchCategories() {
        try {
            const response = await fetch(`get_categories.php?user_id=<?= $_SESSION['user_id'] ?>`);
            if (!response.ok) throw new Error('Network error');
            
            const categories = await response.json();
            const select = document.getElementById('transactionCategory');
            
            // Keep the first "Select Category" option
            while (select.options.length > 1) {
                select.remove(1);
            }
            
            // Add refreshed categories
            categories.forEach(category => {
                const option = new Option(category.name, category.id);
                option.dataset.type = category.type;
                select.add(option);
            });
            
            // Re-apply type filtering
            typeSelect.dispatchEvent(new Event('change'));
        } catch (error) {
            console.error('Error loading categories:', error);
            alert('Failed to refresh categories. Please reload the page.');
        }
    }
});
</script>

</script>

<?php require_once '../includes/footer.php'; ?>