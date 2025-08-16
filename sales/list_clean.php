<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT s.id, p.name AS product_name, s.quantity, s.branch, s.total, s.sale_date, s.product_id FROM sales s JOIN products p ON s.product_id = p.id ORDER BY s.sale_date DESC');

// Get products for modal forms
$products = [];
$productResult = $conn->query('SELECT id, name, price FROM products ORDER BY name ASC');
while ($productRow = $productResult->fetch_assoc()) {
    $products[] = $productRow;
}

$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

// Set page variables for header
$pageTitle = 'Sales Management - LedgerLink Inventory';
$currentPage = 'sales';
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Sales Styles -->
<style>
    .sales-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .sales-header {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .header-content h1 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #10b981, #059669);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .header-content p {
        color: #64748b;
        font-size: 1rem;
        margin: 0;
    }
    
    .header-actions .btn {
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
    
    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .period-performance-card {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
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
        color: #10b981;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        border-radius: 16px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-card.sales { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .stat-card.revenue { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .stat-card.quantity { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
    }
    
    .stat-card::before {
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
    
    .stat-icon {
        font-size: 32px;
        opacity: 0.9;
        min-width: 40px;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 4px;
        color: white;
        transition: opacity 0.3s ease;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        color: white;
        font-weight: 500;
        transition: opacity 0.3s ease;
    }
    
    .table-card {
        background: #ffffff;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
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
        margin: 0;
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
    
    .product-name {
        font-weight: 600;
        color: #1e293b;
    }
    
    .branch-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        background: #e0f2fe;
        color: #0369a1;
    }
    
    .total-amount {
        font-weight: 700;
        color: #059669;
    }
    
    .sale-date {
        color: #64748b;
        font-size: 0.8rem;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }
    
    .btn-outline {
        background: transparent;
        color: #3b82f6;
        border: 1px solid #3b82f6;
    }
    
    .btn-outline:hover {
        background: #3b82f6;
        color: white;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
        border: 1px solid #ef4444;
    }
    
    .btn-danger:hover {
        background: #dc2626;
        border-color: #dc2626;
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
        
        .sales-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .sales-table {
            font-size: 0.75rem;
        }
        
        .sales-table th,
        .sales-table td {
            padding: 0.75rem 0.5rem;
        }
        
        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="sales-container">
    <!-- Header -->
    <div class="sales-header">
        <div class="header-content">
            <h1>Sales Management</h1>
            <p>Track and manage your sales transactions across all branches</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="showAddSaleModal()">
                <span>📈</span> Record New Sale
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <?php
    // Get current date variables
    $today = date('Y-m-d');
    $week_start = date('Y-m-d', strtotime('monday this week'));
    $month_start = date('Y-m-01');
    $quarter_start = date('Y-m-d', strtotime('first day of january this year'));
    $year_start = date('Y-01-01');
    
    // Calculate statistics for all periods
    $statsQuery = "
        SELECT 
            -- Today
            COUNT(CASE WHEN DATE(sale_date) = '$today' THEN 1 END) as today_sales,
            SUM(CASE WHEN DATE(sale_date) = '$today' THEN total ELSE 0 END) as today_revenue,
            SUM(CASE WHEN DATE(sale_date) = '$today' THEN quantity ELSE 0 END) as today_quantity,
            
            -- Week
            COUNT(CASE WHEN DATE(sale_date) >= '$week_start' THEN 1 END) as week_sales,
            SUM(CASE WHEN DATE(sale_date) >= '$week_start' THEN total ELSE 0 END) as week_revenue,
            SUM(CASE WHEN DATE(sale_date) >= '$week_start' THEN quantity ELSE 0 END) as week_quantity,
            
            -- Month
            COUNT(CASE WHEN DATE(sale_date) >= '$month_start' THEN 1 END) as month_sales,
            SUM(CASE WHEN DATE(sale_date) >= '$month_start' THEN total ELSE 0 END) as month_revenue,
            SUM(CASE WHEN DATE(sale_date) >= '$month_start' THEN quantity ELSE 0 END) as month_quantity,
            
            -- Quarter
            COUNT(CASE WHEN DATE(sale_date) >= '$quarter_start' THEN 1 END) as quarter_sales,
            SUM(CASE WHEN DATE(sale_date) >= '$quarter_start' THEN total ELSE 0 END) as quarter_revenue,
            SUM(CASE WHEN DATE(sale_date) >= '$quarter_start' THEN quantity ELSE 0 END) as quarter_quantity,
            
            -- Year
            COUNT(CASE WHEN DATE(sale_date) >= '$year_start' THEN 1 END) as year_sales,
            SUM(CASE WHEN DATE(sale_date) >= '$year_start' THEN total ELSE 0 END) as year_revenue,
            SUM(CASE WHEN DATE(sale_date) >= '$year_start' THEN quantity ELSE 0 END) as year_quantity
        FROM sales
    ";
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    ?>
    
    <!-- Period Performance Section -->
    <div class="period-performance-card">
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
        
        <div class="stats-grid">
            <div class="stat-card sales">
                <div class="stat-icon">📦</div>
                <div class="stat-content">
                    <div class="stat-value" id="sales-value"><?php echo number_format($stats['week_sales']); ?></div>
                    <div class="stat-label" id="sales-label">This Week's Sales</div>
                </div>
            </div>
            
            <div class="stat-card revenue">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <div class="stat-value" id="revenue-value">K<?php echo number_format($stats['week_revenue'], 2); ?></div>
                    <div class="stat-label" id="revenue-label">This Week's Revenue</div>
                </div>
            </div>
            
            <div class="stat-card quantity">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <div class="stat-value" id="quantity-value"><?php echo number_format($stats['week_quantity']); ?> items</div>
                    <div class="stat-label" id="quantity-label">This Week's Quantity</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <span>📈</span> Recent Sales Transactions
            </h2>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="sales-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Branch</th>
                        <th>Total (K)</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo number_format($row['quantity']); ?></td>
                            <td>
                                <span class="branch-badge"><?php echo htmlspecialchars($row['branch']); ?></span>
                            </td>
                            <td class="total-amount">K<?php echo number_format($row['total'], 2); ?></td>
                            <td class="sale-date"><?php echo date('M j, Y g:i A', strtotime($row['sale_date'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-outline btn-sm" onclick="showEditSaleModal(<?php echo $row['id']; ?>, <?php echo $row['product_id']; ?>, <?php echo $row['quantity']; ?>, '<?php echo htmlspecialchars($row['branch']); ?>', <?php echo $row['total']; ?>)">
                                        Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="showDeleteSaleModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['product_name']).' - '.date('M j, Y', strtotime($row['sale_date'])); ?>')">
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📈</div>
                <h3>No Sales Found</h3>
                <p>Start recording sales to see them here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Period Performance Tab Functionality
document.addEventListener('DOMContentLoaded', function() {
    const periodData = {
        day: {
            sales: '<?php echo number_format($stats['today_sales']); ?>',
            revenue: '<?php echo number_format($stats['today_revenue'], 2); ?>',
            quantity: '<?php echo number_format($stats['today_quantity']); ?>',
            salesLabel: "Today's Sales",
            revenueLabel: "Today's Revenue",
            quantityLabel: "Today's Quantity"
        },
        week: {
            sales: '<?php echo number_format($stats['week_sales']); ?>',
            revenue: '<?php echo number_format($stats['week_revenue'], 2); ?>',
            quantity: '<?php echo number_format($stats['week_quantity']); ?>',
            salesLabel: "This Week's Sales",
            revenueLabel: "This Week's Revenue",
            quantityLabel: "This Week's Quantity"
        },
        month: {
            sales: '<?php echo number_format($stats['month_sales']); ?>',
            revenue: '<?php echo number_format($stats['month_revenue'], 2); ?>',
            quantity: '<?php echo number_format($stats['month_quantity']); ?>',
            salesLabel: "This Month's Sales",
            revenueLabel: "This Month's Revenue",
            quantityLabel: "This Month's Quantity"
        },
        quarter: {
            sales: '<?php echo number_format($stats['quarter_sales']); ?>',
            revenue: '<?php echo number_format($stats['quarter_revenue'], 2); ?>',
            quantity: '<?php echo number_format($stats['quarter_quantity']); ?>',
            salesLabel: "This Quarter's Sales",
            revenueLabel: "This Quarter's Revenue",
            quantityLabel: "This Quarter's Quantity"
        },
        year: {
            sales: '<?php echo number_format($stats['year_sales']); ?>',
            revenue: '<?php echo number_format($stats['year_revenue'], 2); ?>',
            quantity: '<?php echo number_format($stats['year_quantity']); ?>',
            salesLabel: "This Year's Sales",
            revenueLabel: "This Year's Revenue",
            quantityLabel: "This Year's Quantity"
        }
    };
    
    const tabs = document.querySelectorAll('.period-tab');
    const salesValue = document.getElementById('sales-value');
    const revenueValue = document.getElementById('revenue-value');
    const quantityValue = document.getElementById('quantity-value');
    const salesLabel = document.getElementById('sales-label');
    const revenueLabel = document.getElementById('revenue-label');
    const quantityLabel = document.getElementById('quantity-label');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get the period from data attribute
            const period = this.getAttribute('data-period');
            const data = periodData[period];
            
            // Update with fade animation
            const elementsToUpdate = [
                salesValue, revenueValue, quantityValue,
                salesLabel, revenueLabel, quantityLabel
            ];
            
            elementsToUpdate.forEach(element => {
                if (element) element.style.opacity = '0.5';
            });
            
            setTimeout(() => {
                // Update values
                salesValue.textContent = data.sales;
                revenueValue.textContent = 'K' + data.revenue;
                quantityValue.textContent = data.quantity + ' items';
                
                // Update labels
                salesLabel.textContent = data.salesLabel;
                revenueLabel.textContent = data.revenueLabel;
                quantityLabel.textContent = data.quantityLabel;
                
                // Restore opacity
                elementsToUpdate.forEach(element => {
                    if (element) element.style.opacity = '1';
                });
            }, 150);
        });
    });
});

// Ensure modal system is initialized
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for header script to load
    setTimeout(function() {
        if (!window.modal) {
            console.log('Modal system not found, initializing...');
            window.modal = new ModalSystem();
        }
    }, 100);
});

// Sales modal functions
function showAddSaleModal() {
    console.log('Add sale modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name'] . ' - K' . number_format($product['price'], 2)]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Record New Sale', [
        {name: 'product_id', label: 'Product', type: 'select', required: true, options: products},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity sold'},
        {name: 'branch', label: 'Branch', type: 'select', required: true, options: branches},
        {name: 'unit_price', label: 'Unit Price', type: 'number', step: '0.01', required: true, placeholder: '0.00'},
        {name: 'customer_name', label: 'Customer Name', type: 'text', placeholder: 'Customer name (optional)'},
        {name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'Sale notes (optional)'}
    ], {submitText: 'Record Sale'}).then(data => {
        window.submitForm('add_handler.php', data, 'POST');
    });
}

function showEditSaleModal(id, productId, quantity, branch, total) {
    console.log('Edit sale modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name'] . ' - K' . number_format($product['price'], 2)]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Edit Sale', [
        {name: 'id', label: 'ID', type: 'hidden', value: id},
        {name: 'product_id', label: 'Product', type: 'select', required: true, value: productId, options: products},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, value: quantity},
        {name: 'branch', label: 'Branch', type: 'select', required: true, value: branch, options: branches},
        {name: 'total', label: 'Total Amount', type: 'number', step: '0.01', required: true, value: total}
    ], {submitText: 'Update Sale'}).then(data => {
        window.submitForm('edit_handler.php', data, 'POST');
    });
}

function showDeleteSaleModal(id, name) {
    console.log('Delete sale modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    window.modal.showConfirmation(
        'Delete Sale',
        `Are you sure you want to delete this sale: ${name}?`,
        'This action cannot be undone.',
        'Delete Sale'
    ).then(confirmed => {
        if (confirmed) {
            window.submitForm('delete_handler.php', {id: id}, 'POST');
        }
    });
}
</script>

<?php $conn->close(); ?>
