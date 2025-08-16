<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$currentPage = 'admin';

$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

if ($id > 0) {
    $conn = getDbConnection();
    // Prevent deleting self (optional)
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            // Redirect to list with success message
            header('Location: list.php?success=deleted');
            exit();
        } else {
            $error = 'Error deleting user.';
        }
        $stmt->close();
    }
    $conn->close();
} else {
    $error = 'Invalid user ID.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delete User</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Delete User</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <a href="list.php">Back to User List</a>
    <?php endif; ?>
</body>
</html>
