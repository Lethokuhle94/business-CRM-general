<?php
require_once '../includes/config.php';
session_start();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid CSRF token";
    header('Location: transactions.php');
    exit;
}

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "You must be logged in";
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['delete_id'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Get the transaction data
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['delete_id'], $_SESSION['user_id']]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found or access denied");
        }
        
        // 2. Move to recycle bin
        $insert = $pdo->prepare("INSERT INTO transaction_recycle_bin (transaction_data, deleted_by) VALUES (?, ?)");
        $insert->execute([json_encode($transaction), $_SESSION['user_id']]);
        
        // 3. Delete from transactions
        $delete = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $delete->execute([$_POST['delete_id'], $_SESSION['user_id']]);
        
        // 4. Adjust account balance
        if ($transaction['type'] === 'income') {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
        } else {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
        }
        $update->execute([$transaction['amount'], $transaction['account_id'], $_SESSION['user_id']]);
        
        $pdo->commit();
        $_SESSION['success_message'] = "Transaction moved to recycle bin";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Delete Transaction Error: " . $e->getMessage());
        $_SESSION['error_message'] = "Error deleting transaction";
    }
}

header('Location: transactions.php');
exit;
?>