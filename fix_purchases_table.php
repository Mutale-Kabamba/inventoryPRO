<?php
// Fix purchases table by adding missing unit_price column
require_once 'config/db.php';

try {
    $conn = getDbConnection();
    
    // Check if unit_price column exists
    $result = $conn->query("SHOW COLUMNS FROM purchases LIKE 'unit_price'");
    
    if ($result->num_rows == 0) {
        // Add unit_price column
        $conn->query("ALTER TABLE purchases ADD COLUMN unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity");
        echo "Added unit_price column to purchases table successfully!\n";
        
        // Also add total_cost column for storing total purchase cost
        $result2 = $conn->query("SHOW COLUMNS FROM purchases LIKE 'total_cost'");
        if ($result2->num_rows == 0) {
            $conn->query("ALTER TABLE purchases ADD COLUMN total_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price");
            echo "Added total_cost column to purchases table successfully!\n";
        }
    } else {
        echo "unit_price column already exists in purchases table.\n";
    }
    
    $conn->close();
    echo "Database update completed!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
