<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (add your authentication logic)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'You must be logged in to perform this action';
    header('Location: ../login.php');
    exit;
}

// Include database configuration
require_once '../includes/config.php';

// Verify client ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid client ID';
    header('Location: list.php');
    exit;
}

$clientId = (int)$_GET['id'];

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Check if client exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
    $stmt->execute([$clientId]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Client not found');
    }

    // First check if client has any invoices
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $invoiceCount = $stmt->fetchColumn();

    if ($invoiceCount > 0) {
        throw new Exception('Cannot delete client with existing invoices');
    }

    // Delete client
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$clientId]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = 'Client deleted successfully';
    
} catch (Exception $e) {
    // Roll back transaction on error
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting client: ' . $e->getMessage();
    
    // Log the error for debugging
    error_log('Client Delete Error: ' . $e->getMessage());
}

// Redirect back to client list
header('Location: list.php');
exit;
?>