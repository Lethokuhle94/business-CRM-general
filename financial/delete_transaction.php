<?php
require_once '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Check if transaction ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Invalid transaction ID.';
    header('Location: transactions.php');
    exit;
}

$transaction_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Start transaction for data integrity
    $pdo->beginTransaction();
    
    // First, verify that the transaction belongs to the current user
    $verify_query = "SELECT id, description, amount FROM transactions WHERE id = ? AND user_id = ?";
    $verify_stmt = $pdo->prepare($verify_query);
    $verify_stmt->execute([$transaction_id, $user_id]);
    $transaction = $verify_stmt->fetch();
    
    if (!$transaction) {
        throw new Exception('Transaction not found or you do not have permission to delete it.');
    }
    
    // Delete the transaction
    $delete_query = "DELETE FROM transactions WHERE id = ? AND user_id = ?";
    $delete_stmt = $pdo->prepare($delete_query);
    $delete_result = $delete_stmt->execute([$transaction_id, $user_id]);
    
    if (!$delete_result) {
        throw new Exception('Failed to delete transaction.');
    }
    
    // Check if any rows were affected
    if ($delete_stmt->rowCount() === 0) {
        throw new Exception('No transaction was deleted. Please try again.');
    }
    
    // Commit the transaction
    $pdo->commit();
    
    // Set success message
    $_SESSION['success'] = 'Transaction "' . htmlspecialchars($transaction['description']) . '" has been deleted successfully.';
    
} catch (Exception $e) {
    // Rollback the transaction on error
    $pdo->rollBack();
    
    // Set error message
    $_SESSION['error'] = 'Error deleting transaction: ' . $e->getMessage();
}

// Redirect back to transactions page
header('Location: transactions.php');
exit;
?>