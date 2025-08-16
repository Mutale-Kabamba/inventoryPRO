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
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $branch = $_POST['branch'] ?? '';
    $unit_price = $_POST['unit_price'] ?? 0;
    $total = $_POST['total'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($id) || empty($product_id) || empty($quantity) || empty($branch) || empty($unit_price)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Calculate total if not provided
    if (empty($total)) {
        $total = $quantity * $unit_price;
    }
    
    $stmt = $conn->prepare("UPDATE sales SET product_id = ?, quantity = ?, branch = ?, unit_price = ?, total = ?, customer_name = ?, notes = ? WHERE id = ?");
    $stmt->bind_param("iisddssi", $product_id, $quantity, $branch, $unit_price, $total, $customer_name, $notes, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Sale updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or sale not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update sale: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
