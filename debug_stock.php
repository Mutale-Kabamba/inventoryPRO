<?php
require_once 'config/db.php';

$conn = getDbConnection();

echo "<h2>Database Tables</h2>";
$result = $conn->query('SHOW TABLES');
echo "<ul>";
while ($row = $result->fetch_array()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

echo "<h2>Products Data</h2>";
$result = $conn->query('SELECT id, name, price FROM products LIMIT 10');
echo "<table border='1'><tr><th>ID</th><th>Name</th><th>Price</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['price'] . "</td></tr>";
}
echo "</table>";

echo "<h2>Purchases Data</h2>";
$result = $conn->query('SELECT * FROM purchases LIMIT 10');
if ($result->num_rows > 0) {
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<table border='1'><tr>";
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No purchases found</p>";
}

echo "<h2>Sales Data</h2>";
$result = $conn->query('SELECT * FROM sales LIMIT 10');
if ($result->num_rows > 0) {
    $first = true;
    while ($row = $result->fetch_assoc()) {
        if ($first) {
            echo "<table border='1'><tr>";
            foreach ($row as $key => $value) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No sales found</p>";
}

// Test stock calculation for a specific product
$product_name = "Makoro Sunrise";
$branch = "Livingstone";

echo "<h2>Stock Calculation Debug for '$product_name' in '$branch'</h2>";

$result = $conn->query("SELECT id FROM products WHERE name LIKE '%$product_name%'");
if ($result->num_rows > 0) {
    $product_id = $result->fetch_assoc()['id'];
    echo "<p>Product ID: $product_id</p>";
    
    // Check purchases
    $purchases = $conn->query("SELECT SUM(quantity) as total FROM purchases WHERE product_id = $product_id");
    $purchase_total = $purchases->fetch_assoc()['total'] ?? 0;
    echo "<p>Total Purchases: $purchase_total</p>";
    
    // Check sales for this branch
    $sales = $conn->query("SELECT SUM(quantity) as total FROM sales WHERE product_id = $product_id AND branch = '$branch'");
    $sales_total = $sales->fetch_assoc()['total'] ?? 0;
    echo "<p>Total Sales in $branch: $sales_total</p>";
    
    // Check transfers (if table exists)
    $transfers_in = $conn->query("SELECT SUM(quantity) as total FROM transfers WHERE product_id = $product_id AND dest_branch = '$branch'");
    $transfers_in_total = 0;
    if ($transfers_in) {
        $transfers_in_total = $transfers_in->fetch_assoc()['total'] ?? 0;
    }
    echo "<p>Transfers IN to $branch: $transfers_in_total</p>";
    
    $transfers_out = $conn->query("SELECT SUM(quantity) as total FROM transfers WHERE product_id = $product_id AND source_branch = '$branch'");
    $transfers_out_total = 0;
    if ($transfers_out) {
        $transfers_out_total = $transfers_out->fetch_assoc()['total'] ?? 0;
    }
    echo "<p>Transfers OUT from $branch: $transfers_out_total</p>";
    
    $calculated_stock = $purchase_total + $transfers_in_total - $sales_total - $transfers_out_total;
    echo "<p><strong>Calculated Stock: $calculated_stock</strong></p>";
    echo "<p>Formula: $purchase_total (purchases) + $transfers_in_total (transfers in) - $sales_total (sales) - $transfers_out_total (transfers out)</p>";
    
} else {
    echo "<p>Product '$product_name' not found</p>";
}

$conn->close();
?>
