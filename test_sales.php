<?php
require_once 'config/db.php';

try {
    $conn = getDbConnection();
    
    // Check sales table structure
    echo "Sales table structure:\n";
    $result = $conn->query("DESCRIBE sales");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    // Check current sales count
    $result = $conn->query("SELECT COUNT(*) as count FROM sales");
    $row = $result->fetch_assoc();
    echo "\nCurrent sales count: " . $row['count'] . "\n";
    
    // Check products table
    $result = $conn->query("SELECT COUNT(*) as count FROM products");
    $row = $result->fetch_assoc();
    echo "Products count: " . $row['count'] . "\n";
    
    // If no products, add some test products
    if ($row['count'] == 0) {
        echo "\nAdding test products...\n";
        $conn->query("INSERT INTO products (name, price, description) VALUES 
            ('Laptop Dell', 1500.00, 'High performance laptop'),
            ('Mouse Wireless', 25.50, 'Wireless optical mouse'),
            ('Keyboard Mechanical', 75.00, 'RGB mechanical keyboard')");
        echo "Test products added!\n";
    }
    
    // Add some test sales if none exist
    $result = $conn->query("SELECT COUNT(*) as count FROM sales");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        echo "\nAdding test sales...\n";
        $conn->query("INSERT INTO sales (product_id, quantity, branch, unit_price, total, customer_name, notes) VALUES 
            (1, 2, 'Lusaka', 1500.00, 3000.00, 'John Kabamba', 'Bulk purchase for office'),
            (2, 5, 'Livingstone', 25.50, 127.50, 'Mary Chanda', 'Office supplies'),
            (3, 1, 'Chisamba', 75.00, 75.00, '', 'Gaming setup')");
        echo "Test sales added!\n";
    }
    
    $conn->close();
    echo "\n✅ All checks completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
