<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /financial/list.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    if (isset($_POST['edit_id'])) {
        // EDIT EXISTING TRANSACTION
        
        // First get original transaction details
        $stmt = $pdo->prepare("SELECT account_id, amount, type FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->execute([$_POST['edit_id'], $_SESSION['user_id']]);
        $original = $stmt->fetch();
        
        if (!$original) {
            throw new Exception("Transaction not found");
        }
        
        // Reverse original transaction's effect on account balance
        if ($original['type'] === 'income') {
            $reverse = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
        } else {
            $reverse = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
        }
        $reverse->execute([$original['amount'], $original['account_id'], $_SESSION['user_id']]);
        
        // Update transaction
        $stmt = $pdo->prepare("
            UPDATE transactions SET
                account_id = ?,
                transaction_date = ?,
                amount = ?,
                type = ?,
                category_id = ?,
                description = ?,
                updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $_POST['account_id'],
            $_POST['transaction_date'],
            $_POST['amount'],
            $_POST['type'],
            $_POST['category_id'] ?: null,
            $_POST['description'] ?: null,
            $_POST['edit_id'],
            $_SESSION['user_id']
        ]);
        
        // Apply new transaction's effect on account balance
        if ($_POST['type'] === 'income') {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
        } else {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
        }
        $update->execute([$_POST['amount'], $_POST['account_id'], $_SESSION['user_id']]);
        
        $_SESSION['success_message'] = 'Transaction updated successfully';
        
    } else {
        // NEW TRANSACTION (existing code)
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                user_id, account_id, transaction_date, amount, type, 
                category_id, description, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['account_id'],
            $_POST['transaction_date'],
            $_POST['amount'],
            $_POST['type'],
            $_POST['category_id'] ?: null,
            $_POST['description'] ?: null
        ]);
        
        if ($_POST['type'] === 'income') {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
        } elseif ($_POST['type'] === 'expense') {
            $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
        }
        
        if (isset($update)) {
            $update->execute([$_POST['amount'], $_POST['account_id'], $_SESSION['user_id']]);
        }
        
        $_SESSION['success_message'] = 'Transaction added successfully';
    }
    
    $pdo->commit();
    header('Location: ' . (isset($_POST['edit_id']) ? 'transactions.php' : 'list.php'));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Error processing transaction: ' . $e->getMessage();
    header('Location: ' . (isset($_POST['edit_id']) ? 'transactions.php' : 'list.php'));
    exit;
}
?> 