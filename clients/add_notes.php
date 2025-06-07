<?php
require_once '../includes/config.php';

if (!isset($_POST['id']) || !isset($_POST['note'])) {
    header('Location: list.php');
    exit;
}

$id = (int)$_POST['id'];
$note = sanitizeInput($_POST['note']);

try {
    $stmt = $pdo->prepare("UPDATE customers SET notes = CONCAT(IFNULL(notes, ''), ?) WHERE id = ?");
    $newNote = "\n\n" . date('Y-m-d H:i:s') . ":\n" . $note;
    $stmt->execute([$newNote, $id]);
    $_SESSION['success_message'] = 'Note added successfully';
} catch (PDOException $e) {
    $_SESSION['error_message'] = 'Error adding note: ' . $e->getMessage();
}

header("Location: view.php?id=$id");
exit;
?>