<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch quotation data
$quotation = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$quotation->execute([$id]);
$quotation = $quotation->fetch();

if (!$quotation) {
    header('Location: list.php');
    exit;
}

// Fetch quotation items
$items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Fetch all customers
$customers = $pdo->query("SELECT id, name, company FROM customers ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Update quotation
        $stmt = $pdo->prepare("UPDATE quotations SET 
            client_id = ?, date = ?, expiry_date = ?, tax_rate = ?, discount = ?, notes = ?
            WHERE id = ?");
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['date'],
            $_POST['expiry_date'],
            $_POST['tax_rate'],
            $_POST['discount'],
            $_POST['notes'],
            $id
        ]);
        
        // Delete existing items
        $stmt = $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
        $stmt->execute([$id]);
        
        // Add updated items
        foreach ($_POST['items'] as $item) {
            if (!empty($item['description'])) {
                $stmt = $pdo->prepare("INSERT INTO quotation_items 
                    (quotation_id, description, quantity, unit_price, tax_rate) 
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['item_tax_rate']
                ]);
            }
        }
        
        $pdo->commit();
        $_SESSION['success'] = 'Quotation updated successfully!';
        header("Location: view.php?id=$id");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error updating quotation: ' . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Quotation #<?= $quotation['quotation_number'] ?></h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form id="quotationForm" method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Client*</label>
                                <select class="form-select" id="client_id" name="client_id" required>
                                    <option value="">Select Client</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>" <?= $customer['id'] == $quotation['client_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($customer['name']) ?>
                                            <?= $customer['company'] ? ' (' . htmlspecialchars($customer['company']) . ')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date*</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?= $quotation['date'] ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="expiry_date" class="form-label">Expiry Date*</label>
                                <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?= $quotation['expiry_date'] ?>" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table" id="itemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40%">Description</th>
                                        <th width="15%">Quantity</th>
                                        <th width="15%">Unit Price (R)</th>
                                        <th width="15%">Tax Rate</th>
                                        <th width="10%">Amount (R)</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $index => $item): ?>
                                    <tr>
                                        <td><input type="text" class="form-control" name="items[<?= $index ?>][description]" value="<?= htmlspecialchars($item['description']) ?>" required></td>
                                        <td><input type="number" class="form-control quantity" name="items[<?= $index ?>][quantity]" value="<?= $item['quantity'] ?>" min="0" step="0.01" required></td>
                                        <td><input type="number" class="form-control unit-price" name="items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?>" min="0" step="0.01" required></td>
                                        <td>
                                            <select class="form-select tax-rate" name="items[<?= $index ?>][item_tax_rate]">
                                                <option value="0" <?= $item['tax_rate'] == 0 ? 'selected' : '' ?>>0%</option>
                                                <option value="15" <?= $item['tax_rate'] == 15 ? 'selected' : '' ?>>15%</option>
                                                <option value="20" <?= $item['tax_rate'] == 20 ? 'selected' : '' ?>>20%</option>
                                            </select>
                                        </td>
                                        <td class="amount">R <?= number_format($item['quantity'] * $item['unit_price'] * (1 + ($item['tax_rate'] / 100)), 2, ',', ' ') ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= htmlspecialchars($quotation['notes']) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 bg-light">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Subtotal:</span>
                                            <span id="subtotal">R 0,00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <span>Tax:</span>
                                                <select class="form-select form-select-sm d-inline-block w-auto ms-2" name="tax_rate">
                                                    <option value="0" <?= $quotation['tax_rate'] == 0 ? 'selected' : '' ?>>0%</option>
                                                    <option value="15" <?= $quotation['tax_rate'] == 15 ? 'selected' : '' ?>>15%</option>
                                                    <option value="20" <?= $quotation['tax_rate'] == 20 ? 'selected' : '' ?>>20%</option>
                                                </select>
                                            </div>
                                            <span id="taxAmount">R 0,00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <span>Discount:</span>
                                                <input type="number" class="form-control form-control-sm d-inline-block w-auto ms-2" name="discount" value="<?= $quotation['discount'] ?>" min="0" step="0.01">
                                            </div>
                                            <span id="discountAmount">R 0,00</span>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between fw-bold">
                                            <span>Total:</span>
                                            <span id="total">R 0,00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fas fa-save me-1"></i> Update Quotation
                            </button>
                            <a href="view.php?id=<?= $quotation['id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format number to South African Rand
    function formatZAR(amount) {
        return 'R ' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,').replace('.', ',');
    }

    // Add new item row
    let itemCount = <?= count($items) ?>;
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
                    <option value="15">15%</option>
                    <option value="20">20%</option>
                </select>
            </td>
            <td class="amount">R 0,00</td>
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
            
            row.querySelector('.amount').textContent = formatZAR(amount);
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
        
        document.getElementById('subtotal').textContent = formatZAR(subtotal);
        document.getElementById('taxAmount').textContent = formatZAR(taxAmount);
        document.getElementById('discountAmount').textContent = formatZAR(discount);
        document.getElementById('total').textContent = formatZAR(total);
    }

    // Initialize calculations on page load
    calculateTotals();
});
</script>

<?php require_once '../includes/footer.php'; ?>