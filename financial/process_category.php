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
    $stmt = $pdo->prepare("
        INSERT INTO transaction_categories 
        (user_id, name, type, color) 
        VALUES (?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['name'],
        $_POST['type'],
        $_POST['color'] ?? '#6c757d'
    ]);
    
    $_SESSION['success_message'] = 'Category created successfully';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Error creating category: ' . $e->getMessage();
}

header('Location: /financial/list.php');
exit;
?>