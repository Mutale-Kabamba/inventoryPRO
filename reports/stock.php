<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();

// Filter parameters
$category_filter = $_GET['category'] ?? '';
$stock_level_filter = $_GET['stock_level'] ?? '';
$branch_filter = $_GET['branch'] ?? '';

// Fetch all products
$products = [];
$result = $conn->query('SELECT id, name, category FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[$row['id']] = $row;
}

$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

// Calculate stock for each product and branch
$stock_data = [];
foreach ($products as $pid => $product) {
    $row_data = [
        'id' => $pid,
        'name' => $product['name'],
        'category' => $product['category'],
        'total_stock' => 0
    ];
    
    foreach ($branches as $branch) {
        // Purchases
        $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $pid AND branch = '$branch'");
        $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Sales
        $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $pid AND branch = '$branch'");
        $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers Out
        $transfers_out = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND source_branch = '$branch'");
        $out = ($row = $transfers_out->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers In
        $transfers_in = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND dest_branch = '$branch'");
        $in = ($row = $transfers_in->fetch_assoc()) ? intval($row['total']) : 0;
        
        $branch_stock = $purchased - $sold - $out + $in;
        $row_data[$branch] = $branch_stock;
        $row_data['total_stock'] += $branch_stock;
    }
    
    $stock_data[] = $row_data;
}

// Apply filters
$filtered_stock_data = [];
foreach ($stock_data as $row) {
    $include = true;
    
    // Category filter
    if ($category_filter && $row['category'] !== $category_filter) {
        $include = false;
    }
    
    // Stock level filter
    if ($stock_level_filter) {
        $total_stock = $row['total_stock'];
        switch ($stock_level_filter) {
            case 'out_of_stock':
                if ($total_stock > 0) $include = false;
                break;
            case 'low_stock':
                if ($total_stock >= 10 || $total_stock <= 0) $include = false;
                break;
            case 'good_stock':
                if ($total_stock < 10 || $total_stock >= 50) $include = false;
                break;
            case 'excellent_stock':
                if ($total_stock < 50) $include = false;
                break;
        }
    }
    
    // Branch filter (check if branch has stock)
    if ($branch_filter && $row[$branch_filter] <= 0) {
        $include = false;
    }
    
    if ($include) {
        $filtered_stock_data[] = $row;
    }
}

// Get unique categories for filter dropdown
$categories = array_unique(array_column($stock_data, 'category'));
sort($categories);

$conn->close();

// Set page variables for header
$pageTitle = 'Stock Report - LedgerLink Inventory';
$currentPage = 'reports';
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Stock Report Styles -->
<style>
    .stock-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .stock-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .stock-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .stock-subtitle {
        color: #64748b;
        font-size: 1.1rem;
        font-weight: 400;
    }
    
    .filters-card {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }
    
    .filters-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        align-items: end;
    }
    
    .filter-actions {
        grid-column: 1 / -1;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-start;
        margin-top: 0.5rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .form-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .form-input, .form-select {
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: #ffffff;
    }
    
    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-secondary {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
        background: #f1f5f9;
        color: #475569;
    }
    
    .performance-header {
        background: #ffffff;
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .performance-title-section {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .performance-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .time-filters {
        display: flex;
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 0.125rem;
        gap: 0.0625rem;
        border: 1px solid #e2e8f0;
    }
    
    .time-filter-btn {
        padding: 0.375rem 0.75rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
        background: transparent;
        color: #64748b;
        min-width: 50px;
        text-align: center;
    }
    
    .time-filter-btn:hover {
        background: rgba(16, 185, 129, 0.08);
        color: #10b981;
    }
    
    .time-filter-btn.active {
        background: #10b981;
        color: white;
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.2);
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .summary-card {
        background: linear-gradient(135deg, var(--card-bg-start), var(--card-bg-end));
        border-radius: 0.75rem;
        padding: 1.25rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border: none;
        position: relative;
        overflow: hidden;
        color: white;
        min-height: 90px;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .summary-card::before {
        content: '';
        position: absolute;
        top: -30%;
        right: -20%;
        width: 80px;
        height: 80px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        opacity: 0.7;
    }
    
    .summary-card::after {
        content: '';
        position: absolute;
        bottom: -15%;
        left: -15%;
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }
    
    .summary-card.products { 
        --card-bg-start: #10b981;
        --card-bg-end: #059669;
    }
    
    .summary-card.low-stock { 
        --card-bg-start: #ef4444;
        --card-bg-end: #dc2626;
    }
    
    .summary-card.total-stock { 
        --card-bg-start: #3b82f6;
        --card-bg-end: #1d4ed8;
    }
    
    .summary-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        position: relative;
        z-index: 2;
        flex-shrink: 0;
    }
    
    .summary-content {
        position: relative;
        z-index: 2;
        flex: 1;
    }
    
    .summary-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        opacity: 0.95;
        line-height: 1.2;
        margin-bottom: 0.25rem;
    }
    
    .summary-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        line-height: 1;
    }
    
    .table-card {
        background: #ffffff;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }
    
    .table-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .table-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .stock-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .stock-table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .stock-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .stock-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .stock-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .stock-level {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
    }
    
    .stock-critical {
        background: #fef2f2;
        color: #dc2626;
    }
    
    .stock-low {
        background: #fef3c7;
        color: #d97706;
    }
    
    .stock-good {
        background: #ecfdf5;
        color: #059669;
    }
    
    .stock-excellent {
        background: #eff6ff;
        color: #2563eb;
    }
    
    .category-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #f1f5f9;
        color: #64748b;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #10b981;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .back-link:hover {
        color: #059669;
        transform: translateX(-2px);
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #64748b;
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        .stock-container {
            padding: 1rem;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
        }
        
        .stock-title {
            font-size: 2rem;
        }
        
        .stock-table {
            font-size: 0.75rem;
        }
        
        .stock-table th,
        .stock-table td {
            padding: 0.75rem 0.5rem;
        }
        
        .performance-header {
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }
        
        .performance-title-section {
            justify-content: center;
        }
        
        .time-filters {
            justify-content: center;
        }
        
        .time-filter-btn {
            flex: 1;
            min-width: auto;
        }
    }
</style>

<?php
// Calculate summary statistics from filtered data
$total_products = count($filtered_stock_data);
$low_stock_count = 0;
$total_stock_units = 0;

foreach ($filtered_stock_data as $row) {
    $total_stock_units += $row['total_stock'];
    if ($row['total_stock'] < 10) {
        $low_stock_count++;
    }
}
?>

<div class="stock-container">
    <!-- Header -->
    <div class="stock-header">
        <h1 class="stock-title">Stock Report</h1>
        <p class="stock-subtitle">Real-time inventory levels across all branches and products</p>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <h2 class="filters-title">
            <span>🔍</span> Smart Filters
        </h2>
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="category" class="form-label">Category:</label>
                <select id="category" name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php if ($category_filter == $cat) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="stock_level" class="form-label">Stock Level:</label>
                <select id="stock_level" name="stock_level" class="form-select">
                    <option value="">All Levels</option>
                    <option value="out_of_stock" <?php if ($stock_level_filter == 'out_of_stock') echo 'selected'; ?>>Out of Stock (0)</option>
                    <option value="low_stock" <?php if ($stock_level_filter == 'low_stock') echo 'selected'; ?>>Low Stock (1-9)</option>
                    <option value="good_stock" <?php if ($stock_level_filter == 'good_stock') echo 'selected'; ?>>Good Stock (10-49)</option>
                    <option value="excellent_stock" <?php if ($stock_level_filter == 'excellent_stock') echo 'selected'; ?>>Excellent Stock (50+)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="branch" class="form-label">Branch Focus:</label>
                <select id="branch" name="branch" class="form-select">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch; ?>" <?php if ($branch_filter == $branch) echo 'selected'; ?>>
                            Has Stock in <?php echo $branch; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <span>📊</span> Filter
                </button>
                <a href="stock.php" class="btn btn-secondary">
                    <span>🔄</span> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Performance Header with Time Filters -->
    <div class="performance-header">
        <div class="performance-title-section">
            <h2 class="performance-title">📊 Stock Performance</h2>
        </div>
        <div class="time-filters">
            <button class="time-filter-btn active" data-period="day">Day</button>
            <button class="time-filter-btn" data-period="week">Week</button>
            <button class="time-filter-btn" data-period="month">Month</button>
            <button class="time-filter-btn" data-period="quarter">Quarter</button>
            <button class="time-filter-btn" data-period="year">Year</button>
        </div>
    </div>

    <!-- Summary -->
    <div class="summary-grid">
        <div class="summary-card products">
            <div class="summary-icon">📦</div>
            <div class="summary-content">
                <div class="summary-label">Total Products</div>
                <div class="summary-value"><?php echo number_format($total_products); ?></div>
            </div>
        </div>
        
        <div class="summary-card low-stock">
            <div class="summary-icon">⚠️</div>
            <div class="summary-content">
                <div class="summary-label">Low Stock Items</div>
                <div class="summary-value"><?php echo number_format($low_stock_count); ?></div>
            </div>
        </div>
        
        <div class="summary-card total-stock">
            <div class="summary-icon">📊</div>
            <div class="summary-content">
                <div class="summary-label">Total Stock Units</div>
                <div class="summary-value"><?php echo number_format($total_stock_units); ?></div>
            </div>
        </div>
    </div>

    <!-- Stock Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <span>📋</span> Inventory by Product and Branch
            </h2>
        </div>
        
        <?php if (!empty($filtered_stock_data)): ?>
            <table class="stock-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <?php foreach ($branches as $branch): ?>
                            <th><?php echo $branch; ?></th>
                        <?php endforeach; ?>
                        <th>Total Stock</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filtered_stock_data as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td>
                                <span class="category-badge">
                                    <?php echo htmlspecialchars($row['category']); ?>
                                </span>
                            </td>
                            <?php foreach ($branches as $branch): ?>
                                <td><?php echo number_format($row[$branch]); ?></td>
                            <?php endforeach; ?>
                            <td><strong><?php echo number_format($row['total_stock']); ?></strong></td>
                            <td>
                                <?php
                                $total = $row['total_stock'];
                                if ($total <= 0) {
                                    echo '<span class="stock-level stock-critical">Out of Stock</span>';
                                } elseif ($total < 10) {
                                    echo '<span class="stock-level stock-low">Low Stock</span>';
                                } elseif ($total < 50) {
                                    echo '<span class="stock-level stock-good">Good</span>';
                                } else {
                                    echo '<span class="stock-level stock-excellent">Excellent</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <h3>No Stock Data Found</h3>
                <p>Try adjusting your filter criteria to see results.</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="dashboard.php" class="back-link">
        <span>←</span> Back to Reports Dashboard
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const timeFilterBtns = document.querySelectorAll('.time-filter-btn');
    
    // Note: Stock data is current inventory levels, not time-based
    // Time filters will be used for future stock movement reports
    timeFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Show loading state
            this.style.opacity = '0.6';
            this.style.pointerEvents = 'none';
            
            // Remove active class from all buttons
            timeFilterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // For now, just provide visual feedback
            // In future, this could filter stock movements by time period
            setTimeout(() => {
                this.style.opacity = '1';
                this.style.pointerEvents = 'auto';
            }, 500);
        });
    });
    
    // Add quick filter functionality for dropdowns
    const categorySelect = document.getElementById('category');
    const stockLevelSelect = document.getElementById('stock_level');
    const branchSelect = document.getElementById('branch');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value !== '') {
                // Auto-submit form when category changes
                this.form.submit();
            }
        });
    }
    
    if (stockLevelSelect) {
        stockLevelSelect.addEventListener('change', function() {
            if (this.value !== '') {
                // Auto-submit form when stock level changes
                this.form.submit();
            }
        });
    }
    
    if (branchSelect) {
        branchSelect.addEventListener('change', function() {
            if (this.value !== '') {
                // Auto-submit form when branch changes
                this.form.submit();
            }
        });
    }
    
    // Add real-time stock level indicators
    const stockTable = document.querySelector('.stock-table');
    if (stockTable) {
        const rows = stockTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
                this.style.transition = 'all 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
            });
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>