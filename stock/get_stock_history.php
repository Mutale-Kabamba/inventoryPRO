<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $conn = getDbConnection();
    
    $productId = $_GET['product_id'] ?? '';
    $branch = $_GET['branch'] ?? '';
    
    if (empty($productId) || empty($branch)) {
        throw new Exception('Product ID and branch are required');
    }
    
    // Get product info
    $productQuery = $conn->prepare("SELECT name, category FROM products WHERE id = ?");
    $productQuery->bind_param('i', $productId);
    $productQuery->execute();
    $productResult = $productQuery->get_result();
    $product = $productResult->fetch_assoc();
    
    if (!$product) {
        throw new Exception('Product not found');
    }
    
    // Get all stock movements
    $movements = [];
    
    // 1. Purchases (stock increases)
    $purchasesQuery = $conn->prepare("
        SELECT 
            'purchase' as type,
            date,
            time,
            quantity,
            damages,
            (quantity - damages) as net_quantity,
            unit_price,
            supplier as reference,
            notes,
            id
        FROM purchases 
        WHERE product_id = ? AND branch = ? 
        ORDER BY date DESC, time DESC
    ");
    $purchasesQuery->bind_param('is', $productId, $branch);
    $purchasesQuery->execute();
    $purchasesResult = $purchasesQuery->get_result();
    
    while ($row = $purchasesResult->fetch_assoc()) {
        $movements[] = [
            'type' => 'purchase',
            'type_label' => 'Purchase',
            'date' => $row['date'],
            'time' => $row['time'],
            'quantity' => intval($row['net_quantity']),
            'change' => '+' . $row['net_quantity'],
            'unit_price' => floatval($row['unit_price']),
            'reference' => $row['reference'],
            'notes' => $row['notes'],
            'damages' => intval($row['damages']),
            'id' => $row['id'],
            'icon' => '📦',
            'color' => 'success'
        ];
    }
    
    // 2. Sales (stock decreases)
    $salesQuery = $conn->prepare("
        SELECT 
            'sale' as type,
            date,
            time,
            quantity,
            unit_price,
            customer as reference,
            notes,
            id
        FROM sales 
        WHERE product_id = ? AND branch = ? 
        ORDER BY date DESC, time DESC
    ");
    $salesQuery->bind_param('is', $productId, $branch);
    $salesQuery->execute();
    $salesResult = $salesQuery->get_result();
    
    while ($row = $salesResult->fetch_assoc()) {
        $movements[] = [
            'type' => 'sale',
            'type_label' => 'Sale',
            'date' => $row['date'],
            'time' => $row['time'],
            'quantity' => intval($row['quantity']),
            'change' => '-' . $row['quantity'],
            'unit_price' => floatval($row['unit_price']),
            'reference' => $row['reference'],
            'notes' => $row['notes'],
            'id' => $row['id'],
            'icon' => '💰',
            'color' => 'warning'
        ];
    }
    
    // 3. Transfers Out (stock decreases)
    $transfersOutQuery = $conn->prepare("
        SELECT 
            'transfer_out' as type,
            date,
            time,
            quantity,
            dest_branch,
            notes,
            id
        FROM transfers 
        WHERE product_id = ? AND source_branch = ? 
        ORDER BY date DESC, time DESC
    ");
    $transfersOutQuery->bind_param('is', $productId, $branch);
    $transfersOutQuery->execute();
    $transfersOutResult = $transfersOutQuery->get_result();
    
    while ($row = $transfersOutResult->fetch_assoc()) {
        $movements[] = [
            'type' => 'transfer_out',
            'type_label' => 'Transfer Out',
            'date' => $row['date'],
            'time' => $row['time'],
            'quantity' => intval($row['quantity']),
            'change' => '-' . $row['quantity'],
            'unit_price' => 0,
            'reference' => 'To ' . $row['dest_branch'],
            'notes' => $row['notes'],
            'id' => $row['id'],
            'icon' => '📤',
            'color' => 'info'
        ];
    }
    
    // 4. Transfers In (stock increases)
    $transfersInQuery = $conn->prepare("
        SELECT 
            'transfer_in' as type,
            date,
            time,
            quantity,
            source_branch,
            notes,
            id
        FROM transfers 
        WHERE product_id = ? AND dest_branch = ? 
        ORDER BY date DESC, time DESC
    ");
    $transfersInQuery->bind_param('is', $productId, $branch);
    $transfersInQuery->execute();
    $transfersInResult = $transfersInQuery->get_result();
    
    while ($row = $transfersInResult->fetch_assoc()) {
        $movements[] = [
            'type' => 'transfer_in',
            'type_label' => 'Transfer In',
            'date' => $row['date'],
            'time' => $row['time'],
            'quantity' => intval($row['quantity']),
            'change' => '+' . $row['quantity'],
            'unit_price' => 0,
            'reference' => 'From ' . $row['source_branch'],
            'notes' => $row['notes'],
            'id' => $row['id'],
            'icon' => '📥',
            'color' => 'success'
        ];
    }
    
    // Sort all movements by date and time (most recent first)
    usort($movements, function($a, $b) {
        $dateCompare = strcmp($b['date'], $a['date']);
        if ($dateCompare === 0) {
            return strcmp($b['time'], $a['time']);
        }
        return $dateCompare;
    });
    
    // Calculate running stock balance
    $currentStock = 0;
    
    // First, calculate the final stock level
    foreach ($movements as $movement) {
        if (in_array($movement['type'], ['purchase', 'transfer_in'])) {
            $currentStock += $movement['quantity'];
        } else {
            $currentStock -= $movement['quantity'];
        }
    }
    
    // Now calculate running balance from most recent backwards
    $runningStock = $currentStock;
    for ($i = 0; $i < count($movements); $i++) {
        $movements[$i]['stock_after'] = $runningStock;
        
        // Calculate stock before this transaction
        if (in_array($movements[$i]['type'], ['purchase', 'transfer_in'])) {
            $runningStock -= $movements[$i]['quantity'];
        } else {
            $runningStock += $movements[$i]['quantity'];
        }
        $movements[$i]['stock_before'] = $runningStock;
    }
    
    // Calculate summary statistics
    $totalPurchases = 0;
    $totalSales = 0;
    $totalTransfersIn = 0;
    $totalTransfersOut = 0;
    $totalValue = 0;
    
    foreach ($movements as $movement) {
        switch ($movement['type']) {
            case 'purchase':
                $totalPurchases += $movement['quantity'];
                $totalValue += $movement['quantity'] * $movement['unit_price'];
                break;
            case 'sale':
                $totalSales += $movement['quantity'];
                break;
            case 'transfer_in':
                $totalTransfersIn += $movement['quantity'];
                break;
            case 'transfer_out':
                $totalTransfersOut += $movement['quantity'];
                break;
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'branch' => $branch,
        'current_stock' => $currentStock,
        'movements' => $movements,
        'summary' => [
            'total_movements' => count($movements),
            'total_purchases' => $totalPurchases,
            'total_sales' => $totalSales,
            'total_transfers_in' => $totalTransfersIn,
            'total_transfers_out' => $totalTransfersOut,
            'total_value' => $totalValue,
            'net_movement' => $totalPurchases + $totalTransfersIn - $totalSales - $totalTransfersOut
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
