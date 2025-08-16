<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT s.id, p.name AS product_name, s.quantity, s.branch, s.total, s.sale_date, s.product_id, s.customer_name, s.notes FROM sales s JOIN products p ON s.product_id = p.id ORDER BY s.sale_date DESC');

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
    
    <style>
    .modal-footer {
        background: #fff !important;
        border-top: 1px solid #e5e7eb !important;
        display: flex !important;
        justify-content: flex-end !important;
        align-items: center !important;
        padding: 24px 32px 56px 32px !important; /* Further increased bottom padding */
        border-radius: 0 0 24px 24px !important;
        box-shadow: none !important;
        gap: 16px !important;
    }
    .modal-footer .btn-modal {
        min-width: 120px;
        padding: 12px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 16px 0 rgba(80, 112, 255, 0.10);
        margin: 0 0 0 16px !important;
        transition: box-shadow 0.2s;
    }
    .modal-footer .btn-modal:first-child {
        margin-left: 0 !important;
    }
    
        border-bottom: 1px solid #e2e8f0;
    
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
                                    <button class="btn btn-outline btn-sm"
                                        data-modal-edit='<?php echo json_encode([
                                            "type" => "Sale",
                                            "fields" => [
                                                ["name" => "id", "label" => "Sale ID", "type" => "hidden", "value" => $row["id"]],
                                                ["name" => "product_id", "label" => "Product", "type" => "select", "required" => true, "options" => array_map(function($p) use ($row) { return ["value" => $p["id"], "text" => $p["name"].' - K'.number_format($p["price"],2), "selected" => $p["id"] == $row["product_id"]]; }, $products)],
                                                ["name" => "quantity", "label" => "Quantity", "type" => "number", "required" => true, "value" => $row["quantity"], "min" => 1],
                                                ["name" => "unit_price", "label" => "Unit Price (K)", "type" => "number", "required" => true, "value" => number_format($row["total"]/$row["quantity"],2), "step" => "0.01", "min" => 0],
                                                ["name" => "branch", "label" => "Branch", "type" => "select", "required" => true, "options" => array_map(function($b) use ($row) { return ["value" => $b, "text" => $b, "selected" => $b == $row["branch"]]; }, $branches)],
                                                ["name" => "customer_name", "label" => "Customer Name", "type" => "text", "value" => $row["customer_name"] ?? '', "placeholder" => "Enter customer name (optional)"],
                                                ["name" => "notes", "label" => "Notes", "type" => "textarea", "value" => $row["notes"] ?? '', "placeholder" => "Add any additional notes about this sale..."]
                                            ],
                                            "url" => "edit_handler.php",
                                            "id" => $row["id"],
                                            "icon" => ""
                                        ]); ?>'>
                                        Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm"
                                        data-modal-delete='<?php echo json_encode([
                                            "type" => "Sale",
                                            "name" => $row["product_name"].' - '.date('M j, Y', strtotime($row['sale_date'])),
                                            "url" => "delete_handler.php",
                                            "id" => $row["id"],
                                            "icon" => ""
                                        ]); ?>'>
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
    // Wait for modal system to be available
    let attempts = 0;
    const maxAttempts = 50;
    
    function checkModalSystem() {
        attempts++;
        console.log(`Attempt ${attempts}: Checking modal system...`);
        console.log('window.modal:', window.modal);
        
        if (window.modal && typeof window.modal.showForm === 'function') {
            console.log('✅ Modal system ready');
            
            // Test the modal system
            console.log('Modal methods:', Object.getOwnPropertyNames(window.modal.__proto__));
            return;
        }
        
        if (attempts < maxAttempts) {
            setTimeout(checkModalSystem, 100);
        } else {
            console.error('❌ Modal system failed to initialize after', maxAttempts * 100, 'ms');
        }
    }
    
    checkModalSystem();
});

// Dashboard-style POS System
function showAddSaleModal() {
    console.log('🛒 showAddSaleModal called - Dashboard POS Style');
    
    if (!window.modal || typeof window.modal.showModal !== 'function') {
        console.log('❌ Modal system not available');
        alert('Modal system not loaded. Please refresh the page and try again.');
        return;
    }
    
    console.log('✅ Using modal system to show POS...');
    
    const products = <?php echo json_encode($products); ?>;
    const branches = <?php echo json_encode($branches); ?>;
    
    // Create Mini POS interface like dashboard
    let cart = [];
    let selectedBranch = branches[0] || '';
    let customerName = '';
    let notes = '';
    
    const posHTML = `
        <div style="max-height: 70vh; overflow-y: auto;">
            <!-- Product Selection -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Select Product to Add</label>
                <div style="display: flex; gap: 0.75rem;">
                    <select id="productSelect" style="flex: 1; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;">
                        <option value="">Choose a product...</option>
                        ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} - K${parseFloat(p.price).toFixed(2)}</option>`).join('')}
                    </select>
                    <button id="addProductBtn" onclick="addToCart()" style="background: #10b981; color: white; border: none; padding: 0.75rem 1rem; border-radius: 0.5rem; font-weight: 500; cursor: pointer;" disabled>Add to Cart</button>
                </div>
            </div>
            
            <!-- Branch Selection -->
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Branch</label>
                <select id="branchSelect" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;" onchange="selectedBranch = this.value">
                    ${branches.map(b => `<option value="${b}" ${b === selectedBranch ? 'selected' : ''}>${b}</option>`).join('')}
                </select>
            </div>
            
            <!-- Shopping Cart -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                    <label style="font-weight: 600; color: #374151;">🛒 Cart Items</label>
                    <button onclick="clearCart()" style="background: #ef4444; color: white; border: none; padding: 0.375rem 0.75rem; border-radius: 0.375rem; font-size: 0.875rem; cursor: pointer;" id="clearCartBtn" disabled>Clear Cart</button>
                </div>
                <div id="cartContainer" style="border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 1rem; min-height: 120px; background: #f9fafb; max-height: 200px; overflow-y: auto;">
                    <div style="text-align: center; color: #6b7280; font-style: italic; padding: 2rem;">Cart is empty - add products above</div>
                </div>
            </div>
            
            <!-- Customer Info -->
            <div style="margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Customer Name (Optional)</label>
                        <input type="text" id="customerInput" placeholder="Enter customer name" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;" oninput="customerName = this.value">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Notes (Optional)</label>
                        <input type="text" id="notesInput" placeholder="Sale notes" style="width: 100%; padding: 0.75rem; border: 2px solid #e5e7eb; border-radius: 0.5rem; font-size: 1rem;" oninput="notes = this.value">
                    </div>
                </div>
            </div>
            
            <!-- Total Display -->
            <div style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #10b981; border-radius: 0.5rem; padding: 1rem; text-align: center;">
                <div style="color: #065f46; font-size: 0.875rem; margin-bottom: 0.25rem;">Total Amount</div>
                <div id="totalDisplay" style="color: #059669; font-size: 1.75rem; font-weight: bold;">K0.00</div>
            </div>
        </div>
    `;
    
    // Show modal with POS interface
    window.modal.showModal(`
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">🛒 Point of Sale - New Sale</h3>
                <button type="button" class="modal-close" onclick="window.modal.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                ${posHTML}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-outline" onclick="window.modal.closeModal()">Cancel</button>
                <button type="button" class="btn-modal btn-modal-primary" id="processSaleBtn" disabled onclick="processSale()">Process Sale</button>
            </div>
        </div>
    `);
    
    // Initialize product selection handler
    document.getElementById('productSelect').addEventListener('change', function() {
        document.getElementById('addProductBtn').disabled = !this.value;
    });
    
    // Cart management functions (window scope for onclick handlers)
    window.addToCart = function() {
        const select = document.getElementById('productSelect');
        const productId = select.value;
        const option = select.options[select.selectedIndex];
        
        if (!productId || !option) return;
        
        const product = {
            id: productId,
            name: option.textContent.split(' - K')[0],
            price: parseFloat(option.dataset.price)
        };
        
        // Check if already in cart
        const existingItem = cart.find(item => item.id === productId);
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: productId,
                name: product.name,
                price: product.price,
                quantity: 1
            });
        }
        
        updateCartDisplay();
        select.value = '';
        document.getElementById('addProductBtn').disabled = true;
    };
    
    window.updateQuantity = function(productId, change) {
        const item = cart.find(item => item.id === productId);
        if (!item) return;
        
        item.quantity += change;
        if (item.quantity <= 0) {
            cart = cart.filter(item => item.id !== productId);
        }
        updateCartDisplay();
    };
    
    window.removeFromCart = function(productId) {
        cart = cart.filter(item => item.id !== productId);
        updateCartDisplay();
    };
    
    window.clearCart = function() {
        if (confirm('Clear all items from cart?')) {
            cart = [];
            updateCartDisplay();
        }
    };
    
    function updateCartDisplay() {
        const container = document.getElementById('cartContainer');
        const clearBtn = document.getElementById('clearCartBtn');
        const processBtn = document.getElementById('processSaleBtn');
        const totalDisplay = document.getElementById('totalDisplay');
        
        if (cart.length === 0) {
            container.innerHTML = '<div style="text-align: center; color: #6b7280; font-style: italic; padding: 2rem;">Cart is empty - add products above</div>';
            clearBtn.disabled = true;
            processBtn.disabled = true;
            totalDisplay.textContent = 'K0.00';
            return;
        }
        
        const cartHTML = cart.map(item => `
            <div style="display: flex; justify-content: space-between; align-items: center; background: white; padding: 1rem; border-radius: 0.5rem; margin-bottom: 0.5rem; border: 1px solid #e5e7eb;">
                <div style="flex: 1;">
                    <div style="font-weight: 600; color: #374151;">${item.name}</div>
                    <div style="font-size: 0.875rem; color: #6b7280;">K${item.price.toFixed(2)} each</div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <button onclick="updateQuantity('${item.id}', -1)" style="width: 28px; height: 28px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: 600;">−</button>
                    <span style="min-width: 30px; text-align: center; font-weight: 600;">${item.quantity}</span>
                    <button onclick="updateQuantity('${item.id}', 1)" style="width: 28px; height: 28px; border: 1px solid #d1d5db; background: white; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: 600;">+</button>
                    <button onclick="removeFromCart('${item.id}')" style="width: 28px; height: 28px; border: 1px solid #ef4444; background: #fef2f2; color: #ef4444; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; margin-left: 0.5rem;">×</button>
                </div>
                <div style="font-weight: bold; color: #059669; min-width: 80px; text-align: right;">K${(item.price * item.quantity).toFixed(2)}</div>
            </div>
        `).join('');
        
        container.innerHTML = cartHTML;
        
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        totalDisplay.textContent = `K${total.toFixed(2)}`;
        
        clearBtn.disabled = false;
        processBtn.disabled = false;
    }
    
    window.processSale = function() {
        if (cart.length === 0) return;
        
        const branch = document.getElementById('branchSelect').value;
        const customer = document.getElementById('customerInput').value;
        const saleNotes = document.getElementById('notesInput').value;
        
        // Process each cart item as separate sale
        let completedSales = 0;
        let failedSales = 0;
        const totalItems = cart.length;
        const errorMessages = [];
        
        const processBtn = document.getElementById('processSaleBtn');
        processBtn.disabled = true;
        processBtn.textContent = 'Processing...';
        
        console.log(`🛒 Processing ${totalItems} items from cart...`);
        
        cart.forEach((item, index) => {
            const formData = new FormData();
            formData.append('product_id', item.id);
            formData.append('quantity', item.quantity);
            formData.append('total', (item.price * item.quantity).toFixed(2));
            formData.append('branch', branch);
            formData.append('customer_name', customer);
            formData.append('notes', saleNotes);
            
            console.log(`📤 Processing item ${index + 1}/${totalItems}: ${item.name} x${item.quantity}`);
            
            fetch('add_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log(`📥 Response for ${item.name}:`, data);
                
                let success = false;
                let message = '';
                
                try {
                    const jsonResponse = JSON.parse(data);
                    success = jsonResponse.success;
                    message = jsonResponse.message || '';
                    
                    if (success) {
                        console.log(`✅ ${item.name} processed successfully`);
                        completedSales++;
                    } else {
                        console.log(`❌ ${item.name} failed: ${message}`);
                        failedSales++;
                        errorMessages.push(`${item.name}: ${message}`);
                    }
                } catch (e) {
                    // Handle non-JSON response
                    const dataLower = data.toLowerCase();
                    if (dataLower.includes('success') && !dataLower.includes('error') && !dataLower.includes('fail')) {
                        console.log(`✅ ${item.name} processed successfully (non-JSON response)`);
                        completedSales++;
                        success = true;
                    } else {
                        console.log(`❌ ${item.name} failed (non-JSON response): ${data.substring(0, 100)}...`);
                        failedSales++;
                        errorMessages.push(`${item.name}: Processing error`);
                    }
                }
                
                // Check if all items have been processed
                if (completedSales + failedSales === totalItems) {
                    console.log(`🏁 Processing complete: ${completedSales} successful, ${failedSales} failed`);
                    
                    window.modal.closeModal();
                    
                    if (failedSales > 0) {
                        const errorSummary = `${failedSales} of ${totalItems} sales failed:\n\n` + errorMessages.join('\n');
                        alert(errorSummary);
                    } else {
                        console.log('🎉 All sales processed successfully!');
                    }
                    
                    // Always reload to show updated data
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error(`❌ Network error for ${item.name}:`, error);
                failedSales++;
                errorMessages.push(`${item.name}: Network error`);
                
                // Check if all items have been processed (including errors)
                if (completedSales + failedSales === totalItems) {
                    console.log(`🏁 Processing complete with errors: ${completedSales} successful, ${failedSales} failed`);
                    
                    window.modal.closeModal();
                    
                    const errorSummary = `${failedSales} of ${totalItems} sales failed:\n\n` + errorMessages.join('\n');
                    alert(errorSummary);
                    
                    window.location.reload();
                }
            });
        });
    };
}

function showEditSaleModal(id, productId, quantity, branch, total, customerName = '', notes = '') {
    if (!window.modal) {
        setTimeout(() => showEditSaleModal(id, productId, quantity, branch, total, customerName, notes), 100);
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name'] . ' - K' . number_format($product['price'], 2)]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    const unitPrice = (total / quantity).toFixed(2);
    
    // Mark selected options
    const productsWithSelection = products.map(p => ({...p, selected: p.value == productId}));
    const branchesWithSelection = branches.map(b => ({...b, selected: b.value === branch}));
    
    // Calculate current total for display
    const currentTotal = (parseFloat(unitPrice) * parseInt(quantity)).toFixed(2);
    
    window.modal.showForm('✏️ Edit Sale', [
        {name: 'id', label: 'Sale ID', type: 'hidden', value: id},
        {
            name: 'product_id', 
            label: '🛍️ Product', 
            type: 'select', 
            required: true, 
            options: productsWithSelection,
            className: 'product-select'
        },
        {
            name: 'quantity', 
            label: '📦 Quantity', 
            type: 'number', 
            required: true, 
            value: quantity, 
            min: 1,
            onChange: 'updateEditTotal()',
            className: 'quantity-input'
        },
        {
            name: 'unit_price', 
            label: '💰 Unit Price (K)', 
            type: 'number', 
            required: true, 
            value: unitPrice, 
            step: '0.01', 
            min: 0,
            onChange: 'updateEditTotal()',
            className: 'price-input'
        },
        {
            name: 'branch', 
            label: '🏢 Branch', 
            type: 'select', 
            required: true, 
            options: branchesWithSelection,
            className: 'branch-select'
        },
        {
            name: 'customer_name', 
            label: '👤 Customer Name', 
            type: 'text', 
            value: customerName, 
            placeholder: 'Enter customer name (optional)',
            className: 'customer-input'
        },
        {
            name: 'notes', 
            label: '📝 Notes', 
            type: 'textarea', 
            value: notes, 
            placeholder: 'Add any additional notes about this sale...',
            className: 'notes-input'
        }
    ], {
        submitText: '💾 Update Sale',
        submitClass: 'btn-modal-success',
        cancelText: '❌ Cancel',
        cancelClass: 'btn-modal-secondary',
        extraHTML: `
            <div style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #10b981; border-radius: 12px; padding: 20px; margin: 20px 0; text-align: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);">
                <div style="color: #065f46; font-size: 14px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;">💵 Total Amount</div>
                <div id="editTotalDisplay" style="color: #059669; font-size: 32px; font-weight: 900; text-shadow: 0 2px 4px rgba(0,0,0,0.1); letter-spacing: -1px;">K${currentTotal}</div>
                <div style="color: #047857; font-size: 12px; margin-top: 6px; opacity: 0.8; font-style: italic;">Updates automatically as you change quantity or price</div>
            </div>
            
            <style>
                /* Enhanced Input Styling with Icons */
                .product-select { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16'/%3e%3c/svg%3e") !important; 
                    background-position: left 16px center !important;
                    padding-left: 48px !important;
                }
                
                .quantity-input { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'/%3e%3c/svg%3e") !important; 
                    background-position: left 16px center !important; 
                    padding-left: 48px !important; 
                }
                
                .price-input { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'/%3e%3c/svg%3e") !important; 
                    background-position: left 16px center !important; 
                    padding-left: 48px !important; 
                }
                
                .branch-select { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'/%3e%3c/svg%3e") !important;
                    background-position: left 16px center !important;
                    padding-left: 48px !important;
                }
                
                .customer-input { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'/%3e%3c/svg%3e") !important; 
                    background-position: left 16px center !important; 
                    padding-left: 48px !important; 
                }
                
                .notes-input { 
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23667eea'%3e%3cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'/%3e%3c/svg%3e") !important; 
                    background-position: left 16px top 20px !important; 
                    padding-left: 48px !important; 
                }
                
                /* Enhanced Button Styling */
                .modal-footer {
                    padding: 24px 32px !important;
                    background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
                    border-top: 1px solid rgba(102, 126, 234, 0.15) !important;
                    display: flex !important;
                    justify-content: flex-end !important;
                    align-items: center !important;
                    gap: 16px !important;
                    border-radius: 0 0 20px 20px !important;
                }
                
                .btn-modal {
                    padding: 16px 32px !important;
                    border-radius: 12px !important;
                    font-weight: 700 !important;
                    font-size: 15px !important;
                    min-width: 140px !important;
                    height: 48px !important;
                    display: inline-flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 8px !important;
                    border: none !important;
                    cursor: pointer !important;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    text-transform: uppercase !important;
                    letter-spacing: 0.5px !important;
                    position: relative !important;
                    overflow: hidden !important;
                }
                
                .btn-modal-success {
                    background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
                    color: white !important;
                    box-shadow: 0 20px 20px rgba(16, 185, 129, 0.4) !important;
                }
                
                .btn-modal-success:hover {
                    transform: translateY(-3px) !important;
                    box-shadow: 0 10px 30px rgba(16, 185, 129, 0.5) !important;
                    filter: brightness(1.1) !important;
                }
                
                .btn-modal-secondary {
                    background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important;
                    color: #475569 !important;
                    border: 2px solid rgba(102, 126, 234, 0.1) !important;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
                }
                
                .btn-modal-secondary:hover {
                    background: linear-gradient(135deg, #e2e8f0, #cbd5e1) !important;
                    color: #334155 !important;
                    transform: translateY(-2px) !important;
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12) !important;
                    border-color: rgba(102, 126, 234, 0.2) !important;
                }
                
                .btn-modal::before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: -100%;
                    width: 100%;
                    height: 100%;
                    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
                    transition: left 0.5s;
                }
                
                .btn-modal:hover::before {
                    left: 100%;
                }
                
                .btn-modal:active {
                    transform: translateY(0) !important;
                }
            </style>
        `
    }).then(data => {
        // Calculate total from unit price and quantity
        data.total = (parseFloat(data.unit_price || 0) * parseInt(data.quantity || 0)).toFixed(2);
        
        console.log('📝 Edit form data:', data);
        
        // Submit the form
        const formData = new FormData();
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }
        
        fetch('edit_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            console.log('📥 Edit response:', result);
            
            // Try to parse JSON response first
            try {
                const jsonResponse = JSON.parse(result);
                if (jsonResponse.success) {
                    console.log('✅ Sale updated successfully');
                    window.location.reload();
                } else {
                    alert('❌ Error updating sale: ' + jsonResponse.message);
                }
            } catch (e) {
                // Not JSON, check for success keywords in text
                const resultLower = result.toLowerCase();
                if (resultLower.includes('success') || resultLower.includes('updated')) {
                    console.log('✅ Sale updated successfully (non-JSON response)');
                    window.location.reload();
                } else if (resultLower.includes('error') || resultLower.includes('fail')) {
                    alert('❌ Error updating sale: ' + result);
                } else {
                    // Assume success if no clear error
                    console.log('✅ Assuming success, reloading...');
                    window.location.reload();
                }
            }
        })
        .catch(error => {
            console.error('❌ Network error:', error);
            alert('❌ Error updating sale. Please try again.');
        });
    }).catch(() => {
        console.log('❌ Edit modal cancelled');
    });
    
    // Add dynamic total calculation
    window.updateEditTotal = function() {
        setTimeout(() => {
            const quantityInput = document.querySelector('input[name="quantity"]');
            const priceInput = document.querySelector('input[name="unit_price"]');
            const totalDisplay = document.getElementById('editTotalDisplay');
            
            if (quantityInput && priceInput && totalDisplay) {
                const qty = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const total = (qty * price).toFixed(2);
                totalDisplay.textContent = `K${total}`;
                
                // Add animation effect
                totalDisplay.style.transform = 'scale(1.1)';
                totalDisplay.style.color = '#059669';
                setTimeout(() => {
                    totalDisplay.style.transform = 'scale(1)';
                    totalDisplay.style.color = '#059669';
                }, 200);
            }
        }, 50);
    };
}

function showDeleteSaleModal(id, name) {
    if (!window.modal) {
        setTimeout(() => showDeleteSaleModal(id, name), 100);
        return;
    }
    
    window.modal.confirm(
        `
        <div style="text-align: center; padding: 30px 20px;">
            <div style="background: linear-gradient(135deg, #fee2e2, #fef2f2); border-radius: 50%; width: 100px; height: 100px; margin: 0 auto 30px; display: flex; align-items: center; justify-content: center; border: 4px solid #fca5a5; box-shadow: 0 8px 25px rgba(252, 165, 165, 0.3);">
                <span style="font-size: 44px;">🗑️</span>
            </div>
            <h3 style="color: #dc2626; margin: 0 0 16px 0; font-size: 28px; font-weight: 800; letter-spacing: -0.5px;">Delete Sale Record</h3>
            <p style="color: #6b7280; margin: 0 0 30px 0; line-height: 1.7; font-size: 16px;">Are you sure you want to permanently delete this sale?</p>
            
            <div style="background: linear-gradient(135deg, #f9fafb, #f3f4f6); padding: 20px; border-radius: 16px; margin: 30px 0; border-left: 6px solid #ef4444; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                <div style="font-size: 14px; color: #6b7280; margin-bottom: 6px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">📊 Sale Details:</div>
                <div style="font-weight: 800; color: #374151; font-size: 18px; line-height: 1.4;">${name}</div>
            </div>
            
            <div style="background: linear-gradient(135deg, #fef2f2, #fecaca); padding: 16px; border-radius: 12px; border: 2px solid #fca5a5; margin-top: 20px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);">
                <div style="color: #dc2626; font-size: 14px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 10px;">
                    <span style="font-size: 18px;">⚠️</span>
                    <span>This action cannot be undone</span>
                </div>
            </div>
        </div>
        
        <style>
            .modal-footer {
                padding: 10px !important;
                background: linear-gradient(135deg, #f8fafc, #f1f5f9) !important;
                border-top: 1px solid rgba(239, 68, 68, 0.1) !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 10px !important;
                border-radius: 0 0 24px 24px !important;
            }
            
            .btn-modal {
                padding: 10px 10px !important;
                border-radius: 14px !important;
                font-weight: 600 !important;
                font-size: 15px !important;
                min-width: 140px !important;
                height: 40px !important;
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 10px !important;
                border: none !important;
                cursor: pointer !important;
                transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1) !important;
                text-transform: uppercase !important;
                letter-spacing: 0.8px !important;
                position: relative !important;
                overflow: hidden !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15) !important;
            }
            
            .btn-modal-danger {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
                color: white !important;
                box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
            }
            
            .btn-modal-danger:hover {
                transform: translateY(-4px) scale(1.02) !important;
                box-shadow: 0 12px 35px rgba(239, 68, 68, 0.5) !important;
                filter: brightness(1.1) saturate(1.1) !important;
            }
            
            .btn-modal-secondary {
                background: linear-gradient(135deg, #f1f5f9, #e2e8f0) !important;
                color: #475569 !important;
                border: 2px solid rgba(71, 85, 105, 0.2) !important;
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1) !important;
            }
            
            .btn-modal-secondary:hover {
                background: linear-gradient(135deg, #e2e8f0, #cbd5e1) !important;
                color: #334155 !important;
                transform: translateY(-3px) scale(1.02) !important;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
                border-color: rgba(71, 85, 105, 0.3) !important;
            }
            
            .btn-modal::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
                transition: left 0.6s;
            }
            
            .btn-modal:hover::before {
                left: 100%;
            }
            
            .btn-modal:active {
                transform: translateY(-1px) scale(0.98) !important;
            }
            
            .btn-modal:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
            }
        </style>
        `,
        null,
        {
            title: '🗑️ Confirm Deletion',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            confirmClass: 'btn-modal-danger',
            cancelClass: 'btn-modal-secondary',
            confirmIcon: '🗑️',
        }
    ).then(confirmed => {
        if (confirmed) {
            console.log(`🗑️ Deleting sale #${id}: ${name}`);
            
            const formData = new FormData();
            formData.append('id', id);
            
            // Show loading state
            const deleteButtons = document.querySelectorAll('.btn-modal-danger');
            deleteButtons.forEach(btn => {
                btn.textContent = '🔄 Deleting...';
                btn.disabled = true;
            });
            
            fetch('delete_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📥 Delete response received');
                // Delete handler redirects, so any response means it processed
                window.location.reload();
            })
            .catch(error => {
                console.error('❌ Delete error:', error);
                // Even on "error" (redirect), reload the page
                window.location.reload();
            });
        } else {
            console.log('❌ Delete cancelled');
        }
    }).catch(() => {
        console.log('❌ Delete modal cancelled');
    });
}
</script>

<?php $conn->close(); ?>
