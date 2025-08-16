<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$categories = [
    'Operation',
    'Logistics',
    'Office & Stationery',
    'Home & Farm',
    'Miscellaneous',
    'Bills'
];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

$conn = getDbConnection();
$id = intval($_GET['id'] ?? 0);
$error = '';
$success = '';

// Fetch expense data
$stmt = $conn->prepare('SELECT category, amount, branch, description FROM expenses WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($category, $amount, $branch, $description);
if (!$stmt->fetch()) {
    $error = 'Expense not found.';
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $new_category = $_POST['category'] ?? '';
    $new_amount = floatval($_POST['amount'] ?? 0);
    $new_branch = $_POST['branch'] ?? '';
    $new_description = trim($_POST['description'] ?? '');
    if ($new_category && $new_amount > 0 && in_array($new_branch, $branches)) {
        $stmt = $conn->prepare('UPDATE expenses SET category = ?, amount = ?, branch = ?, description = ? WHERE id = ?');
        $stmt->bind_param('sdssi', $new_category, $new_amount, $new_branch, $new_description, $id);
        if ($stmt->execute()) {
            $success = 'Expense updated successfully!';
            $category = $new_category;
            $amount = $new_amount;
            $branch = $new_branch;
            $description = $new_description;
        } else {
            $error = 'Error updating expense.';
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
    <title>Edit Expense</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Edit Expense</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="post">
        <label for="category">Category</label>
        <select id="category" name="category" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php if (($category ?? '') === $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="amount">Amount (ZMW)</label>
        <input type="number" id="amount" name="amount" min="0" step="0.01" value="<?php echo htmlspecialchars($amount ?? ''); ?>" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>" <?php if (($branch ?? '') == $b) echo 'selected'; ?>><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="description">Description (optional)</label>
        <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($description ?? ''); ?>">
        <button type="submit">Update Expense</button>
    </form>
    <a href="list.php">Back to Expense List</a>
</body>
</html>
