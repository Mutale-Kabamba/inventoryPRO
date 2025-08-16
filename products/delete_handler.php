<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = getDbConnection();
    
    $id = $_POST['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Product ID is required']);
        exit;
    }
    
    // Check if product is used in other tables
    $checkTables = ['sales', 'purchases', 'transfers'];
    foreach ($checkTables as $table) {
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM $table WHERE product_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete product. It has associated transactions.']);
            $checkStmt->close();
            $conn->close();
            exit;
        }
        $checkStmt->close();
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
