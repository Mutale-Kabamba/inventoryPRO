<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Fetch products and branches
$products = [];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];
$result = $conn->query('SELECT id, name, price FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch sale data
$stmt = $conn->prepare('SELECT product_id, quantity, branch FROM sales WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($product_id, $quantity, $branch);
if (!$stmt->fetch()) {
    $error = 'Sale not found.';
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $new_product_id = intval($_POST['product_id'] ?? 0);
    $new_quantity = intval($_POST['quantity'] ?? 0);
    $new_branch = $_POST['branch'] ?? '';
    if ($new_product_id > 0 && $new_quantity > 0 && in_array($new_branch, $branches)) {
        // Get product price
        $stmt = $conn->prepare('SELECT price FROM products WHERE id = ?');
        $stmt->bind_param('i', $new_product_id);
        $stmt->execute();
        $stmt->bind_result($price);
        if ($stmt->fetch()) {
            $total = $price * $new_quantity;
            $stmt->close();
            $stmt = $conn->prepare('UPDATE sales SET product_id = ?, quantity = ?, branch = ?, total = ? WHERE id = ?');
            $stmt->bind_param('iisdi', $new_product_id, $new_quantity, $new_branch, $total, $id);
            if ($stmt->execute()) {
                $success = 'Sale updated successfully!';
                $product_id = $new_product_id;
                $quantity = $new_quantity;
                $branch = $new_branch;
            } else {
                $error = 'Error updating sale.';
            }
            $stmt->close();
        } else {
            $error = 'Product not found.';
        }
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
    <title>Edit Sale</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Edit Sale</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="product_id">Product</label>
        <select id="product_id" name="product_id" required>
            <option value="">Select Product</option>
            <?php foreach ($products as $prod): ?>
                <option value="<?php echo $prod['id']; ?>" <?php if (($product_id ?? 0) == $prod['id']) echo 'selected'; ?>><?php echo htmlspecialchars($prod['name']); ?> (ZMW <?php echo number_format($prod['price'], 2); ?>)</option>
            <?php endforeach; ?>
        </select>
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" value="<?php echo htmlspecialchars($quantity ?? ''); ?>" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>" <?php if (($branch ?? '') == $b) echo 'selected'; ?>><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Update Sale</button>
    </form>
    <a href="list.php">Back to Sales List</a>
</body>
</html>
