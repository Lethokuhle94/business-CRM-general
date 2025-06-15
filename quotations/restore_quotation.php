<?php
require_once '../includes/config.php';

// Authentication and password verification
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

if ($_POST['password'] !== RECYCLE_BIN_PASSWORD) {
    $_SESSION['error_message'] = "Incorrect recycle bin password";
    header('Location: recycle_bin_quotations.php');
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 1. Get quotation from bin
    $stmt = $pdo->prepare("SELECT * FROM quotation_recycle_bin WHERE id = ? AND deleted_by = ?");
    $stmt->execute([$_POST['restore_id'], $_SESSION['user_id']]);
    $binItem = $stmt->fetch();
    
    if (!$binItem) {
        throw new Exception("Quotation not found in recycle bin");
    }
    
    $quotation = json_decode($binItem['quotation_data'], true);
    $items = json_decode($binItem['items_data'], true);
    
    // 2. Restore quotation
    $insertQuotation = $pdo->prepare("
        INSERT INTO quotations 
        (quotation_number, client_id, date, expiry_date, status, tax_rate, discount, notes, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertQuotation->execute([
        $quotation['quotation_number'],
        $quotation['client_id'],
        $quotation['date'],
        $quotation['expiry_date'],
        $quotation['status'],
        $quotation['tax_rate'],
        $quotation['discount'],
        $quotation['notes'],
        $quotation['created_at'],
        $quotation['updated_at']
    ]);
    
    $newQuotationId = $pdo->lastInsertId();
    
    // 3. Restore items
    foreach ($items as $item) {
        $insertItem = $pdo->prepare("
            INSERT INTO quotation_items 
            (quotation_id, description, quantity, unit_price, tax_rate)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertItem->execute([
            $newQuotationId,
            $item['description'],
            $item['quantity'],
            $item['unit_price'],
            $item['tax_rate']
        ]);
    }
    
    // 4. Remove from bin
    $delete = $pdo->prepare("DELETE FROM quotation_recycle_bin WHERE id = ?");
    $delete->execute([$_POST['restore_id']]);
    
    $pdo->commit();
    $_SESSION['success_message'] = "Quotation restored successfully";
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Restore Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Error restoring quotation";
}

header('Location: recycle_bin_quotations.php');
exit;
?>