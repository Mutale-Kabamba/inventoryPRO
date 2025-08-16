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
    $stmt = $conn->prepare('DELETE FROM purchases WHERE id = ?');
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $success = 'Purchase deleted successfully!';
    } else {
        $error = 'Error deleting purchase.';
    }
    $stmt->close();
    $conn->close();
} else {
    $error = 'Invalid purchase ID.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete Purchase</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Delete Purchase</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <a href="list.php">Back to Purchase List</a>
</body>
</html>
