<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo 'Invalid request method';
    exit;
}

try {
    $conn = getDbConnection();
    
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $available_branches = $_POST['branches'] ?? [];
    
    if (empty($name) || empty($category) || empty($price) || empty($available_branches)) {
        echo 'Please fill in all required fields';
        exit;
    }
    
    // Check if product already exists
    $stmt = $conn->prepare('SELECT id FROM products WHERE name = ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        echo 'Product already exists';
        exit;
    }
    
    // Convert branches array to comma-separated string
    $branches_str = implode(',', $available_branches);
    
    $stmt = $conn->prepare('INSERT INTO products (name, category, price, branches) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('ssds', $name, $category, $price, $branches_str);
    
    if ($stmt->execute()) {
        echo 'Product added successfully';
    } else {
        echo 'Failed to add product: ' . $stmt->error;
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
