<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$error = '';
$success = '';

$conn = getDbConnection();
// Fetch products and branches
$products = [];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];
$result = $conn->query('SELECT id, name, price FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $branch = $_POST['branch'] ?? '';
    if ($product_id > 0 && $quantity > 0 && in_array($branch, $branches)) {
        // Get product price
        $stmt = $conn->prepare('SELECT price FROM products WHERE id = ?');
        $stmt->bind_param('i', $product_id);
        $stmt->execute();
        $stmt->bind_result($price);
        if ($stmt->fetch()) {
            $total = $price * $quantity;
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO sales (product_id, quantity, branch, total, sale_date) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('iisd', $product_id, $quantity, $branch, $total);
            if ($stmt->execute()) {
                $success = 'Sale recorded successfully!';
            } else {
                $error = 'Error recording sale.';
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
    <title>Add Sale</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Add Sale</h2>
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
                <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['name']); ?> (ZMW <?php echo number_format($prod['price'], 2); ?>)</option>
            <?php endforeach; ?>
        </select>
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Add Sale</button>
    </form>
    <a href="list.php">Back to Sales List</a>
</body>
</html>
