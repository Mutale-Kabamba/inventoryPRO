<?php
header('Content-Type: application/json');
require_once 'db.php';

try {
    $conn = getDbConnection();
    $result = $conn->query('SELECT id, name, price FROM products ORDER BY name ASC');
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'price' => $row['price']
        ];
    }
    
    echo json_encode($products);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load products']);
}
?>
