<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

// Debug: Log the incoming data
error_log("Sales add_handler.php - Incoming POST data: " . print_r($_POST, true));

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
    $total = $_POST['total'] ?? 0;
    $customer_name = $_POST['customer_name'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // If total is provided but not unit_price, calculate unit_price
    if (!empty($total) && empty($unit_price) && !empty($quantity)) {
        $unit_price = $total / $quantity;
    } elseif (empty($total) && !empty($unit_price) && !empty($quantity)) {
        $total = $quantity * $unit_price;
    }
    
    // Debug: Log the extracted values
    error_log("Sales add_handler.php - Extracted values: product_id=$product_id, quantity=$quantity, branch='$branch', unit_price=$unit_price, total=$total, customer_name='$customer_name', notes='$notes'");
    
    if (empty($product_id) || empty($quantity) || empty($branch) || (empty($unit_price) && empty($total))) {
        error_log("Sales add_handler.php - Missing required fields. product_id: $product_id, quantity: $quantity, branch: '$branch', unit_price: $unit_price, total: $total");
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Simplified stock check - just verify product exists and has been purchased
    $stockCheck = $conn->prepare("
        SELECT p.name, 
               COALESCE((SELECT SUM(quantity) FROM purchases WHERE product_id = p.id), 0) as total_purchased,
               COALESCE((SELECT SUM(quantity) FROM sales WHERE product_id = p.id), 0) as total_sold
        FROM products p 
        WHERE p.id = ?
    ");
    $stockCheck->bind_param("i", $product_id);
    $stockCheck->execute();
    $stockResult = $stockCheck->get_result();
    
    error_log("Sales add_handler.php - Stock check query executed for product_id=$product_id");
    
    if ($stockResult->num_rows === 0) {
        error_log("Sales add_handler.php - Product not found: product_id=$product_id");
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $productData = $stockResult->fetch_assoc();
    $totalPurchased = $productData['total_purchased'];
    $totalSold = $productData['total_sold'];
    $currentStock = $totalPurchased - $totalSold;
    $productName = $productData['name'];
    
    error_log("Sales add_handler.php - Product data: name='$productName', purchased=$totalPurchased, sold=$totalSold, current_stock=$currentStock, requested_quantity=$quantity");
    
    // Allow sales if there's any stock available (simplified check)
    if ($currentStock < $quantity) {
        error_log("Sales add_handler.php - Insufficient stock: current_stock=$currentStock, requested_quantity=$quantity");
        echo json_encode([
            'success' => false, 
            'message' => "Insufficient stock for {$productName}. Available: {$currentStock}, Requested: {$quantity}"
        ]);
        exit;
    }
    
    error_log("Sales add_handler.php - About to insert sale: product_id=$product_id, quantity=$quantity, branch='$branch', unit_price=$unit_price, total=$total, customer_name='$customer_name', notes='$notes'");
    
    $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, branch, unit_price, total, customer_name, notes, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisddss", $product_id, $quantity, $branch, $unit_price, $total, $customer_name, $notes);
    
    if ($stmt->execute()) {
        error_log("Sales add_handler.php - Sale inserted successfully. Affected rows: " . $stmt->affected_rows);
        echo json_encode([
            'success' => true, 
            'message' => 'Sale recorded successfully',
            'remaining_stock' => $currentStock - $quantity
        ]);
    } else {
        error_log("Sales add_handler.php - Failed to insert sale: " . $stmt->error);
        echo json_encode(['success' => false, 'message' => 'Failed to record sale: ' . $stmt->error]);
    }
    
    $stockCheck->close();
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
