<?php
require_once '../includes/config.php';
require_once '../includes/header.php';
require_once '../includes/navigation.php';

// Search and filter logic
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ? OR company LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status) && in_array($status, ['active', 'inactive', 'lead'])) {
    $query .= " AND status = ?";
    $params[] = $status;
}

$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-address-book me-2"></i>Client List</h2>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Add New Client
        </a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Search clients..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="lead" <?= $status === 'lead' ? 'selected' : '' ?>>Lead</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="list.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-sync-alt me-1"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?= $customer['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($customer['name']) ?>
                                <?php if ($customer['company']): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($customer['company']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($customer['company']) ?></td>
                            <td>
                                <div><?= htmlspecialchars($customer['email']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($customer['phone']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $customer['status'] === 'active' ? 'success' : 
                                    ($customer['status'] === 'lead' ? 'warning' : 'secondary') 
                                ?>">
                                    <?= ucfirst($customer['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-outline-primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= $customer['id'] ?>" class="btn btn-outline-secondary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete.php?id=<?= $customer['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure?')">
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

<?php require_once '../includes/footer.php'; ?>