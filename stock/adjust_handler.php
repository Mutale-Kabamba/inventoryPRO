<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    // Get POST data
    $productId = $_POST['product_id'] ?? '';
    $branch = $_POST['branch'] ?? '';
    $adjustmentType = $_POST['adjustment_type'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    // Validate input
    if (empty($productId) || empty($branch) || empty($adjustmentType) || $quantity <= 0) {
        throw new Exception('Missing required fields');
    }
    
    // Get current stock level
    $currentStockQuery = $conn->prepare("
        SELECT 
            COALESCE(SUM(p.quantity - p.damages), 0) - 
            COALESCE(SUM(s.quantity), 0) - 
            COALESCE(SUM(t_out.quantity), 0) + 
            COALESCE(SUM(t_in.quantity), 0) as current_stock
        FROM products pr
        LEFT JOIN purchases p ON p.product_id = pr.id AND p.branch = ?
        LEFT JOIN sales s ON s.product_id = pr.id AND s.branch = ?
        LEFT JOIN transfers t_out ON t_out.product_id = pr.id AND t_out.source_branch = ?
        LEFT JOIN transfers t_in ON t_in.product_id = pr.id AND t_in.dest_branch = ?
        WHERE pr.id = ?
    ");
    
    $currentStockQuery->bind_param('ssssi', $branch, $branch, $branch, $branch, $productId);
    $currentStockQuery->execute();
    $result = $currentStockQuery->get_result();
    $currentStock = $result->fetch_assoc()['current_stock'];
    
    // For decrease adjustments, check if we have enough stock
    if ($adjustmentType === 'decrease' && $quantity > $currentStock) {
        throw new Exception("Cannot decrease stock by $quantity. Only $currentStock items available.");
    }
    
    // Determine how to record the adjustment
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    if ($adjustmentType === 'increase') {
        // Record as a purchase with zero cost for stock increases
        $stmt = $conn->prepare("
            INSERT INTO purchases (product_id, branch, quantity, unit_price, supplier, date, time, damages, notes) 
            VALUES (?, ?, ?, 0, 'Stock Adjustment', ?, ?, 0, ?)
        ");
        $adjustmentNotes = "Stock Adjustment: $reason. $notes";
        $stmt->bind_param('isdsss', $productId, $branch, $quantity, $date, $time, $adjustmentNotes);
        
    } else { // decrease
        // Record as a sale with zero price for stock decreases
        $stmt = $conn->prepare("
            INSERT INTO sales (product_id, branch, quantity, unit_price, customer, date, time, notes) 
            VALUES (?, ?, ?, 0, 'Stock Adjustment', ?, ?, ?)
        ");
        $adjustmentNotes = "Stock Adjustment: $reason. $notes";
        $stmt->bind_param('isdsss', $productId, $branch, $quantity, $date, $time, $adjustmentNotes);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to record stock adjustment: ' . $stmt->error);
    }
    
    // Calculate new stock level
    $newStock = $adjustmentType === 'increase' ? $currentStock + $quantity : $currentStock - $quantity;
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock adjustment applied successfully',
        'data' => [
            'previous_stock' => $currentStock,
            'adjustment' => ($adjustmentType === 'increase' ? '+' : '-') . $quantity,
            'new_stock' => $newStock,
            'reason' => $reason
        ]
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->close();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
