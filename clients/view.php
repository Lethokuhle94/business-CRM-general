<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    header('Location: list.php');
    exit;
}
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Client Details</h4>
                    <span class="badge bg-<?= 
                        $customer['status'] === 'active' ? 'success' : 
                        ($customer['status'] === 'lead' ? 'warning' : 'secondary') 
                    ?>">
                        <?= ucfirst($customer['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5><?= htmlspecialchars($customer['name']) ?></h5>
                            <?php if ($customer['company']): ?>
                                <p class="text-muted"><?= htmlspecialchars($customer['company']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p>Client Since: <?= date('M j, Y', strtotime($customer['created_at'])) ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Contact Information</h6>
                            <p>
                                <i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($customer['email']) ?><br>
                                <i class="fas fa-phone me-2"></i> <?= htmlspecialchars($customer['phone']) ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2">Billing Information</h6>
                            <p>
                                <?= htmlspecialchars($customer['address']) ?><br>
                                <?= htmlspecialchars($customer['city']) ?>, <?= htmlspecialchars($customer['state']) ?> <?= htmlspecialchars($customer['postal_code']) ?><br>
                                <?= htmlspecialchars($customer['country']) ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($customer['tax_id'] || $customer['payment_terms']): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2">Additional Details</h6>
                            <p>
                                <?php if ($customer['tax_id']): ?>
                                    <strong>Tax ID:</strong> <?= htmlspecialchars($customer['tax_id']) ?><br>
                                <?php endif; ?>
                                <strong>Payment Terms:</strong> <?= htmlspecialchars($customer['payment_terms']) ?>
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($customer['notes']): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2">Notes</h6>
                            <p><?= nl2br(htmlspecialchars($customer['notes'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <a href="edit.php?id=<?= $customer['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Edit Client
                    </a>
                    <a href="list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Recent Invoices</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">No invoices found for this client.</p>
                    <a href="../invoices/create.php?client_id=<?= $customer['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-plus me-1"></i> Create Invoice
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="mailto:<?= htmlspecialchars($customer['email']) ?>" class="btn btn-outline-primary text-start">
                            <i class="fas fa-envelope me-2"></i> Send Email
                        </a>
                        <a href="tel:<?= htmlspecialchars($customer['phone']) ?>" class="btn btn-outline-primary text-start">
                            <i class="fas fa-phone me-2"></i> Call Client
                        </a>
                        <button class="btn btn-outline-secondary text-start" data-bs-toggle="modal" data-bs-target="#notesModal">
                            <i class="fas fa-plus me-2"></i> Add Note
                        </button>
                        <a href="delete.php?id=<?= $customer['id'] ?>" class="btn btn-outline-danger text-start" onclick="return confirm('Are you sure?')">
                            <i class="fas fa-trash me-2"></i> Delete Client
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Notes Modal -->
<div class="modal fade" id="notesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Client Note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="add_note.php">
                <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" name="note" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>