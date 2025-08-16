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
$result = $conn->query('SELECT id, name FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $unit_price = floatval($_POST['unit_price'] ?? 0);
    $supplier = trim($_POST['supplier'] ?? '');
    $branch = $_POST['branch'] ?? '';
    $damages = intval($_POST['damages'] ?? 0);
    $total_cost = $quantity * $unit_price;
    
    if ($product_id > 0 && $quantity > 0 && $unit_price > 0 && $supplier && in_array($branch, $branches)) {
        $stmt = $conn->prepare('INSERT INTO purchases (product_id, quantity, unit_price, total_cost, supplier, branch, damages, purchase_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->bind_param('iiddssi', $product_id, $quantity, $unit_price, $total_cost, $supplier, $branch, $damages);
        if ($stmt->execute()) {
            $success = 'Purchase recorded successfully!';
        } else {
            $error = 'Error recording purchase.';
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
    <title>Add Purchase</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Add Purchase</h2>
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
                <option value="<?php echo $prod['id']; ?>"><?php echo htmlspecialchars($prod['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" required>
        <label for="unit_price">Unit Price</label>
        <input type="number" id="unit_price" name="unit_price" min="0" step="0.01" required>
        <label for="supplier">Supplier</label>
        <input type="text" id="supplier" name="supplier" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="damages">Damages (if any)</label>
        <input type="number" id="damages" name="damages" min="0" value="0">
        <button type="submit">Add Purchase</button>
    </form>
    <a href="list.php">Back to Purchase List</a>
</body>
</html>
