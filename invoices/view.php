<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch invoice with client details
$stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.company, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
                       FROM invoices i
                       JOIN customers c ON i.client_id = c.id
                       WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: list.php');
    exit;
}

// Fetch invoice items
$items = $pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Calculate totals
$subtotal = array_reduce($items, function($carry, $item) {
    return $carry + ($item['quantity'] * $item['unit_price']);
}, 0);

$tax = $subtotal * ($invoice['tax_rate'] / 100);
$total = $subtotal + $tax - $invoice['discount'];
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Invoice #<?= $invoice['invoice_number'] ?></h4>
                    <div>
                        <span class="badge bg-<?= 
                            $invoice['status'] === 'paid' ? 'success' : 
                            ($invoice['status'] === 'overdue' ? 'danger' : 
                            ($invoice['status'] === 'sent' ? 'primary' : 'secondary')) 
                        ?>">
                            <?= ucfirst($invoice['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>From</h5>
                            <p>
                                <strong><?= htmlspecialchars($APP_NAME) ?></strong><br>
                                123 Business Street<br>
                                City, State 12345<br>
                                United States
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5>Bill To</h5>
                            <p>
                                <strong><?= htmlspecialchars($invoice['client_name']) ?></strong><br>
                                <?php if ($invoice['company']): ?>
                                    <?= htmlspecialchars($invoice['company']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($invoice['address']) ?><br>
                                <?= htmlspecialchars($invoice['city']) ?>, <?= htmlspecialchars($invoice['state']) ?> <?= htmlspecialchars($invoice['postal_code']) ?><br>
                                <?= htmlspecialchars($invoice['country']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Invoice Date:</strong> <?= date('M j, Y', strtotime($invoice['date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Due Date:</strong> <?= date('M j, Y', strtotime($invoice['due_date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $invoice['status'] === 'paid' ? 'success' : 
                                    ($invoice['status'] === 'overdue' ? 'danger' : 
                                    ($invoice['status'] === 'sent' ? 'primary' : 'secondary')) 
                                ?>">
                                    <?= ucfirst($invoice['status']) ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Tax</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>R<?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['tax_rate'] ?>%</td>
                                    <td>R<?= number_format($item['quantity'] * $item['unit_price'] * (1 + ($item['tax_rate'] / 100)), 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end">Subtotal:</td>
                                    <td>R<?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Tax (<?= $invoice['tax_rate'] ?>%):</td>
                                    <td>R<?= number_format($tax, 2) ?></td>
                                </tr>
                                <?php if ($invoice['discount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end">Discount:</td>
                                    <td>-R<?= number_format($invoice['discount'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">R<?= number_format($total, 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($invoice['notes']): ?>
                    <div class="border-top pt-3">
                        <h6>Notes</h6>
                        <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to List
                            </a>
                        </div>
                        <div>
                            <a href="edit.php?id=<?= $invoice['id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <button class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i> Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Watermark Section -->
            <div class="text-center mt-4" style="opacity: 0.6;">
                <img src="/assets/images/watermarks/1.png" alt="Probi Notes Logo" style="height: 20px; vertical-align: middle;">
                <span class="text-muted" style="font-size: 0.9rem;">Created using Probi Notes</span>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>