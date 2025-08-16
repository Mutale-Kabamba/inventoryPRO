<?php
require_once 'config/db.php';
$conn = getDbConnection();

echo '<h3>Checking Stock Data:</h3>';

// Check purchases
$result = $conn->query('SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM purchases');
$purchases = $result->fetch_assoc();
echo "Total Purchases: " . $purchases['count'] . " records, " . $purchases['total_qty'] . " total quantity<br>";

// Check transfers
$result = $conn->query('SELECT COUNT(*) as count FROM transfers');
$transfers = $result->fetch_assoc();
echo "Total Transfers: " . $transfers['count'] . " records<br>";

// Check sales
$result = $conn->query('SELECT COUNT(*) as count, SUM(quantity) as total_qty FROM sales');
$sales = $result->fetch_assoc();
echo "Total Sales: " . $sales['count'] . " records, " . $sales['total_qty'] . " total quantity<br>";

// Check specific stock for product 1 in Livingstone
echo '<h3>Stock calculation for Product 1 in Livingstone:</h3>';
$stockCheck = $conn->prepare("
    SELECT p.name,
           (SELECT COALESCE(SUM(quantity), 0) FROM purchases WHERE product_id = p.id) as total_purchases,
           (SELECT COALESCE(SUM(quantity), 0) FROM transfers WHERE product_id = p.id AND dest_branch = 'Livingstone') as transfers_in,
           (SELECT COALESCE(SUM(quantity), 0) FROM sales WHERE product_id = p.id AND branch = 'Livingstone') as sales_out,
           (SELECT COALESCE(SUM(quantity), 0) FROM transfers WHERE product_id = p.id AND source_branch = 'Livingstone') as transfers_out,
           COALESCE(
               (SELECT SUM(quantity) FROM purchases WHERE product_id = p.id) + 
               (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND dest_branch = 'Livingstone') -
               (SELECT SUM(quantity) FROM sales WHERE product_id = p.id AND branch = 'Livingstone') -
               (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND source_branch = 'Livingstone'), 
               0
           ) as calculated_stock
    FROM products p 
    WHERE p.id = 1
");
$stockCheck->execute();
$stockResult = $stockCheck->get_result();
if ($stockData = $stockResult->fetch_assoc()) {
    echo 'Product: ' . $stockData['name'] . '<br>';
    echo 'Total Purchases: ' . $stockData['total_purchases'] . '<br>';
    echo 'Transfers In: ' . $stockData['transfers_in'] . '<br>';
    echo 'Sales Out: ' . $stockData['sales_out'] . '<br>';
    echo 'Transfers Out: ' . $stockData['transfers_out'] . '<br>';
    echo '<strong>Calculated Stock: ' . $stockData['calculated_stock'] . '</strong><br>';
} else {
    echo 'Product ID 1 not found<br>';
}

$conn->close();
?>
