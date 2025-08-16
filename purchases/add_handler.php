<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Invalid request method';
    exit;
}

try {
    $conn = getDbConnection();
    
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 0;
    $unit_price = $_POST['unit_price'] ?? 0;
    $supplier = $_POST['supplier'] ?? '';
    $branch = $_POST['branch'] ?? '';
    $damages = $_POST['damages'] ?? 0;
    
    if (empty($product_id) || empty($quantity) || empty($unit_price) || empty($supplier) || empty($branch)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
        exit;
        exit;
    }
    
    $total_cost = $quantity * $unit_price;
    
    $stmt = $conn->prepare("INSERT INTO purchases (product_id, quantity, unit_price, total_cost, supplier, branch, damages, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiddssi", $product_id, $quantity, $unit_price, $total_cost, $supplier, $branch, $damages);
    
    if ($stmt->execute()) {
            echo json_encode(['success' => true]);
    } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
