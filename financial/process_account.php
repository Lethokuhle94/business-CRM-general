<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /financial/list.php');
    exit;
}

// Validate user
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Process account creation
try {
    $stmt = $pdo->prepare("
        INSERT INTO financial_accounts (
            user_id, account_name, account_type, account_number, 
            bank_name, opening_balance, current_balance, 
            currency, description, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['account_name'],
        $_POST['account_type'],
        $_POST['account_number'] ?: null,
        $_POST['bank_name'] ?: null,
        $_POST['opening_balance'],
        $_POST['opening_balance'], // current_balance starts same as opening
        'ZAR', // Default to South African Rand
        $_POST['description'] ?: null
    ]);
    
    $_SESSION['success_message'] = 'Account added successfully';
    header('Location: /financial/list.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error adding account: ' . $e->getMessage();
    header('Location: /financial/list.php');
    exit;
}
?>