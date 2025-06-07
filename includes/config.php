<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'invoice_app');

// Start session
session_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper functions
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Create database connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function getSettings($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM settings WHERE user_id = ?");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        return [
            'currency' => 'USD',
            'logo_path' => null,
            'company_name' => null,
            'tax_id' => null,
            'invoice_prefix' => 'INV'
        ];
    }
    
    return $settings;
}

// Add this to your existing config.php
$settings = getSettings($pdo, $_SESSION['user_id'] ?? 0);

?>