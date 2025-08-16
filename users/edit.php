<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Fetch user data
$stmt = $conn->prepare('SELECT username, role FROM users WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($username, $role);
if (!$stmt->fetch()) {
    $error = 'User not found.';
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $new_username = trim($_POST['username'] ?? '');
    $new_password = $_POST['password'] ?? '';
    $new_role = $_POST['role'] ?? 'employee';
    if ($new_username && in_array($new_role, ['admin', 'employee'])) {
        // Check for username conflict
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $stmt->bind_param('si', $new_username, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            if ($new_password) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
                $stmt->bind_param('sssi', $new_username, $hash, $new_role, $id);
            } else {
                $stmt = $conn->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
                $stmt->bind_param('ssi', $new_username, $new_role, $id);
            }
            if ($stmt->execute()) {
                // Redirect to list with success message
                header('Location: list.php?success=edited');
                exit();
            } else {
                $error = 'Error updating user.';
            }
        }
        $stmt->close();
    } else {
        $error = 'Please fill all fields correctly.';
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <link rel="stylesheet" href="../public/style.css">
</head>

<body style="background:#f3f4f6;">
    <div style="max-width:900px;margin:32px auto 0 auto;background:#fff;border-radius:12px;box-shadow:0 4px 16px rgba(30,58,138,0.08);padding:2.5rem 2.5rem 2rem 2.5rem;">
    <h2 style="color:#1E3A8A;margin-bottom:2rem;">Edit User</h2>
        <?php if ($error): ?>
            <div style="background:#fee2e2;color:#b91c1c;padding:0.8rem 1.2rem;border-radius:6px;margin-bottom:1.5rem;font-weight:500;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <label for="username" style="display:block;font-weight:600;margin-bottom:0.5rem;">Username</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username ?? ''); ?>" required style="width:100%;padding:0.7rem 1rem;margin-bottom:1.5rem;border:1px solid #e5e7eb;border-radius:6px;font-size:1.1rem;">
            <label for="password" style="display:block;font-weight:600;margin-bottom:0.5rem;">Password (leave blank to keep current)</label>
            <input type="password" id="password" name="password" style="width:100%;padding:0.7rem 1rem;margin-bottom:1.5rem;border:1px solid #e5e7eb;border-radius:6px;font-size:1.1rem;">
            <label for="role" style="display:block;font-weight:600;margin-bottom:0.5rem;">Role</label>
            <select id="role" name="role" style="width:100%;padding:0.7rem 1rem;margin-bottom:2rem;border:1px solid #e5e7eb;border-radius:6px;font-size:1.1rem;">
                <option value="employee" <?php if (($role ?? '') === 'employee') echo 'selected'; ?>>Employee</option>
                <option value="admin" <?php if (($role ?? '') === 'admin') echo 'selected'; ?>>Admin</option>
            </select>
            <button type="submit" style="background:#1E3A8A;color:#fff;padding:0.7rem 2.2rem;border:none;border-radius:6px;font-size:1.1rem;font-weight:600;cursor:pointer;transition:background 0.2s;">Update User</button>
        </form>
        <a href="list.php" style="display:inline-block;margin-top:2.2rem;color:#059669;font-size:1.1rem;text-decoration:none;font-weight:500;">&larr; Back to User List</a>
    </div>
</body>
</html>
