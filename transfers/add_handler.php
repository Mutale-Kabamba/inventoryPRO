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
    $source_branch = $_POST['source_branch'] ?? '';
    $dest_branch = $_POST['dest_branch'] ?? '';
    $quantity = $_POST['quantity'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    
    if (empty($product_id) || empty($source_branch) || empty($dest_branch) || empty($quantity)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if ($source_branch === $dest_branch) {
        echo json_encode(['success' => false, 'message' => 'Source and destination branches cannot be the same']);
        exit;
    }
    
    // Check available stock in source branch
    $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $product_id AND branch = '$source_branch'");
    $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
    
    $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $product_id AND branch = '$source_branch'");
    $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
    
    $transfers_out = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $product_id AND source_branch = '$source_branch'");
    $out = ($row = $transfers_out->fetch_assoc()) ? intval($row['total']) : 0;
    
    $transfers_in = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $product_id AND dest_branch = '$source_branch'");
    $in = ($row = $transfers_in->fetch_assoc()) ? intval($row['total']) : 0;
    
    $available = $purchased - $sold - $out + $in;
    
    if ($available < $quantity) {
        echo json_encode(['success' => false, 'message' => "Insufficient stock. Available: $available, Requested: $quantity"]);
        exit;
    }
    
    // Record the transfer
    $stmt = $conn->prepare("INSERT INTO transfers (product_id, quantity, source_branch, dest_branch, notes, transfer_date) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("iisss", $product_id, $quantity, $source_branch, $dest_branch, $notes);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Transfer of $quantity units from $source_branch to $dest_branch completed successfully"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to record transfer: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
