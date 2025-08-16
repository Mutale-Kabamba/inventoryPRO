<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$error = '';
$success = '';

// Product categories and branches (static for now)
$categories = ['Brick', 'Water Storage', 'Paver', 'Kerb Stone', 'Block'];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category = $_POST['category'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $available_branches = $_POST['branches'] ?? [];
    if ($name && $category && $price > 0 && !empty($available_branches)) {
        $conn = getDbConnection();
        $stmt = $conn->prepare('SELECT id FROM products WHERE name = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Product already exists.';
        } else {
            $branches_str = implode(',', $available_branches);
            $stmt = $conn->prepare('INSERT INTO products (name, category, price, branches) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssds', $name, $category, $price, $branches_str);
            if ($stmt->execute()) {
                $success = 'Product added successfully!';
            } else {
                $error = 'Error adding product.';
            }
        }
        $stmt->close();
        $conn->close();
    } else {
        $error = 'Please fill all fields correctly.';
    }
}

// Set page variables for header
$pageTitle = 'Add Product - LedgerLink Inventory';
$currentPage = 'products';
?>
<?php include '../includes/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <h1>Add New Product</h1>
    <p>Create a new product in your inventory</p>
</div>

<!-- Add Product Form -->
<div class="form-container">
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="post">
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php echo ($_POST['category'] ?? '') == $cat ? 'selected' : ''; ?>>
                        <?php echo $cat; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="price">Price (K)</label>
            <input type="number" id="price" name="price" min="0" step="0.01" required value="<?php echo $_POST['price'] ?? ''; ?>">
        </div>
        
        <div class="form-group">
            <label>Available Branches</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;">
                <?php foreach ($branches as $branch): ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: normal;">
                        <input type="checkbox" name="branches[]" value="<?php echo $branch; ?>" 
                               <?php echo in_array($branch, $_POST['branches'] ?? []) ? 'checked' : ''; ?>>
                        <?php echo $branch; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="form-group" style="display: flex; gap: 1rem; justify-content: flex-end;">
            <a href="list.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary">Add Product</button>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
