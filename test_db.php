<?php
require_once 'config/db.php';

try {
    $conn = getDbConnection();
    echo "Database connection successful!\n";
    
    // Check if users table exists and show existing users
    $result = $conn->query("SELECT username, role FROM users");
    if ($result) {
        echo "Existing users:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['username'] . " (" . $row['role'] . ")\n";
        }
    }
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
