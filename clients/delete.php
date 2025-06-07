<?php
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success_message'] = 'Client deleted successfully';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Error deleting client: ' . $e->getMessage();
}

header('Location: list.php');
exit;
?>