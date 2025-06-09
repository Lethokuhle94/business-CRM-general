<?php
require_once '../includes/config.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid invoice ID';
    header('Location: list.php');
    exit;
}

$invoiceId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // Check if invoice exists
    $stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);
    if (!$stmt->fetch()) {
        throw new Exception('Invoice not found');
    }

    // First delete all related invoice items
    $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
    $stmt->execute([$invoiceId]);

    // Then delete the invoice
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
    $stmt->execute([$invoiceId]);

    $pdo->commit();

    $_SESSION['success'] = 'Invoice deleted successfully';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting invoice: ' . $e->getMessage();
}

header('Location: list.php');
exit;
?>