<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$error = '';
$success = '';

$categories = [
    'Operation',
    'Logistics',
    'Office & Stationery',
    'Home & Farm',
    'Miscellaneous',
    'Bills'
];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $branch = $_POST['branch'] ?? '';
    $description = trim($_POST['description'] ?? '');
    if ($category && $amount > 0 && in_array($branch, $branches)) {
        $conn = getDbConnection();
        $stmt = $conn->prepare('INSERT INTO expenses (category, amount, branch, description, expense_date) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('sdss', $category, $amount, $branch, $description);
        if ($stmt->execute()) {
            $success = 'Expense recorded successfully!';
        } else {
            $error = 'Error recording expense.';
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill all fields correctly.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Expense</title>
    <link rel="stylesheet" href="../public/style.css">
</head>
<body>
    <h2>Add Expense</h2>
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
                <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="amount">Amount (ZMW)</label>
        <input type="number" id="amount" name="amount" min="0" step="0.01" required>
        <label for="branch">Branch</label>
        <select id="branch" name="branch" required>
            <option value="">Select Branch</option>
            <?php foreach ($branches as $b): ?>
                <option value="<?php echo $b; ?>"><?php echo $b; ?></option>
            <?php endforeach; ?>
        </select>
        <label for="description">Description (optional)</label>
        <input type="text" id="description" name="description">
        <button type="submit">Add Expense</button>
    </form>
    <a href="list.php">Back to Expense List</a>
</body>
</html>
