<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

// Debug: Log the incoming data
error_log("Sales add_handler.php - Simple version - Incoming POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Sales add_handler.php - Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = getDbConnection();
    
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $branch = $_POST['branch'] ?? '';
    $unit_price = $_POST['unit_price'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Debug: Log the extracted values
    error_log("Sales add_handler.php - Simple version - Extracted values: product_id=$product_id, quantity=$quantity, branch='$branch', unit_price=$unit_price");
    
    if (empty($product_id) || empty($quantity) || empty($branch) || empty($unit_price)) {
        error_log("Sales add_handler.php - Simple version - Missing required fields");
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Skip stock check for now - just verify product exists
    $productCheck = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $productCheck->bind_param("i", $product_id);
    $productCheck->execute();
    $result = $productCheck->get_result();
    
    if ($result->num_rows === 0) {
        error_log("Sales add_handler.php - Simple version - Product not found: product_id=$product_id");
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $productData = $result->fetch_assoc();
    $productName = $productData['name'];
    error_log("Sales add_handler.php - Simple version - Product found: $productName");
    
    $total = $quantity * $unit_price;
    
    error_log("Sales add_handler.php - Simple version - About to insert sale");
    
    $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, branch, unit_price, total, customer_name, notes, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisddss", $product_id, $quantity, $branch, $unit_price, $total, $customer_name, $notes);
    
    if ($stmt->execute()) {
        error_log("Sales add_handler.php - Simple version - Sale inserted successfully. Affected rows: " . $stmt->affected_rows);
        echo json_encode([
            'success' => true, 
            'message' => 'Sale recorded successfully',
            'product_name' => $productName
        ]);
    } else {
        error_log("Sales add_handler.php - Simple version - Failed to insert sale: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to record sale: ' . $stmt->error]);
    }
    
    $productCheck->close();
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    error_log("Sales add_handler.php - Simple version - Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
