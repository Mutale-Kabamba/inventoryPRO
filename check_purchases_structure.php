<?php
require_once 'config/db.php';
$conn = getDbConnection();
$result = $conn->query('DESCRIBE purchases');
echo "Purchases table structure:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
$conn->close();
?>
