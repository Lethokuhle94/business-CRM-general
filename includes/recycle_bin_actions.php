<?php
// Handle recycle bin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $pdo->beginTransaction();
        
        // Verify password for any action
        if ($_POST['password'] !== RECYCLE_BIN_PASSWORD) {
            throw new Exception("Incorrect recycle bin password");
        }
        
        $itemId = (int)$_POST['item_id'];
        $itemType = $_POST['item_type'];
        $action = $_POST['action'];
        
        switch ($action) {
            case 'restore':
                if ($itemType === 'transaction') {
                    // Restore transaction logic
                    require_once 'restore_transaction.php';
                } elseif ($itemType === 'quotation') {
                    // Restore quotation logic
                    require_once 'restore_quotation.php';
                }
                $_SESSION['success_message'] = "Item restored successfully";
                break;
                
            case 'delete':
                if ($itemType === 'transaction') {
                    $stmt = $pdo->prepare("DELETE FROM transaction_recycle_bin WHERE id = ? AND deleted_by = ?");
                } elseif ($itemType === 'quotation') {
                    $stmt = $pdo->prepare("DELETE FROM quotation_recycle_bin WHERE id = ? AND deleted_by = ?");
                }
                $stmt->execute([$itemId, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Item not found or already deleted");
                }
                
                $_SESSION['success_message'] = "Item permanently deleted";
                break;
                
            default:
                throw new Exception("Invalid action");
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Recycle Bin Action Error: " . $e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
    }
    
    header('Location: recycle_bin.php');
    exit;
}
?>