<?php
require_once 'config/db.php';
$conn = getDbConnection();

// Add some sample purchases to create stock
$stmt = $conn->prepare("INSERT INTO purchases (product_id, quantity, unit_price, total, purchase_date) VALUES (?, ?, ?, ?, NOW())");

// Add stock for product 1
$product_id = 1;
$quantity = 1000;
$unit_price = 10.00;
$total = $quantity * $unit_price;
$stmt->bind_param("iidd", $product_id, $quantity, $unit_price, $total);

if ($stmt->execute()) {
    echo "✅ Added purchase record: Product ID $product_id, Quantity $quantity, Unit Price K$unit_price, Total K$total<br>";
} else {
    echo "❌ Failed to add purchase: " . $stmt->error . "<br>";
}

$stmt->close();
$conn->close();

echo '<br><a href="check_stock_debug.php">Check Stock Again</a>';
echo '<br><a href="public/dashboard.php">Back to Dashboard</a>';
?>
