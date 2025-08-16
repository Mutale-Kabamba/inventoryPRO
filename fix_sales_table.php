<?php
require_once 'config/db.php';

echo "Fixing Sales Table Structure...\n";

try {
    $conn = getDbConnection();
    
    // Check if the additional columns exist
    $result = $conn->query("DESCRIBE sales");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    $updates_made = false;
    
    // Add unit_price column if it doesn't exist
    if (!in_array('unit_price', $existing_columns)) {
        echo "Adding unit_price column...\n";
        $conn->query("ALTER TABLE sales ADD COLUMN unit_price DECIMAL(10,2) DEFAULT 0 AFTER branch");
        $updates_made = true;
    }
    
    // Add customer_name column if it doesn't exist
    if (!in_array('customer_name', $existing_columns)) {
        echo "Adding customer_name column...\n";
        $conn->query("ALTER TABLE sales ADD COLUMN customer_name VARCHAR(255) DEFAULT '' AFTER sale_date");
        $updates_made = true;
    }
    
    // Add notes column if it doesn't exist
    if (!in_array('notes', $existing_columns)) {
        echo "Adding notes column...\n";
        $conn->query("ALTER TABLE sales ADD COLUMN notes TEXT DEFAULT '' AFTER customer_name");
        $updates_made = true;
    }
    
    // Update existing records to calculate unit_price from total/quantity
    if ($updates_made) {
        echo "Updating existing records with unit_price...\n";
        $conn->query("UPDATE sales SET unit_price = CASE WHEN quantity > 0 THEN total / quantity ELSE 0 END WHERE unit_price = 0");
    }
    
    // Add indexes for better performance
    echo "Adding indexes for better performance...\n";
    $conn->query("CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(sale_date)");
    $conn->query("CREATE INDEX IF NOT EXISTS idx_sales_branch ON sales(branch)");
    $conn->query("CREATE INDEX IF NOT EXISTS idx_sales_product ON sales(product_id)");
    
    if ($updates_made) {
        echo "✅ Sales table structure updated successfully!\n";
    } else {
        echo "✅ Sales table structure is already up to date!\n";
    }
    
    // Show final structure
    echo "\nCurrent Sales Table Structure:\n";
    $result = $conn->query("DESCRIBE sales");
    while ($row = $result->fetch_assoc()) {
        echo "- {$row['Field']}: {$row['Type']}\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
