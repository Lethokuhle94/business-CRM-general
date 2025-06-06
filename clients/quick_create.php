<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("INSERT INTO customers 
        (name, email, phone, company, status) 
        VALUES (?, ?, ?, ?, 'active')");
    
    $stmt->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['company']
    ]);
    
    $clientId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT name, company FROM customers WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'id' => $clientId,
        'name' => $client['name'],
        'company' => $client['company']
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error creating client: ' . $e->getMessage()
    ]);
}