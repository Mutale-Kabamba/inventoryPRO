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
    
    $product_id = $_POST['product_id'] ?? 0;
    $branch = $_POST['branch'] ?? '';
    $adjustment_type = $_POST['adjustment_type'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($product_id) || empty($branch) || empty($adjustment_type) || empty($quantity) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    // Get product details
    $productStmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
    $productStmt->bind_param("i", $product_id);
    $productStmt->execute();
    $productResult = $productStmt->get_result();
    
    if ($productResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $productResult->fetch_assoc();
    $unit_price = $product['price'];
    
    if ($adjustment_type === 'increase') {
        // Add as purchase with adjustment reason
        $total_cost = $quantity * $unit_price;
        $stmt = $conn->prepare("INSERT INTO purchases (product_id, quantity, unit_price, total_cost, supplier, branch, damages, purchase_date) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
        $supplier = "Stock Adjustment - " . ucfirst($reason) . ($notes ? " - Notes: $notes" : "");
        $stmt->bind_param("iiddss", $product_id, $quantity, $unit_price, $total_cost, $supplier, $branch);
    } else {
        // Add as sale with adjustment reason
        $total = $quantity * $unit_price;
        $stmt = $conn->prepare("INSERT INTO sales (product_id, quantity, branch, unit_price, total, customer_name, notes, sale_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $customer = "Stock Adjustment - " . ucfirst($reason);
        $adjustmentNotes = "Stock adjustment (-$quantity) - Reason: $reason" . ($notes ? " - Notes: $notes" : "");
        $stmt->bind_param("iisdss", $product_id, $quantity, $branch, $unit_price, $total, $customer, $adjustmentNotes);
    }
    
    if ($stmt->execute()) {
        $action = ($adjustment_type === 'increase') ? 'increased' : 'decreased';
        echo json_encode(['success' => true, 'message' => "Stock $action successfully by $quantity units"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to adjust stock: ' . $stmt->error]);
    }
    
    $stmt->close();
    $productStmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
