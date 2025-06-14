<?php
require_once '../includes/config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid quotation ID';
    header('Location: list.php');
    exit;
}

$quotationId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();
    
    // Fetch quotation data
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
    $stmt->execute([$quotationId]);
    $quotation = $stmt->fetch();
    
    if (!$quotation) {
        throw new Exception('Quotation not found');
    }
    
    // Create invoice
    $stmt = $pdo->prepare("INSERT INTO invoices 
        (invoice_number, client_id, date, due_date, status, tax_rate, discount, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
    $stmt->execute([
        $invoiceNumber,
        $quotation['client_id'],
        date('Y-m-d'),
        date('Y-m-d', strtotime('+30 days')), // Default 30-day payment terms
        'draft',
        $quotation['tax_rate'],
        $quotation['discount'],
        "Converted from Quotation #" . $quotation['quotation_number'] . "\n" . $quotation['notes']
    ]);
    
    $invoiceId = $pdo->lastInsertId();
    
    // Copy items from quotation to invoice
    $items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $items->execute([$quotationId]);
    
    foreach ($items->fetchAll() as $item) {
        $stmt = $pdo->prepare("INSERT INTO invoice_items 
            (invoice_id, description, quantity, unit_price, tax_rate) 
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $invoiceId,
            $item['description'],
            $item['quantity'],
            $item['unit_price'],
            $item['tax_rate']
        ]);
    }
    
    // Update quotation status
    $stmt = $pdo->prepare("UPDATE quotations SET status = 'converted' WHERE id = ?");
    $stmt->execute([$quotationId]);
    
    $pdo->commit();
    
    $_SESSION['success'] = 'Quotation converted to invoice successfully!';
    header("Location: ../invoices/view.php?id=$invoiceId");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error converting quotation: ' . $e->getMessage();
    header("Location: view.php?id=$quotationId");
    exit;
}