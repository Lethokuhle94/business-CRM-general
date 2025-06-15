<?php
require_once '../includes/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid quotation ID';
    header('Location: list.php');
    exit;
}

$quotationId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. Get the quotation and its items
    $stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
    $stmt->execute([$quotationId]);
    $quotation = $stmt->fetch();

    if (!$quotation) {
        throw new Exception('Quotation not found');
    }

    // Get quotation items
    $items = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id = ?");
    $items->execute([$quotationId]);
    $quotationItems = $items->fetchAll();

    // 2. Store in recycle bin
    $insert = $pdo->prepare("
        INSERT INTO quotation_recycle_bin 
        (quotation_data, items_data, deleted_by)
        VALUES (?, ?, ?)
    ");
    $insert->execute([
        json_encode($quotation),
        json_encode($quotationItems),
        $_SESSION['user_id']
    ]);

    // 3. Delete items
    $deleteItems = $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
    $deleteItems->execute([$quotationId]);

    // 4. Delete quotation
    $deleteQuotation = $pdo->prepare("DELETE FROM quotations WHERE id = ?");
    $deleteQuotation->execute([$quotationId]);

    $pdo->commit();
    $_SESSION['success'] = 'Quotation moved to recycle bin';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting quotation: ' . $e->getMessage();
}

header('Location: list.php');
exit;
?>