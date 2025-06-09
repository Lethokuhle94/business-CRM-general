<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Include TCPDF library
require_once '../lib/tcpdf/tcpdf.php';

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

// Generate PDF function
function generateInvoicePDF($invoiceData, $invoiceItems, $clientInfo) {
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Company Name');
    $pdf->SetTitle('Invoice ' . $invoiceData['invoice_number']);
    $pdf->SetSubject('Invoice');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Logo (using placeholder - replace with your actual logo path)
    $logoPath = '../images/company_logo.png'; // Update this path
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    } else {
        $pdf->Image('https://placehold.co/150x50?text=Company+Logo', 15, 15, 40, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    
    // Invoice title and number
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->Cell(0, 10, 'INVOICE', 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'No: ' . $invoiceData['invoice_number'], 0, 1, 'R');
    
    // Date
    $pdf->Cell(0, 5, 'Date: ' . date('d/m/Y', strtotime($invoiceData['date'])), 0, 1, 'R');
    $pdf->Cell(0, 5, 'Due Date: ' . date('d/m/Y', strtotime($invoiceData['due_date'])), 0, 1, 'R');
    
    // Spacer
    $pdf->Ln(10);
    
    // From/To addresses
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(85, 5, 'From:', 0, 0);
    $pdf->Cell(85, 5, 'To:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    
    // Update these with your company details
    $pdf->MultiCell(85, 5, "Your Company Name\n123 Business Street\nJohannesburg\nSouth Africa\nVAT: 123456789", 0, 'L', false, 0);
    $pdf->MultiCell(85, 5, $clientInfo['name'] . "\n" . ($clientInfo['company'] ? $clientInfo['company'] . "\n" : "") . 
        ($clientInfo['address'] ? $clientInfo['address'] . "\n" : "") . 
        ($clientInfo['email'] ? $clientInfo['email'] . "\n" : "") . 
        ($clientInfo['phone'] ? $clientInfo['phone'] : ""), 0, 'L', false, 1);
    
    // Spacer
    $pdf->Ln(10);
    
    // Invoice items table header
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(90, 7, 'Description', 1, 0, 'L');
    $pdf->Cell(20, 7, 'Qty', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Unit Price', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Tax', 1, 0, 'R');
    $pdf->Cell(25, 7, 'Amount', 1, 1, 'R');
    
    // Invoice items
    $pdf->SetFont('helvetica', '', 10);
    $subtotal = 0;
    foreach ($invoiceItems as $item) {
        $itemTotal = $item['quantity'] * $item['unit_price'];
        $itemTax = $itemTotal * ($item['tax_rate'] / 100);
        $subtotal += $itemTotal;
        
        $pdf->Cell(90, 7, $item['description'], 1, 0, 'L');
        $pdf->Cell(20, 7, number_format($item['quantity'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($item['unit_price'], 2, ',', ' '), 1, 0, 'R');
        $pdf->Cell(25, 7, $item['tax_rate'] . '%', 1, 0, 'R');
        $pdf->Cell(25, 7, 'R ' . number_format($itemTotal + $itemTax, 2, ',', ' '), 1, 1, 'R');
    }
    
    // Calculate totals
    $tax = $subtotal * ($invoiceData['tax_rate'] / 100);
    $total = $subtotal + $tax - $invoiceData['discount'];
    
    // Display totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(135, 7, 'Subtotal:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($subtotal, 2, ',', ' '), 1, 1, 'R');
    
    $pdf->Cell(135, 7, 'Tax (' . $invoiceData['tax_rate'] . '%):', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($tax, 2, ',', ' '), 1, 1, 'R');
    
    $pdf->Cell(135, 7, 'Discount:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($invoiceData['discount'], 2, ',', ' '), 1, 1, 'R');
    
    $pdf->Cell(135, 7, 'Total:', 0, 0, 'R');
    $pdf->Cell(25, 7, 'R ' . number_format($total, 2, ',', ' '), 1, 1, 'R');
    
    // Notes
    if (!empty($invoiceData['notes'])) {
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'I', 10);
        $pdf->MultiCell(0, 5, 'Notes: ' . $invoiceData['notes'], 0, 'L');
    }
    
    // Footer
    $pdf->Ln(15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Thank you for your business!', 0, 1, 'C');
    
    return $pdf;
}

// Handle PDF download
if (isset($_GET['download_pdf'])) {
    $invoiceId = (int)$_GET['download_pdf'];
    
    // Fetch invoice data
    $stmt = $pdo->prepare("SELECT i.*, c.* FROM invoices i JOIN customers c ON i.client_id = c.id WHERE i.id = ?");
    $stmt->execute([$invoiceId]);
    $invoiceData = $stmt->fetch();
    
    // Fetch invoice items
    $stmt = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $invoiceItems = $stmt->fetchAll();
    
    // Generate PDF
    $pdf = generateInvoicePDF($invoiceData, $invoiceItems, $invoiceData);
    
    // Output PDF
    $pdf->Output('invoice_' . $invoiceData['invoice_number'] . '.pdf', 'D');
    exit;
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-4">
                <img src="../images/company_logo.png" alt="Company Logo" class="img-fluid mb-3" style="max-height: 100px;">
                <h2 class="mb-1">Invoice System</h2>
                <p class="text-muted">Create and manage your invoices</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Create New Invoice</h4>
                    <?php if (isset($_GET['id'])): ?>
                        <div>
                            <a href="?download_pdf=<?= $_GET['id'] ?>" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-file-pdf me-1"></i> Download PDF
                            </a>
                            <a href="edit.php?id=<?= $_GET['id'] ?>" class="btn btn-outline-light btn-sm">
                                <i class="fas fa-edit me-1"></i> Edit Invoice
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <form id="invoiceForm" method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="client_id" class="form-label">Client*</label>
                                <div class="input-group">
                                    <select class="form-select" id="client_id" name="client_id" required>
                                        <option value="">Select Client</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" <?= isset($_POST['client_id']) && $_POST['client_id'] == $customer['id'] ? 'selected' : '' ?>>
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
                                <input type="date" class="form-control" id="date" name="date" value="<?= isset($_POST['date']) ? $_POST['date'] : date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="due_date" class="form-label">Due Date*</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" value="<?= isset($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d', strtotime('+30 days')) ?>" required>
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
                                    <?php 
                                    $itemIndex = 0;
                                    if (isset($_POST['items']) && is_array($_POST['items'])): 
                                        foreach ($_POST['items'] as $index => $item): 
                                            if (!empty($item['description'])): ?>
                                                <tr>
                                                    <td><input type="text" class="form-control" name="items[<?= $index ?>][description]" value="<?= htmlspecialchars($item['description']) ?>" required></td>
                                                    <td><input type="number" class="form-control quantity" name="items[<?= $index ?>][quantity]" value="<?= $item['quantity'] ?>" min="0" step="0.01" required></td>
                                                    <td><input type="number" class="form-control unit-price" name="items[<?= $index ?>][unit_price]" value="<?= $item['unit_price'] ?>" min="0" step="0.01" required></td>
                                                    <td>
                                                        <select class="form-select tax-rate" name="items[<?= $index ?>][item_tax_rate]">
                                                            <option value="0" <?= $item['item_tax_rate'] == 0 ? 'selected' : '' ?>>0%</option>
                                                            <option value="15" <?= $item['item_tax_rate'] == 15 ? 'selected' : '' ?>>15%</option>
                                                            <option value="20" <?= $item['item_tax_rate'] == 20 ? 'selected' : '' ?>>20%</option>
                                                        </select>
                                                    </td>
                                                    <td class="amount">R 0,00</td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php 
                                            $itemIndex = $index + 1;
                                            endif;
                                        endforeach; 
                                    else: ?>
                                        <tr>
                                            <td><input type="text" class="form-control" name="items[0][description]" required></td>
                                            <td><input type="number" class="form-control quantity" name="items[0][quantity]" value="1" min="0" step="0.01" required></td>
                                            <td><input type="number" class="form-control unit-price" name="items[0][unit_price]" value="0" min="0" step="0.01" required></td>
                                            <td>
                                                <select class="form-select tax-rate" name="items[0][item_tax_rate]">
                                                    <option value="0">0%</option>
                                                    <option value="15">15%</option>
                                                    <option value="20">20%</option>
                                                </select>
                                            </td>
                                            <td class="amount">R 0,00</td>
                                            <td></td>
                                        </tr>
                                    <?php endif; ?>
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
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?= isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : '' ?></textarea>
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
                                                    <option value="0" <?= isset($_POST['tax_rate']) && $_POST['tax_rate'] == 0 ? 'selected' : '' ?>>0%</option>
                                                    <option value="15" <?= !isset($_POST['tax_rate']) || $_POST['tax_rate'] == 15 ? 'selected' : '' ?>>15%</option>
                                                    <option value="20" <?= isset($_POST['tax_rate']) && $_POST['tax_rate'] == 20 ? 'selected' : '' ?>>20%</option>
                                                </select>
                                            </div>
                                            <span id="taxAmount">R 0,00</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <div>
                                                <span>Discount:</span>
                                                <input type="number" class="form-control form-control-sm d-inline-block w-auto ms-2" name="discount" value="<?= isset($_POST['discount']) ? $_POST['discount'] : 0 ?>" min="0" step="0.01">
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
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="2"></textarea>
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
// Format number to South African Rand
function formatZAR(amount) {
    return 'R ' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,').replace('.', ',');
}

// Add new item row
let itemCount = <?= $itemIndex + 1 ?>;
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

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', calculateTotals);
</script>

<?php require_once '../includes/footer.php'; ?>