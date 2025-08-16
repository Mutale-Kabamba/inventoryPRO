<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$categories = ['Brick', 'Water Storage', 'Paver', 'Kerb Stone', 'Block'];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

$conn = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Fetch product data
$stmt = $conn->prepare('SELECT name, category, price, branches FROM products WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($name, $category, $price, $branches_str);
if (!$stmt->fetch()) {
    $error = 'Product not found.';
}
$stmt->close();
$selected_branches = isset($branches_str) ? explode(',', $branches_str) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $new_name = trim($_POST['name'] ?? '');
    $new_category = $_POST['category'] ?? '';
    $new_price = floatval($_POST['price'] ?? 0);
    $new_branches = $_POST['branches'] ?? [];
    if ($new_name && $new_category && $new_price > 0 && !empty($new_branches)) {
        // Check for name conflict
        $stmt = $conn->prepare('SELECT id FROM products WHERE name = ? AND id != ?');
        $stmt->bind_param('si', $new_name, $id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Product name already exists.';
        } else {
            $branches_str = implode(',', $new_branches);
            $stmt = $conn->prepare('UPDATE products SET name = ?, category = ?, price = ?, branches = ? WHERE id = ?');
            $stmt->bind_param('ssdsi', $new_name, $new_category, $new_price, $branches_str, $id);
            if ($stmt->execute()) {
                $success = 'Product updated successfully!';
                $name = $new_name;
                $category = $new_category;
                $price = $new_price;
                $selected_branches = $new_branches;
            } else {
                $error = 'Error updating product.';
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
    <title>Edit Product</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Edit Product</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="name">Product Name</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
        <label for="category">Category</label>
        <select id="category" name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php if (($category ?? '') === $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="price">Price (ZMW)</label>
        <input type="number" id="price" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($price ?? ''); ?>" required>
        <label>Available Branches</label>
        <?php foreach ($branches as $branch): ?>
            <label><input type="checkbox" name="branches[]" value="<?php echo $branch; ?>" <?php if (in_array($branch, $selected_branches)) echo 'checked'; ?>> <?php echo $branch; ?></label>
        <?php endforeach; ?>
        <br>
        <button type="submit">Update Product</button>
    </form>
    <a href="list.php">Back to Product List</a>
</body>
</html>
