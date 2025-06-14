<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

// Fetch quotation with client details
$stmt = $pdo->prepare("SELECT q.*, c.name as client_name, c.company, c.email, c.phone, c.address, c.city, c.state, c.postal_code, c.country
                       FROM quotations q
                       JOIN customers c ON q.client_id = c.id
                       WHERE q.id = ?");
$stmt->execute([$id]);
$quotation = $stmt->fetch();

if (!$quotation) {
    header('Location: list.php');
    exit;
}

// Fetch quotation items
$items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Calculate totals
$subtotal = array_reduce($items, function($carry, $item) {
    return $carry + ($item['quantity'] * $item['unit_price']);
}, 0);

$tax = $subtotal * ($quotation['tax_rate'] / 100);
$total = $subtotal + $tax - $quotation['discount'];
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Quotation #<?= $quotation['quotation_number'] ?></h4>
                    <div>
                        <a href="download.php?id=<?= $quotation['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-file-pdf me-1"></i> Download PDF
                        </a>
                        <span class="badge bg-<?= 
                            $quotation['status'] === 'accepted' ? 'success' : 
                            ($quotation['status'] === 'rejected' ? 'danger' : 
                            ($quotation['status'] === 'expired' ? 'warning' : 
                            ($quotation['status'] === 'sent' ? 'primary' : 'secondary'))) 
                        ?>">
                            <?= ucfirst($quotation['status']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>From</h5>
                            <p>
                                <strong>Binary Intel (Pty) Ltd</strong><br>
                                10 Samson Avenue<br>
                                Newcastle, South Africa<br>
                                2940
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h5>Quotation To</h5>
                            <p>
                                <strong><?= htmlspecialchars($quotation['client_name']) ?></strong><br>
                                <?php if ($quotation['company']): ?>
                                    <?= htmlspecialchars($quotation['company']) ?><br>
                                <?php endif; ?>
                                <?= htmlspecialchars($quotation['address']) ?><br>
                                <?= htmlspecialchars($quotation['city']) ?>, <?= htmlspecialchars($quotation['state']) ?> <?= htmlspecialchars($quotation['postal_code']) ?><br>
                                <?= htmlspecialchars($quotation['country']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong>Quotation Date:</strong> <?= date('M j, Y', strtotime($quotation['date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Expiry Date:</strong> <?= date('M j, Y', strtotime($quotation['expiry_date'])) ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?= 
                                    $quotation['status'] === 'accepted' ? 'success' : 
                                    ($quotation['status'] === 'rejected' ? 'danger' : 
                                    ($quotation['status'] === 'expired' ? 'warning' : 
                                    ($quotation['status'] === 'sent' ? 'primary' : 'secondary'))) 
                                ?>">
                                    <?= ucfirst($quotation['status']) ?>
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
                                    <th>VAT</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['description']) ?></td>
                                    <td><?= number_format($item['quantity'], 2, ',', ' ') ?></td>
                                    <td>R <?= number_format($item['unit_price'], 2, ',', ' ') ?></td>
                                    <td><?= $item['tax_rate'] ?>%</td>
                                    <td>R <?= number_format($item['quantity'] * $item['unit_price'] * (1 + ($item['tax_rate'] / 100)), 2, ',', ' ') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end">Subtotal:</td>
                                    <td>R <?= number_format($subtotal, 2, ',', ' ') ?></td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">VAT (<?= $quotation['tax_rate'] ?>%):</td>
                                    <td>R <?= number_format($tax, 2, ',', ' ') ?></td>
                                </tr>
                                <?php if ($quotation['discount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end">Discount:</td>
                                    <td>-R <?= number_format($quotation['discount'], 2, ',', ' ') ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">R <?= number_format($total, 2, ',', ' ') ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($quotation['notes']): ?>
                    <div class="border-top pt-3">
                        <h6>Notes</h6>
                        <p><?= nl2br(htmlspecialchars($quotation['notes'])) ?></p>
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
                            <a href="edit.php?id=<?= $quotation['id'] ?>" class="btn btn-primary me-2">
                                <i class="fas fa-edit me-1"></i> Edit
                            </a>
                            <a href="convert.php?id=<?= $quotation['id'] ?>" class="btn btn-success me-2">
                                <i class="fas fa-exchange-alt me-1"></i> Convert to Invoice
                            </a>
                            <button class="btn btn-info">
                                <i class="fas fa-paper-plane me-1"></i> Send
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>