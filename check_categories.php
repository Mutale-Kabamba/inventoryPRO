<?php
require_once 'config/db.php';

$conn = getDbConnection();

// Check products table structure
echo "Products table structure:\n";
$result = $conn->query('DESCRIBE products');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
}

echo "\n\nActual categories in products table:\n";
// Get distinct categories from products table
$result = $conn->query('SELECT DISTINCT category FROM products ORDER BY category');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "'" . $row['category'] . "'\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
