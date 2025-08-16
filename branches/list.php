<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$branches = [
    'Livingstone' => 'All products',
    'Chisamba' => 'Bricks & Water Tanks',
    'Lusaka' => 'Bricks only'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Branch Management</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Branch Management</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <th>Branch Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($branches as $name => $desc): ?>
            <tr>
                <td><?php echo $name; ?></td>
                <td><?php echo $desc; ?></td>
                <td>
                    <a href="../stock/view.php?branch=<?php echo $name; ?>">View Stock</a> |
                    <a href="../sales/list.php?branch=<?php echo $name; ?>">View Sales</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <a href="../public/dashboard.php">Back to Dashboard</a>
</body>
</html>