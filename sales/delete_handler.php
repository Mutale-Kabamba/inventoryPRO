<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method';
    header('Location: list.php');
    exit;
}

try {
    $conn = getDbConnection();
    
    $id = $_POST['id'] ?? 0;
    
    if (empty($id)) {
        $_SESSION['error_message'] = 'Sale ID is required';
        header('Location: list.php');
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = 'Sale deleted successfully';
        } else {
            $_SESSION['error_message'] = 'Sale not found';
        }
    } else {
        $_SESSION['error_message'] = 'Failed to delete sale: ' . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
}

// Redirect back to sales list
header('Location: list.php');
exit;
?>
