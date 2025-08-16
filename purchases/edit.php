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
$result = $conn->query('SELECT id, name FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Fetch purchase data
$stmt = $conn->prepare('SELECT product_id, quantity, supplier, branch, damages FROM purchases WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($product_id, $quantity, $supplier, $branch, $damages);
if (!$stmt->fetch()) {
    $error = 'Purchase not found.';
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $new_product_id = intval($_POST['product_id'] ?? 0);
    $new_quantity = intval($_POST['quantity'] ?? 0);
    $new_supplier = trim($_POST['supplier'] ?? '');
    $new_branch = $_POST['branch'] ?? '';
    $new_damages = intval($_POST['damages'] ?? 0);
    if ($new_product_id > 0 && $new_quantity > 0 && $new_supplier && in_array($new_branch, $branches)) {
        $stmt = $conn->prepare('UPDATE purchases SET product_id = ?, quantity = ?, supplier = ?, branch = ?, damages = ? WHERE id = ?');
        $stmt->bind_param('iissii', $new_product_id, $new_quantity, $new_supplier, $new_branch, $new_damages, $id);
        if ($stmt->execute()) {
            $success = 'Purchase updated successfully!';
            $product_id = $new_product_id;
            $quantity = $new_quantity;
            $supplier = $new_supplier;
            $branch = $new_branch;
            $damages = $new_damages;
        } else {
            $error = 'Error updating purchase.';
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
    <title>Edit Purchase</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Edit Purchase</h2>
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
                <option value="<?php echo $prod['id']; ?>" <?php if (($product_id ?? 0) == $prod['id']) echo 'selected'; ?>><?php echo htmlspecialchars($prod['name']); ?></option>
            <?php endforeach; ?>
        </select>
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" min="1" value="<?php echo htmlspecialchars($quantity ?? ''); ?>" required>
        <label for="supplier">Supplier</label>
        <input type="text" id="supplier" name="supplier" value="<?php echo htmlspecialchars($supplier ?? ''); ?>" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>" <?php if (($branch ?? '') == $b) echo 'selected'; ?>><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="damages">Damages (if any)</label>
        <input type="number" id="damages" name="damages" min="0" value="<?php echo htmlspecialchars($damages ?? 0); ?>">
        <button type="submit">Update Purchase</button>
    </form>
    <a href="list.php">Back to Purchase List</a>
</body>
</html>
