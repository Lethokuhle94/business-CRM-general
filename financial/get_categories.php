<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_GET['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, name, type 
        FROM transaction_categories 
        WHERE user_id = ? OR is_default = TRUE 
        ORDER BY type, name
    ");
    $stmt->execute([$_GET['user_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo json_encode([]);
}
?>