<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navigation.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: $BASE_URL/login.php");
    exit;
}

// Get current settings or initialize defaults
$stmt = $pdo->prepare("SELECT * FROM settings WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$settings = $stmt->fetch();

if (!$settings) {
    // Initialize default settings
    $stmt = $pdo->prepare("INSERT INTO settings (user_id, currency) VALUES (?, 'USD')");
    $stmt->execute([$_SESSION['user_id']]);
    $settings = ['currency' => 'USD', 'logo_path' => null, 'company_name' => null, 'tax_id' => null, 'invoice_prefix' => 'INV'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $currency = $_POST['currency'];
        $company_name = $_POST['company_name'];
        $tax_id = $_POST['tax_id'];
        $invoice_prefix = $_POST['invoice_prefix'];
        
        // Handle file upload
        $logo_path = $settings['logo_path'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../uploads/logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_path = '/uploads/logos/' . $filename;
                // Delete old logo if exists
                if ($settings['logo_path'] && file_exists(__DIR__ . '/..' . $settings['logo_path'])) {
                    unlink(__DIR__ . '/..' . $settings['logo_path']);
                }
            }
        }
        
        // Update settings
        $stmt = $pdo->prepare("INSERT INTO settings 
            (user_id, currency, logo_path, company_name, tax_id, invoice_prefix) 
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            currency = VALUES(currency),
            logo_path = VALUES(logo_path),
            company_name = VALUES(company_name),
            tax_id = VALUES(tax_id),
            invoice_prefix = VALUES(invoice_prefix)");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $currency,
            $logo_path,
            $company_name,
            $tax_id,
            $invoice_prefix
        ]);
        
        $_SESSION['success'] = 'Settings updated successfully';
        header("Location: $BASE_URL/settings.php");
        exit;
    } catch (Exception $e) {
        $error = 'Error updating settings: ' . $e->getMessage();
    }
}
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Application Settings</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php elseif (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <!-- Currency Selection -->
                            <div class="col-md-6">
                                <label for="currency" class="form-label">Default Currency</label>
                                <select class="form-select" id="currency" name="currency" required>
                                    <option value="ZAR" <?= $settings['currency'] === 'ZAR' ? 'selected' : '' ?>>South African Rand (ZAR)</option>
                                    <option value="USD" <?= $settings['currency'] === 'USD' ? 'selected' : '' ?>>US Dollar (USD)</option>
                                    <option value="GBP" <?= $settings['currency'] === 'GBP' ? 'selected' : '' ?>>British Pound (GBP)</option>
                                    <option value="EUR" <?= $settings['currency'] === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                                    <option value="CNY" <?= $settings['currency'] === 'CNY' ? 'selected' : '' ?>>Chinese Yuan (CNY)</option>
                                    <option value="JPY" <?= $settings['currency'] === 'JPY' ? 'selected' : '' ?>>Japanese Yen (JPY)</option>
                                    <option value="CAD" <?= $settings['currency'] === 'CAD' ? 'selected' : '' ?>>Canadian Dollar (CAD)</option>
                                    <option value="CHF" <?= $settings['currency'] === 'CHF' ? 'selected' : '' ?>>Swiss Franc (CHF)</option>
                                    <option value="NGN" <?= $settings['currency'] === 'NGN' ? 'selected' : '' ?>>Nigerian Naira (NGN)</option>
                                    <option value="EGP" <?= $settings['currency'] === 'EGP' ? 'selected' : '' ?>>Egyptian Pound (EGP)</option>
                                    <option value="AED" <?= $settings['currency'] === 'AED' ? 'selected' : '' ?>>UAE Dirham (AED)</option>
                                </select>
                            </div>
                            
                            <!-- Company Logo -->
                            <div class="col-md-6">
                                <label for="logo" class="form-label">Company Logo</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                <?php if ($settings['logo_path']): ?>
                                    <div class="mt-2">
                                        <img src="<?= $BASE_URL . $settings['logo_path'] ?>" alt="Current Logo" style="max-height: 50px;">
                                        <small class="text-muted d-block">Current logo</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Company Info -->
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                    value="<?= htmlspecialchars($settings['company_name'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="tax_id" class="form-label">Tax ID/VAT Number</label>
                                <input type="text" class="form-control" id="tax_id" name="tax_id" 
                                    value="<?= htmlspecialchars($settings['tax_id'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="invoice_prefix" class="form-label">Invoice Prefix</label>
                                <input type="text" class="form-control" id="invoice_prefix" name="invoice_prefix" 
                                    value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV') ?>" required>
                            </div>
                            
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>