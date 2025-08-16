<?php
require_once 'config/db.php';
$conn = getDbConnection();
$result = $conn->query('SELECT id, name, price FROM products LIMIT 5');
echo '<h3>Products:</h3>';
while ($row = $result->fetch_assoc()) {
    echo 'ID: ' . $row['id'] . ', Name: ' . $row['name'] . ', Price: ' . $row['price'] . '<br>';
}

// Check stock for a product
echo '<h3>Stock Check for Product ID 1 in Livingstone:</h3>';
$stockCheck = $conn->prepare("
    SELECT p.name, 
           COALESCE(
               (SELECT SUM(quantity) FROM purchases WHERE product_id = p.id) + 
               (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND dest_branch = ?) -
               (SELECT SUM(quantity) FROM sales WHERE product_id = p.id AND branch = ?) -
               (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND source_branch = ?), 
               0
           ) as current_stock
    FROM products p 
    WHERE p.id = ?
");
$branch = 'Livingstone';
$product_id = 1;
$stockCheck->bind_param("sssi", $branch, $branch, $branch, $product_id);
$stockCheck->execute();
$stockResult = $stockCheck->get_result();
if ($stockData = $stockResult->fetch_assoc()) {
    echo 'Product: ' . $stockData['name'] . ', Stock: ' . $stockData['current_stock'] . '<br>';
} else {
    echo 'Product ID 1 not found<br>';
}

$conn->close();
?>
