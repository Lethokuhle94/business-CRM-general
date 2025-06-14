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

    // Check if quotation exists
    $stmt = $pdo->prepare("SELECT id FROM quotations WHERE id = ?");
    $stmt->execute([$quotationId]);
    if (!$stmt->fetch()) {
        throw new Exception('Quotation not found');
    }

    // First delete all related quotation items
    $stmt = $pdo->prepare("DELETE FROM quotation_items WHERE quotation_id = ?");
    $stmt->execute([$quotationId]);

    // Then delete the quotation
    $stmt = $pdo->prepare("DELETE FROM quotations WHERE id = ?");
    $stmt->execute([$quotationId]);

    $pdo->commit();

    $_SESSION['success'] = 'Quotation deleted successfully';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting quotation: ' . $e->getMessage();
}

header('Location: list.php');
exit;
?>