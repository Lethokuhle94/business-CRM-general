<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Fetch all customers for dropdown
$customers = $pdo->query("SELECT id, name, company FROM customers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Create invoice
        $stmt = $pdo->prepare("INSERT INTO invoices 
            (invoice_number, client_id, date, due_date, status, tax_rate, discount, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
        $stmt->execute([
            $invoiceNumber,
            $_POST['client_id'],
            $_POST['date'],
            $_POST['due_date'],
            'draft',
            $_POST['tax_rate'],
            $_POST['discount'],
            $_POST['notes']
        ]);
        
        $invoiceId = $pdo->lastInsertId();
        
        // Add items
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description'])) {
                $stmt = $pdo->prepare("INSERT INTO invoice_items 
                    (invoice_id, description, quantity, unit_price, tax_rate) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $invoiceId,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['item_tax_rate']
                ]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = 'Invoice created successfully!';
        header("Location: edit.php?id=$invoiceId");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error creating invoice: ' . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Create New Invoice</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form id="invoiceForm" method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Client*</label>
                                <div class="input-group">
                                    <select class="form-select" id="client_id" name="client_id" required>
                                        <option value="">Select Client</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>">
                                                <?= htmlspecialchars($customer['name']) ?>
                                                <?= $customer['company'] ? ' (' . htmlspecialchars($customer['company']) . ')' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newClientModal">
                                        <i class="fas fa-plus"></i> New
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date*</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label">Due Date*</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Description</th>
                                        <th width="15%">Quantity</th>
                                        <th width="15%">Unit Price</th>
                                        <th width="15%">Tax Rate</th>
                                        <th width="10%">Amount</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input type="text" class="form-control" name="items[0][description]" required></td>
                                        <td><input type="number" class="form-control quantity" name="items[0][quantity]" value="1" min="0" step="0.01" required></td>
                                        <td><input type="number" class="form-control unit-price" name="items[0][unit_price]" value="0" min="0" step="0.01" required></td>
                                        <td>
                                            <select class="form-select tax-rate" name="items[0][item_tax_rate]">
                                                <option value="0">0%</option>
                                                <option value="10">10%</option>
                                                <option value="20">20%</option>
                                            </select>
                                        </td>
                                        <td class="amount">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="6">
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addItem">
                                                <i class="fas fa-plus me-1"></i> Add Item
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span id="subtotal">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <span>Tax:</span>
                                                <select class="form-select form-select-sm d-inline-block w-auto ms-2" name="tax_rate">
                                                    <option value="0">0%</option>
                                                    <option value="10" selected>10%</option>
                                                    <option value="20">20%</option>
                                                </select>
                                            </div>
                                            <span id="taxAmount">$0.00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <span>Discount:</span>
                                                <input type="number" class="form-control form-control-sm d-inline-block w-auto ms-2" name="discount" value="0" min="0" step="0.01">
                                            </div>
                                            <span id="discountAmount">$0.00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span id="total">$0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Save Invoice
                            </button>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- New Client Modal -->
<div class="modal fade" id="newClientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickClientForm" action="../clients/quick_create.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name*</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" class="form-control" name="company">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Client</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Add new item row
let itemCount = 1;
document.getElementById('addItem').addEventListener('click', function() {
    const tbody = document.querySelector('#itemsTable tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
        <td><input type="text" class="form-control" name="items[${itemCount}][description]" required></td>
        <td><input type="number" class="form-control quantity" name="items[${itemCount}][quantity]" value="1" min="0" step="0.01" required></td>
        <td><input type="number" class="form-control unit-price" name="items[${itemCount}][unit_price]" value="0" min="0" step="0.01" required></td>
        <td>
            <select class="form-select tax-rate" name="items[${itemCount}][item_tax_rate]">
                <option value="0">0%</option>
                <option value="10">10%</option>
                <option value="20">20%</option>
            </select>
        </td>
        <td class="amount">$0.00</td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    itemCount++;
});

// Calculate amounts
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || e.target.classList.contains('tax-rate')) {
        const row = e.target.closest('tr');
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        const taxRate = parseFloat(row.querySelector('.tax-rate').value) || 0;
        
        const subtotal = quantity * unitPrice;
        const taxAmount = subtotal * (taxRate / 100);
        const amount = subtotal + taxAmount;
        
        row.querySelector('.amount').textContent = '$' + amount.toFixed(2);
        calculateTotals();
    }
});

// Remove item
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
        e.target.closest('tr').remove();
        calculateTotals();
    }
});

// Calculate totals
function calculateTotals() {
    let subtotal = 0;
    document.querySelectorAll('#itemsTable tbody tr').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity').value) || 0;
        const unitPrice = parseFloat(row.querySelector('.unit-price').value) || 0;
        subtotal += quantity * unitPrice;
    });
    
    const taxRate = parseFloat(document.querySelector('select[name="tax_rate"]').value) || 0;
    const discount = parseFloat(document.querySelector('input[name="discount"]').value) || 0;
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + taxAmount - discount;
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('taxAmount').textContent = '$' + taxAmount.toFixed(2);
    document.getElementById('discountAmount').textContent = '$' + discount.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}

// Quick client form submission
document.getElementById('quickClientForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch(this.action, {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('client_id');
            const option = document.createElement('option');
            option.value = data.id;
            option.textContent = data.name + (data.company ? ' (' + data.company + ')' : '');
            option.selected = true;
            select.appendChild(option);
            $('#newClientModal').modal('hide');
            this.reset();
        } else {
            alert(data.error || 'Error creating client');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>