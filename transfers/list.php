<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT t.id, p.name AS product_name, t.quantity, t.source_branch, t.dest_branch, t.transfer_date FROM transfers t JOIN products p ON t.product_id = p.id ORDER BY t.transfer_date DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Transfer List</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Stock Transfer List</h2>
    <a href="add.php">Add New Transfer</a>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Product</th>
            <th>Quantity</th>
            <th>Source Branch</th>
            <th>Destination Branch</th>
            <th>Date</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo htmlspecialchars($row['source_branch']); ?></td>
                <td><?php echo htmlspecialchars($row['dest_branch']); ?></td>
                <td><?php echo $row['transfer_date']; ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="../public/dashboard.php">Back to Dashboard</a>
</body>
</html>
<?php $conn->close(); ?>
