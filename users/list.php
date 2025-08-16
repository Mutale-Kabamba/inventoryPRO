<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT id, username, role FROM users ORDER BY id ASC');
?>
<?php include '../includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="../public/style.css">
    <style>
        .admin-container {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(30,58,138,0.08);
            padding: 2rem 2.5rem 2.5rem 2.5rem;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .admin-header h2 {
            color: #1E3A8A;
            margin: 0;
        }
        .admin-header .add-btn {
            background: #059669;
            color: #fff;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }
        .admin-header .add-btn:hover {
            background: #047857;
        }
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        .user-table th, .user-table td {
            padding: 0.9rem 0.7rem;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .user-table th {
            background: #F3F4F6;
            color: #1E3A8A;
            font-weight: 600;
        }
        .user-table tr:last-child td {
            border-bottom: none;
        }
        .action-btn {
            background: #2563EB;
            color: #fff;
            border: none;
            padding: 0.4rem 0.9rem;
            border-radius: 4px;
            font-size: 0.95rem;
            margin-right: 0.5rem;
            text-decoration: none;
            transition: background 0.2s;
        }
        .action-btn.edit { background: #2563EB; }
        .action-btn.delete { background: #DC2626; }
        .action-btn.edit:hover { background: #1D4ED8; }
        .action-btn.delete:hover { background: #B91C1C; }
        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #059669;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h2>User Management</h2>
            <a href="add.php" class="add-btn">+ Add New User</a>
        </div>
        <table class="user-table">
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['role'])); ?></td>
                    <td>
                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="action-btn edit">Edit</a>
                        <?php if ($_SESSION['user_id'] != $row['id']): ?>
                            <a href="#" class="action-btn delete" onclick="showDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username'])); ?>'); return false;">Delete</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <a href="../public/dashboard.php" class="back-link">&larr; Back to Dashboard</a>
    </div>
    <!-- Custom Modal HTML -->
    <div id="modal-overlay" style="display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(30,58,138,0.15);z-index:1000;"></div>
    <div id="custom-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:10px;box-shadow:0 4px 24px rgba(30,58,138,0.18);padding:2rem 2.5rem;z-index:1001;min-width:320px;max-width:90vw;">
        <div id="modal-message" style="font-size:1.1rem;margin-bottom:1.5rem;color:#1E3A8A;"></div>
        <div id="modal-actions" style="text-align:right;"></div>
    </div>
    <form id="delete-form" method="get" action="delete.php" style="display:none;">
        <input type="hidden" name="id" id="delete-user-id">
    </form>
    <script>
    // Custom Modal Functions
    function showModal(message, actionsHtml) {
        document.getElementById('modal-message').innerHTML = message;
        document.getElementById('modal-actions').innerHTML = actionsHtml;
        document.getElementById('modal-overlay').style.display = 'block';
        document.getElementById('custom-modal').style.display = 'block';
    }
    function closeModal() {
        document.getElementById('modal-overlay').style.display = 'none';
        document.getElementById('custom-modal').style.display = 'none';
    }
    // Delete confirmation modal
    function showDeleteModal(userId, username) {
        showModal(
            `Are you sure you want to delete <b>${username}</b>?`,
            `<button onclick=\"closeModal()\" style=\"background:#e5e7eb;color:#1E3A8A;border:none;padding:0.5rem 1.2rem;border-radius:5px;font-size:1rem;margin-right:0.7rem;cursor:pointer;\">Cancel</button>` +
            `<button onclick=\"confirmDelete(${userId})\" style=\"background:#DC2626;color:#fff;border:none;padding:0.5rem 1.2rem;border-radius:5px;font-size:1rem;cursor:pointer;\">Delete</button>`
        );
    }
    function confirmDelete(userId) {
        document.getElementById('delete-user-id').value = userId;
        document.getElementById('delete-form').submit();
    }
    // Success popup modal
    const params = new URLSearchParams(window.location.search);
    if (params.has('success')) {
        let msg = '';
        switch (params.get('success')) {
            case 'added': msg = 'User added successfully!'; break;
            case 'edited': msg = 'User updated successfully!'; break;
            case 'deleted': msg = 'User deleted successfully!'; break;
        }
        if (msg) {
            showModal(`<span style=\"color:#059669;\">${msg}</span>`, `<button onclick=\"closeModal()\" style=\"background:#059669;color:#fff;border:none;padding:0.5rem 1.2rem;border-radius:5px;font-size:1rem;cursor:pointer;\">OK</button>`);
        }
        // Remove the param from URL after showing
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    // Close modal on overlay click
    document.getElementById('modal-overlay').onclick = closeModal;
    </script>
</body>
</html>
<?php $conn->close(); ?>
