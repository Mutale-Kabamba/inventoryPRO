<?php
require_once 'config/db.php';

try {
    $conn = getDbConnection();
    
    echo "Expenses table structure:\n";
    $result = $conn->query('DESCRIBE expenses');
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
    echo "\nSales table structure:\n";
    $result = $conn->query('DESCRIBE sales');
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
