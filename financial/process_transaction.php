<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /financial/list.php');
    exit;
}

// Validate user
if (!isset($_SESSION['user_id']) {
    header('Location: /login.php');
    exit;
}

// Process transaction
try {
    $pdo->beginTransaction();
    
    // Insert transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            user_id, account_id, transaction_date, amount, type, 
            category_id, description, reference, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['account_id'],
        $_POST['transaction_date'],
        $_POST['amount'],
        $_POST['type'],
        $_POST['category_id'] ?: null,
        $_POST['description'] ?: null,
        $_POST['reference'] ?: null
    ]);
    
    // Update account balance
    if ($_POST['type'] === 'income') {
        $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance + ? WHERE id = ? AND user_id = ?");
    } elseif ($_POST['type'] === 'expense') {
        $update = $pdo->prepare("UPDATE financial_accounts SET current_balance = current_balance - ? WHERE id = ? AND user_id = ?");
    }
    
    if (isset($update)) {
        $update->execute([$_POST['amount'], $_POST['account_id'], $_SESSION['user_id']]);
    }
    
    $pdo->commit();
    
    $_SESSION['success_message'] = 'Transaction added successfully';
    header('Location: /financial/list.php');
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = 'Error adding transaction: ' . $e->getMessage();
    header('Location: /financial/list.php');
    exit;
}
?>