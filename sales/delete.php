<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($id > 0) {
    $conn = getDbConnection();
    $stmt = $conn->prepare('DELETE FROM sales WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $success = 'Sale deleted successfully!';
    } else {
        $error = 'Error deleting sale.';
    }
    $stmt->close();
    $conn->close();
} else {
    $error = 'Invalid sale ID.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Sale</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Delete Sale</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <a href="list.php">Back to Sales List</a>
</body>
</html>
