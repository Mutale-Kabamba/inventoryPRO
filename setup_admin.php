<?php
// Setup script to create admin user with properly hashed password
require_once 'config/db.php';

echo "<h2>Admin User Setup</h2>\n";

try {
    $conn = getDbConnection();
    echo "<p>Database connection successful!</p>\n";
    
    // Check if admin user already exists
    $stmt = $conn->prepare('SELECT id, username FROM users WHERE username = ? OR username = ? LIMIT 1');
    $username1 = 'admin';
    $username2 = 'Admin';
    $stmt->bind_param('ss', $username1, $username2);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>User '" . htmlspecialchars($user['username']) . "' already exists. Updating password...</p>\n";
        
        // Update existing user with hashed password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET password = ?, username = ? WHERE id = ?');
        $newUsername = 'admin';
        $stmt->bind_param('ssi', $hashedPassword, $newUsername, $user['id']);
        $stmt->execute();
        echo "<p style='color: green;'>Admin password updated successfully!</p>\n";
    } else {
        echo "<p>Creating new admin user...</p>\n";
        
        // Create new admin user with hashed password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
        $username = 'admin';
        $role = 'admin';
        $stmt->bind_param('sss', $username, $hashedPassword, $role);
        $stmt->execute();
        echo "<p style='color: green;'>Admin user created successfully!</p>\n";
    }
    
    echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0;'>\n";
    echo "<h3>Login Credentials:</h3>\n";
    echo "<strong>Username:</strong> admin<br>\n";
    echo "<strong>Password:</strong> admin123<br>\n";
    echo "<p>You can now login to the system at: <a href='public/login.php'>public/login.php</a></p>\n";
    echo "</div>\n";
    
    // Show all users in the database
    echo "<h3>All Users in Database:</h3>\n";
    $result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id");
    if ($result && $result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>ID</th><th>Username</th><th>Role</th><th>Created</th></tr>\n";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No users found in database.</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Make sure XAMPP MySQL service is running and the database 'inventory_db' exists.</p>\n";
}
?>
