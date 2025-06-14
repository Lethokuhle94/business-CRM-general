<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Fetch quotations with client names
$query = "SELECT q.*, c.name as client_name 
          FROM quotations q
          JOIN customers c ON q.client_id = c.id
          ORDER BY q.date DESC";
$quotations = $pdo->query($query)->fetchAll();

function calculateQuotationTotal($quotationId, $pdo) {
    $stmt = $pdo->prepare("SELECT SUM(quantity * unit_price) as subtotal FROM quotation_items WHERE quotation_id = ?");
    $stmt->execute([$quotationId]);
    $subtotal = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT tax_rate, discount FROM quotations WHERE id = ?");
    $stmt->execute([$quotationId]);
    $quotation = $stmt->fetch();
    
    $tax = $subtotal * ($quotation['tax_rate'] / 100);
    return $subtotal + $tax - $quotation['discount'];
}
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-alt me-2"></i>Quotations</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-1"></i> New Quotation
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Quotation #</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotations as $quotation): ?>
                        <tr>
                            <td><?= $quotation['quotation_number'] ?></td>
                            <td><?= htmlspecialchars($quotation['client_name']) ?></td>
                            <td><?= date('M j, Y', strtotime($quotation['date'])) ?></td>
                            <td><?= date('M j, Y', strtotime($quotation['expiry_date'])) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $quotation['status'] === 'accepted' ? 'success' : 
                                    ($quotation['status'] === 'rejected' ? 'danger' : 
                                    ($quotation['status'] === 'expired' ? 'warning' : 
                                    ($quotation['status'] === 'sent' ? 'primary' : 'secondary'))) 
                                ?>">
                                    <?= ucfirst($quotation['status']) ?>
                                </span>
                            </td>
                            <td>R<?= number_format(calculateQuotationTotal($quotation['id'], $pdo), 2, ',', ' ') ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?= $quotation['id'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $quotation['id'] ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $quotation['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="convert.php?id=<?= $quotation['id'] ?>" class="btn btn-outline-success" title="Convert to Invoice">
                                        <i class="fas fa-exchange-alt"></i>
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

<?php require_once '../includes/footer.php'; ?>