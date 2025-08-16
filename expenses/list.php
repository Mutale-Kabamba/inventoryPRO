<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT id, category, amount, branch, description, expense_date FROM expenses ORDER BY expense_date DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expense List</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Expense List</h2>
    <a href="add.php">Add New Expense</a>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>ID</th>
            <th>Category</th>
            <th>Amount (ZMW)</th>
            <th>Branch</th>
            <th>Description</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo number_format($row['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($row['branch']); ?></td>
                <td><?php echo htmlspecialchars($row['description']); ?></td>
                <td><?php echo $row['expense_date']; ?></td>
                <td>
                    <a href="edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                    <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this expense?');">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
    <a href="../public/dashboard.php">Back to Dashboard</a>
</body>
</html>
<?php $conn->close(); ?>
