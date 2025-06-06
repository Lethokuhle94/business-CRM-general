<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Fetch invoices with client names
$query = "SELECT i.*, c.name as client_name 
          FROM invoices i
          JOIN customers c ON i.client_id = c.id
          ORDER BY i.date DESC";
$invoices = $pdo->query($query)->fetchAll();
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice me-2"></i>Invoices</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> New Invoice
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= $invoice['invoice_number'] ?></td>
                            <td><?= htmlspecialchars($invoice['client_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($invoice['date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($invoice['due_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $invoice['status'] === 'paid' ? 'success' : 
                                    ($invoice['status'] === 'overdue' ? 'danger' : 
                                    ($invoice['status'] === 'sent' ? 'primary' : 'secondary')) 
                                ?>">
                                    <?= ucfirst($invoice['status']) ?>
                                </span>
                            </td>
                            <td>$<?= number_format(calculateInvoiceTotal($invoice['id'], $pdo), 2) ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?= $invoice['id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $invoice['id'] ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $invoice['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
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

<?php 
function calculateInvoiceTotal($invoiceId, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(quantity * unit_price) as subtotal FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);
    $subtotal = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT tax_rate, discount FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    $tax = $subtotal * ($invoice['tax_rate'] / 100);
    return $subtotal + $tax - $invoice['discount'];
}

require_once '../includes/footer.php'; 
?>