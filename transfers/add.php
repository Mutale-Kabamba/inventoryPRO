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
    $source = $_POST['source_branch'] ?? '';
    $dest = $_POST['dest_branch'] ?? '';
    if ($product_id > 0 && $quantity > 0 && in_array($source, $branches) && in_array($dest, $branches) && $source !== $dest) {
        // Check available stock in source branch
        $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $product_id AND branch = '$source'");
        $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
        $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $product_id AND branch = '$source'");
        $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
        $transfers_out = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $product_id AND source_branch = '$source'");
        $out = ($row = $transfers_out->fetch_assoc()) ? intval($row['total']) : 0;
        $transfers_in = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $product_id AND dest_branch = '$source'");
        $in = ($row = $transfers_in->fetch_assoc()) ? intval($row['total']) : 0;
        $available = $purchased - $sold - $out + $in;
        if ($quantity > $available) {
            $error = 'Not enough stock in source branch.';
        } else {
            $stmt = $conn->prepare('INSERT INTO transfers (product_id, quantity, source_branch, dest_branch, transfer_date) VALUES (?, ?, ?, ?, NOW())');
            $stmt->bind_param('iiss', $product_id, $quantity, $source, $dest);
            if ($stmt->execute()) {
                $success = 'Stock transfer recorded successfully!';
            } else {
                $error = 'Error recording transfer.';
            }
            $stmt->close();
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
    <title>Add Stock Transfer</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Add Stock Transfer</h2>
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
        <label for="source_branch">Source Branch</label>
        <select id="source_branch" name="source_branch" required>
            <option value="">Select Source</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="dest_branch">Destination Branch</label>
        <select id="dest_branch" name="dest_branch" required>
            <option value="">Select Destination</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Add Transfer</button>
    </form>
    <a href="list.php">Back to Transfer List</a>
</body>
</html>
