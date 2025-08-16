<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();

// Filter parameters
$branch_filter = $_GET['branch'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
if ($branch_filter) {
    $where_conditions[] = "s.branch = '" . $conn->real_escape_string($branch_filter) . "'";
}
if ($category_filter) {
    $where_conditions[] = "p.category = '" . $conn->real_escape_string($category_filter) . "'";
}
if ($date_from) {
    $where_conditions[] = "DATE(s.sale_date) >= '" . $conn->real_escape_string($date_from) . "'";
}
if ($date_to) {
    $where_conditions[] = "DATE(s.sale_date) <= '" . $conn->real_escape_string($date_to) . "'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT s.id, p.name AS product_name, s.quantity, s.branch, s.total, s.sale_date 
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          $where_clause 
          ORDER BY s.sale_date DESC";

$result = $conn->query($query);

// Calculate totals
$total_sales = 0;
$total_quantity = 0;
$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
    $total_sales += $row['total'];
    $total_quantity += $row['quantity'];
}

$branches = ['Livingstone', 'Chisamba', 'Lusaka'];
$conn->close();

// Set page variables for header
$pageTitle = 'Sales Report - LedgerLink Inventory';
$currentPage = 'reports';
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Sales Report Styles -->
<style>
    .sales-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .sales-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .sales-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .sales-subtitle {
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
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 1.5rem;
        align-items: end;
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
        margin-bottom: 0.25rem;
    }
    
    .form-input, .form-select {
        padding: 0.875rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        background: #ffffff;
        height: 48px;
        min-width: 0;
    }
    
    .form-input:focus, .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .form-input::placeholder {
        color: #9ca3af;
    }
    
    .filter-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-start;
        margin-top: 1.5rem;
        grid-column: 1 / -1;
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
        height: 48px;
    }
    
    .btn-primary {
        background: #ef4444;
        color: white;
        padding: 0 2rem;
    }
    
    .btn-primary:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .btn-secondary {
        background: #f8fafc;
        color: #64748b;
        border: 1px solid #e2e8f0;
        padding: 0 1.5rem;
    }
    
    .btn-secondary:hover {
        background: #f1f5f9;
        color: #475569;
    }
    
    .performance-section {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 3rem;
    }
    
    .performance-header {
        margin-bottom: 1.5rem;
    }
    
    .performance-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    
    .period-tabs {
        display: flex;
        background: #f1f5f9;
        border-radius: 0.5rem;
        padding: 0.25rem;
        margin-bottom: 1.5rem;
        overflow-x: auto;
        gap: 0.125rem;
    }
    
    .period-tab {
        padding: 0.5rem 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        background: transparent;
        border: none;
        color: #64748b;
        white-space: nowrap;
        flex-shrink: 0;
        min-width: fit-content;
    }
    
    .period-tab.active {
        background: #ffffff;
        color: #3b82f6;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .summary-card {
        border-radius: 1rem;
        padding: 2rem;
        color: white;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
        min-height: 120px;
    }
    
    .summary-card:hover {
        transform: translateY(-2px);
    }
    
    .summary-card.sales { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .summary-card.quantity { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .summary-card.transactions { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
    }
    
    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transform: translate(30px, -30px);
    }
    
    .summary-icon {
        font-size: 2rem;
        opacity: 0.9;
        min-width: 40px;
        margin-bottom: 0;
    }
    
    .summary-content {
        flex: 1;
    }
    
    .summary-value {
        font-size: 2rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.25rem;
        line-height: 1;
    }
    
    .summary-label {
        font-size: 0.9rem;
        opacity: 0.9;
        color: white;
        font-weight: 500;
        margin-bottom: 0;
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
    
    .sales-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .sales-table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .sales-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .sales-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .sales-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #3b82f6;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .back-link:hover {
        color: #1d4ed8;
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
        .sales-container {
            padding: 1rem;
        }
        
        .filter-form {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .filter-actions {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
        }
        
        .sales-title {
            font-size: 2rem;
        }
        
        .sales-table {
            font-size: 0.75rem;
        }
        
        .sales-table th,
        .sales-table td {
            padding: 0.75rem 0.5rem;
        }
    }
</style>

<div class="sales-container">
    <!-- Header -->
    <div class="sales-header">
        <h1 class="sales-title">Sales Report</h1>
        <p class="sales-subtitle">Comprehensive sales analysis and transaction history</p>
    </div>

    <!-- Filters -->
    <div class="filters-card">
        <h2 class="filters-title">
            <span>🔍</span> Filter Options
        </h2>
        <form method="get" class="filter-form">
            <div class="form-group">
                <label for="category" class="form-label">Category:</label>
                <select id="category" name="category" class="form-select">
                    <option value="">All Categories</option>
                    <option value="Block" <?php if ($category_filter == 'Block') echo 'selected'; ?>>Block</option>
                    <option value="Brick" <?php if ($category_filter == 'Brick') echo 'selected'; ?>>Brick</option>
                    <option value="Kerb Stone" <?php if ($category_filter == 'Kerb Stone') echo 'selected'; ?>>Kerb Stone</option>
                    <option value="Paver" <?php if ($category_filter == 'Paver') echo 'selected'; ?>>Paver</option>
                    <option value="Water Storage" <?php if ($category_filter == 'Water Storage') echo 'selected'; ?>>Water Storage</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="branch" class="form-label">Branch:</label>
                <select id="branch" name="branch" class="form-select">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b; ?>" <?php if ($branch_filter == $b) echo 'selected'; ?>><?php echo $b; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="date_from" class="form-label">From Date:</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-input" placeholder="dd/mm/yyyy">
            </div>
            
            <div class="form-group">
                <label for="date_to" class="form-label">To Date:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-input" placeholder="dd/mm/yyyy">
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    � Filter
                </button>
                <a href="sales.php" class="btn btn-secondary">
                    �️ Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Sales Performance Section -->
    <div class="performance-section">
        <div class="performance-header">
            <h2 class="performance-title">
                <span>📊</span> Sales Performance
            </h2>
        </div>
        
        <div class="period-tabs">
            <button class="period-tab" data-period="day">Day</button>
            <button class="period-tab active" data-period="week">Week</button>
            <button class="period-tab" data-period="month">Month</button>
            <button class="period-tab" data-period="quarter">Quarter</button>
            <button class="period-tab" data-period="year">Year</button>
        </div>
        
        <!-- Summary -->
        <div class="summary-grid">
        <div class="summary-card sales">
            <div class="summary-icon">�</div>
            <div class="summary-content">
                <div class="summary-value"><?php echo count($sales_data); ?></div>
                <div class="summary-label">This Week's Sales</div>
            </div>
        </div>
        
        <div class="summary-card quantity">
            <div class="summary-icon">�</div>
            <div class="summary-content">
                <div class="summary-value">K<?php echo number_format($total_sales, 2); ?></div>
                <div class="summary-label">This Week's Revenue</div>
            </div>
        </div>
        
        <div class="summary-card transactions">
            <div class="summary-icon">�</div>
            <div class="summary-content">
                <div class="summary-value"><?php echo number_format($total_quantity); ?> items</div>
                <div class="summary-label">This Week's Quantity</div>
            </div>
        </div>
    </div>
    </div>

    <!-- Sales Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <span>📈</span> Sales Transactions
            </h2>
        </div>
        
        <?php if (!empty($sales_data)): ?>
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Branch</th>
                        <th>Total (ZMW)</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_data as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo number_format($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td>K<?php echo number_format($row['total'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['sale_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <h3>No Sales Data Found</h3>
                <p>Try adjusting your filter criteria or check back later.</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="dashboard.php" class="back-link">
        <span>←</span> Back to Reports Dashboard
    </a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodTabs = document.querySelectorAll('.period-tab');
    
    periodTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            periodTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get the selected period
            const period = this.getAttribute('data-period');
            
            // Update the performance data
            updatePerformanceData(period);
        });
    });
    
    function updatePerformanceData(period) {
        // Show loading state
        const summaryCards = document.querySelectorAll('.summary-value');
        summaryCards.forEach(card => {
            card.textContent = 'Loading...';
        });
        
        // Make AJAX request to get updated data
        fetch(`get_performance_data.php?period=${period}&branch=${encodeURIComponent('<?php echo $branch_filter; ?>')}&category=${encodeURIComponent('<?php echo $category_filter; ?>')}&date_from=${encodeURIComponent('<?php echo $date_from; ?>')}&date_to=${encodeURIComponent('<?php echo $date_to; ?>')}`)
            .then(response => response.json())
            .then(data => {
                // Update the cards with new data
                document.querySelector('.summary-card.sales .summary-value').textContent = data.total_transactions;
                document.querySelector('.summary-card.quantity .summary-value').textContent = 'K' + parseFloat(data.total_revenue).toFixed(2);
                document.querySelector('.summary-card.transactions .summary-value').textContent = data.total_quantity + ' items';
                
                // Update the labels based on period
                const periodLabels = {
                    'day': "Today's",
                    'week': "This Week's", 
                    'month': "This Month's",
                    'quarter': "This Quarter's",
                    'year': "This Year's"
                };
                
                document.querySelector('.summary-card.sales .summary-label').textContent = periodLabels[period] + ' Sales';
                document.querySelector('.summary-card.quantity .summary-label').textContent = periodLabels[period] + ' Revenue';
                document.querySelector('.summary-card.transactions .summary-label').textContent = periodLabels[period] + ' Quantity';
            })
            .catch(error => {
                console.error('Error fetching performance data:', error);
                // Reset to original values on error
                document.querySelector('.summary-card.sales .summary-value').textContent = '<?php echo count($sales_data); ?>';
                document.querySelector('.summary-card.quantity .summary-value').textContent = 'K<?php echo number_format($total_sales, 2); ?>';
                document.querySelector('.summary-card.transactions .summary-value').textContent = '<?php echo number_format($total_quantity); ?> items';
            });
    }
});
</script>

<?php include '../includes/footer.php'; ?>