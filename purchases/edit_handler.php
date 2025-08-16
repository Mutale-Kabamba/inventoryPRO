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
    $unit_price = $_POST['unit_price'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';
    $branch = $_POST['branch'] ?? '';
    $damages = $_POST['damages'] ?? 0;
    
    if (empty($id) || empty($product_id) || empty($quantity) || empty($unit_price) || empty($supplier) || empty($branch)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    $total_cost = $quantity * $unit_price;
    
    $stmt = $conn->prepare("UPDATE purchases SET product_id = ?, quantity = ?, unit_price = ?, total_cost = ?, supplier = ?, branch = ?, damages = ? WHERE id = ?");
    $stmt->bind_param("iiddssis", $product_id, $quantity, $unit_price, $total_cost, $supplier, $branch, $damages, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Purchase updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made or purchase not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update purchase: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
