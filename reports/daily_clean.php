<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';

// Get database connection
$conn = getDbConnection();

// Define available branches (based on your existing data structure)
$branches = [
    ['id' => 'Livingstone', 'name' => 'Livingstone'],
    ['id' => 'Chisamba', 'name' => 'Chisamba'],
    ['id' => 'Lusaka', 'name' => 'Lusaka']
];

// Get selected date and branch
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_branch = $_GET['branch'] ?? '';

// Initialize data arrays
$opening_stock = [];
$purchases = [];
$sales = [];
$transfers = [];
$expenses = [];
$closing_stock = [];
$metrics = [
    'total_sales' => 0,
    'total_expenses' => 0,
    'net_cash' => 0,
    'top_product' => 'N/A',
    'low_stock_count' => 0
];

if ($selected_branch && $selected_date) {
    // Calculate previous day for opening stock
    $previous_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
    
    // Get opening stock (previous day's closing stock)
    $opening_query = "
        SELECT p.name as product_name, p.price, 
               COALESCE(
                   (SELECT SUM(quantity) FROM purchases WHERE product_id = p.id AND branch = ? AND DATE(purchase_date) <= ?),0
               ) +
               COALESCE(
                   (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND dest_branch = ? AND DATE(transfer_date) <= ?),0
               ) -
               COALESCE(
                   (SELECT SUM(quantity) FROM sales WHERE product_id = p.id AND branch = ? AND DATE(sale_date) <= ?),0
               ) -
               COALESCE(
                   (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND source_branch = ? AND DATE(transfer_date) <= ?),0
               ) as opening_stock
        FROM products p
        WHERE FIND_IN_SET(?, REPLACE(p.branches, ' ', '')) > 0
        HAVING opening_stock > 0
        ORDER BY p.name
    ";
    $stmt = $conn->prepare($opening_query);
    $stmt->bind_param("sssssssss", $selected_branch, $previous_date, $selected_branch, $previous_date, 
                      $selected_branch, $previous_date, $selected_branch, $previous_date, $selected_branch);
    $stmt->execute();
    $opening_result = $stmt->get_result();
    while ($row = $opening_result->fetch_assoc()) {
        $row['stock_value'] = $row['opening_stock'] * $row['price'];
        $opening_stock[] = $row;
    }
    
    // Get purchases for the day
    $purchases_query = "
        SELECT p.name as product_name, pur.quantity, pur.damages, 
               (pur.quantity - COALESCE(pur.damages, 0)) as qty_received,
               pur.purchase_date, (pur.quantity * p.price) as total_amount
        FROM purchases pur
        JOIN products p ON pur.product_id = p.id
        WHERE pur.branch = ? AND DATE(pur.purchase_date) = ?
        ORDER BY pur.purchase_date DESC
    ";
    $stmt = $conn->prepare($purchases_query);
    $stmt->bind_param("ss", $selected_branch, $selected_date);
    $stmt->execute();
    $purchases_result = $stmt->get_result();
    while ($row = $purchases_result->fetch_assoc()) {
        $purchases[] = $row;
    }
    
    // Get sales for the day
    $sales_query = "
        SELECT TIME(s.sale_date) as sale_time, p.name as product_name, 
               s.quantity, (s.total / s.quantity) as unit_price, s.total as total_amount, 
               'Walk-in Customer' as customer_name
        FROM sales s
        JOIN products p ON s.product_id = p.id
        WHERE s.branch = ? AND DATE(s.sale_date) = ?
        ORDER BY s.sale_date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param("ss", $selected_branch, $selected_date);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    while ($row = $sales_result->fetch_assoc()) {
        $sales[] = $row;
        $metrics['total_sales'] += $row['total_amount'];
    }
    
    // Get transfers for the day
    $transfers_query = "
        SELECT p.name as product_name, t.quantity, t.transfer_date,
               CASE 
                   WHEN t.source_branch = ? THEN 'Outgoing'
                   ELSE 'Incoming'
               END as transfer_type,
               CASE 
                   WHEN t.source_branch = ? THEN t.dest_branch
                   ELSE t.source_branch
               END as other_branch
        FROM transfers t
        JOIN products p ON t.product_id = p.id
        WHERE (t.source_branch = ? OR t.dest_branch = ?) AND DATE(t.transfer_date) = ?
        ORDER BY t.transfer_date DESC
    ";
    $stmt = $conn->prepare($transfers_query);
    $stmt->bind_param("sssss", $selected_branch, $selected_branch, $selected_branch, $selected_branch, $selected_date);
    $stmt->execute();
    $transfers_result = $stmt->get_result();
    while ($row = $transfers_result->fetch_assoc()) {
        $transfers[] = $row;
    }
    
    // Get expenses for the day
    $expenses_query = "
        SELECT category, description, amount
        FROM expenses
        WHERE branch = ? AND DATE(expense_date) = ?
        ORDER BY expense_date DESC
    ";
    $stmt = $conn->prepare($expenses_query);
    $stmt->bind_param("ss", $selected_branch, $selected_date);
    $stmt->execute();
    $expenses_result = $stmt->get_result();
    while ($row = $expenses_result->fetch_assoc()) {
        $expenses[] = $row;
        $metrics['total_expenses'] += $row['amount'];
    }
    
    // Calculate net cash
    $metrics['net_cash'] = $metrics['total_sales'] - $metrics['total_expenses'];
    
    // Get top selling product
    $top_product_query = "
        SELECT p.name, SUM(s.quantity) as total_qty
        FROM sales s
        JOIN products p ON s.product_id = p.id
        WHERE s.branch = ? AND DATE(s.sale_date) = ?
        GROUP BY s.product_id
        ORDER BY total_qty DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($top_product_query);
    $stmt->bind_param("ss", $selected_branch, $selected_date);
    $stmt->execute();
    $top_result = $stmt->get_result();
    if ($top_row = $top_result->fetch_assoc()) {
        $metrics['top_product'] = $top_row['name'] . ' (' . $top_row['total_qty'] . ')';
    }
    
    // Calculate closing stock and low stock alerts
    $closing_query = "
        SELECT p.name as product_name, p.price, 50 as min_stock,
               COALESCE(
                   (SELECT SUM(quantity) FROM purchases WHERE product_id = p.id AND branch = ? AND DATE(purchase_date) <= ?),0
               ) +
               COALESCE(
                   (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND dest_branch = ? AND DATE(transfer_date) <= ?),0
               ) -
               COALESCE(
                   (SELECT SUM(quantity) FROM sales WHERE product_id = p.id AND branch = ? AND DATE(sale_date) <= ?),0
               ) -
               COALESCE(
                   (SELECT SUM(quantity) FROM transfers WHERE product_id = p.id AND source_branch = ? AND DATE(transfer_date) <= ?),0
               ) as closing_stock
        FROM products p
        WHERE FIND_IN_SET(?, REPLACE(p.branches, ' ', '')) > 0
        HAVING closing_stock >= 0
        ORDER BY p.name
    ";
    $stmt = $conn->prepare($closing_query);
    $stmt->bind_param("sssssssss", $selected_branch, $selected_date, $selected_branch, $selected_date, 
                      $selected_branch, $selected_date, $selected_branch, $selected_date, $selected_branch);
    $stmt->execute();
    $closing_result = $stmt->get_result();
    while ($row = $closing_result->fetch_assoc()) {
        $row['stock_value'] = $row['closing_stock'] * $row['price'];
        $closing_stock[] = $row;
        
        // Check for low stock
        if ($row['closing_stock'] <= $row['min_stock']) {
            $metrics['low_stock_count']++;
        }
    }
}

include '../includes/header.php';
$currentPage = 'daily-report';
?>

<style>
    body {
        background: #f8fafc;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .page-container {
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
        border-left: 4px solid #10b981;
    }
    
    .page-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #111827;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .page-subtitle {
        color: #6b7280;
        font-size: 1rem;
        margin-bottom: 2rem;
    }
    
    .controls-section {
        display: flex;
        gap: 1rem;
        align-items: end;
        flex-wrap: wrap;
    }
    
    .control-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 200px;
    }
    
    .control-label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .control-input {
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.875rem;
        background: white;
        transition: border-color 0.2s;
    }
    
    .control-input:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .btn-primary {
        background: #10b981;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary:hover {
        background: #059669;
        transform: translateY(-1px);
    }
    
    .btn-pdf {
        background: #dc2626;
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: 1rem;
    }
    
    .btn-pdf:hover {
        background: #b91c1c;
        transform: translateY(-1px);
    }
    
    .performance-section {
        margin-bottom: 2rem;
    }
    
    .performance-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
    }
    
    .performance-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .time-filters {
        display: flex;
        gap: 0.5rem;
        background: #f3f4f6;
        padding: 0.25rem;
        border-radius: 8px;
    }
    
    .time-filter {
        padding: 0.5rem 1rem;
        border: none;
        background: transparent;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .time-filter.active {
        background: white;
        color: #10b981;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .time-filter:hover:not(.active) {
        color: #374151;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .metric-card {
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        position: relative;
        overflow: hidden;
        color: white;
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }
    
    .metric-card.sales { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .metric-card.revenue { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    .metric-card.quantity { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    .metric-card.expenses { 
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }
    .metric-card.net-cash { 
        background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        color: #333;
    }
    
    .metric-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    
    .metric-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }
    
    .metric-value {
        font-size: 2.25rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .metric-label {
        font-size: 0.875rem;
        font-weight: 500;
        opacity: 0.9;
    }
    
    .content-sections {
        display: grid;
        gap: 2rem;
    }
    
    .section-card {
        background: white;
        padding: 1.5rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .section-header {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .section-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        background: #f9fafb;
        padding: 0.75rem;
        text-align: left;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .data-table td {
        padding: 0.75rem;
        font-size: 0.875rem;
        color: #111827;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .data-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .amount {
        font-weight: 600;
        color: #059669;
    }
    
    .expense-amount {
        font-weight: 600;
        color: #dc2626;
    }
    
    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6b7280;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .page-container {
            padding: 1rem;
        }
        
        .controls-section {
            flex-direction: column;
            align-items: stretch;
        }
        
        .control-group {
            min-width: auto;
        }
        
        .time-filters {
            flex-wrap: wrap;
        }
        
        .metrics-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">
            📊 Daily Report
        </h1>
        <p class="page-subtitle">Comprehensive daily business analytics and performance tracking</p>
        
        <form method="GET" class="controls-section">
            <div class="control-group">
                <label class="control-label">📅 Report Date</label>
                <input type="date" name="date" class="control-input" value="<?php echo htmlspecialchars($selected_date); ?>" required>
            </div>
            <div class="control-group">
                <label class="control-label">🏢 Branch</label>
                <select name="branch" class="control-input" required>
                    <option value="">Select Branch</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php echo $selected_branch == $branch['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary">
                🔍 Generate Report
            </button>
            <?php if ($selected_branch && $selected_date): ?>
            <button type="button" onclick="exportToPDF()" class="btn-pdf">
                📄 Export PDF
            </button>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($selected_branch && $selected_date): ?>
        <!-- Performance Section -->
        <div class="performance-section">
            <div class="performance-header">
                <h3 class="performance-title">
                    📈 Business Performance
                </h3>
                <div class="time-filters">
                    <button type="button" class="time-filter">Day</button>
                    <button type="button" class="time-filter active">Week</button>
                    <button type="button" class="time-filter">Month</button>
                    <button type="button" class="time-filter">Quarter</button>
                    <button type="button" class="time-filter">Year</button>
                </div>
            </div>
            
            <div class="metrics-grid">
                <div class="metric-card sales">
                    <div class="metric-header">
                        <div class="metric-icon">📊</div>
                    </div>
                    <div class="metric-value"><?php echo count($sales); ?></div>
                    <div class="metric-label">This Week's Sales</div>
                </div>
                
                <div class="metric-card revenue">
                    <div class="metric-header">
                        <div class="metric-icon">💰</div>
                    </div>
                    <div class="metric-value">K<?php echo number_format($metrics['total_sales'], 2); ?></div>
                    <div class="metric-label">This Week's Revenue</div>
                </div>
                
                <div class="metric-card quantity">
                    <div class="metric-header">
                        <div class="metric-icon">📦</div>
                    </div>
                    <div class="metric-value"><?php 
                        $total_qty = 0;
                        foreach($sales as $sale) {
                            $total_qty += $sale['quantity'];
                        }
                        echo number_format($total_qty);
                    ?> items</div>
                    <div class="metric-label">This Week's Quantity</div>
                </div>
                
                <div class="metric-card expenses">
                    <div class="metric-header">
                        <div class="metric-icon">💸</div>
                    </div>
                    <div class="metric-value">K<?php echo number_format($metrics['total_expenses'], 2); ?></div>
                    <div class="metric-label">Today's Expenses</div>
                </div>
                
                <div class="metric-card net-cash">
                    <div class="metric-header">
                        <div class="metric-icon">💵</div>
                    </div>
                    <div class="metric-value">K<?php echo number_format($metrics['net_cash'], 2); ?></div>
                    <div class="metric-label">Net Cash Flow</div>
                </div>
            </div>
        </div>

        <!-- Content Sections -->
        <div class="content-sections">
            <!-- Opening Stock -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">📦 Opening Stock (Start of Day)</h3>
                </div>
                <?php if (!empty($opening_stock)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Opening Stock</th>
                                <th>Unit Price</th>
                                <th>Stock Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($opening_stock as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo number_format($item['opening_stock']); ?></td>
                                    <td class="amount">K<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="amount">K<?php echo number_format($item['stock_value'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <p>No opening stock data available for this date.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sales -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">🛒 Sales Transactions</h3>
                </div>
                <?php if (!empty($sales)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Product</th>
                                <th>Qty Sold</th>
                                <th>Unit Price</th>
                                <th>Total</th>
                                <th>Customer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($sale['sale_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                                    <td><?php echo number_format($sale['quantity']); ?></td>
                                    <td class="amount">K<?php echo number_format($sale['unit_price'], 2); ?></td>
                                    <td class="amount">K<?php echo number_format($sale['total_amount'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🛒</div>
                        <p>No sales recorded for this date.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Expenses -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">💸 Daily Expenses</h3>
                </div>
                <?php if (!empty($expenses)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td class="expense-amount">K<?php echo number_format($expense['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💸</div>
                        <p>No expenses recorded for this date.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Closing Stock -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">📋 Closing Stock (End of Day)</h3>
                </div>
                <?php if (!empty($closing_stock)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Closing Stock</th>
                                <th>Unit Price</th>
                                <th>Stock Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($closing_stock as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td><?php echo number_format($item['closing_stock']); ?></td>
                                    <td class="amount">K<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="amount">K<?php echo number_format($item['stock_value'], 2); ?></td>
                                    <td>
                                        <?php if ($item['closing_stock'] <= $item['min_stock']): ?>
                                            <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500;">⚠️ Low Stock</span>
                                        <?php else: ?>
                                            <span style="background: #dcfce7; color: #059669; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500;">✓ Good</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <p>No closing stock data available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <h3>Select Date and Branch</h3>
            <p>Please select a date and branch to generate your comprehensive daily report.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Time filter functionality
document.querySelectorAll('.time-filter').forEach(button => {
    button.addEventListener('click', function() {
        document.querySelectorAll('.time-filter').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>

<!-- PDF Generation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// PDF Export Function
function exportToPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Get report data
    const branchSelect = document.querySelector('select[name="branch"]');
    const branch = branchSelect.options[branchSelect.selectedIndex].text;
    const date = document.querySelector('input[name="date"]').value;
    const formattedDate = new Date(date).toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Header Section with Green Background
    doc.setFillColor(16, 185, 129);
    doc.rect(0, 0, 210, 35, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont(undefined, 'bold');
    doc.text('ØUÈ Daily Business Report', 20, 20);
    
    doc.setFontSize(12);
    doc.setFont(undefined, 'normal');
    doc.text('Comprehensive business analytics and performance tracking', 20, 28);
    
    // Report Info Section
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.text('Report Information', 20, 50);
    
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    doc.text(`Branch: ${branch}`, 20, 60);
    doc.text(`Period: Selected Day`, 20, 68);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 20, 76);
    
    let yPos = 90;
    
    // Performance Metrics Section
    doc.setFontSize(16);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(16, 185, 129);
    doc.text('ØUÈ Performance Metrics', 20, yPos);
    
    // Extract metrics from the metric cards
    const metricCards = document.querySelectorAll('.metric-card');
    const metricsData = [];
    
    metricCards.forEach(card => {
        const value = card.querySelector('.metric-value')?.textContent.trim() || 'N/A';
        const label = card.querySelector('.metric-label')?.textContent.trim() || '';
        if (label) {
            metricsData.push([label, value]);
        }
    });
    
    // Create metrics table
    if (metricsData.length > 0) {
        doc.autoTable({
            startY: yPos + 10,
            head: [['Metric', 'Value']],
            body: metricsData,
            theme: 'grid',
            headStyles: {
                fillColor: [16, 185, 129],
                textColor: [255, 255, 255],
                fontStyle: 'bold'
            },
            styles: {
                fontSize: 10,
                cellPadding: 6
            },
            columnStyles: {
                0: { cellWidth: 100 },
                1: { cellWidth: 60, halign: 'right', fontStyle: 'bold' }
            }
        });
        yPos = doc.lastAutoTable.finalY + 20;
    }
    
    // Opening Stock Section
    const openingStockTable = document.querySelector('.section-card:nth-of-type(1) .data-table');
    if (openingStockTable && openingStockTable.rows.length > 1) {
        // Check if we need a new page
        if (yPos > 220) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(16, 185, 129);
        doc.text('📦 Opening Stock (Start of Day)', 20, yPos);
        
        const openingData = [];
        const openingRows = openingStockTable.querySelectorAll('tbody tr');
        
        openingRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                openingData.push([
                    cells[0]?.textContent.trim() || '',
                    cells[1]?.textContent.trim() || '',
                    cells[2]?.textContent.trim() || '',
                    cells[3]?.textContent.trim() || ''
                ]);
            }
        });
        
        if (openingData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Product', 'Opening Stock', 'Unit Price', 'Stock Value']],
                body: openingData,
                theme: 'striped',
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                columnStyles: {
                    0: { cellWidth: 70 },
                    1: { cellWidth: 35, halign: 'center' },
                    2: { cellWidth: 35, halign: 'right' },
                    3: { cellWidth: 40, halign: 'right', fontStyle: 'bold' }
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Sales Transactions Section
    const salesTable = document.querySelector('.section-card:nth-of-type(2) .data-table');
    if (salesTable && salesTable.rows.length > 1) {
        // Check if we need a new page
        if (yPos > 200) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(16, 185, 129);
        doc.text('🛒 Sales Transactions', 20, yPos);
        
        const salesData = [];
        const salesRows = salesTable.querySelectorAll('tbody tr');
        
        salesRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                salesData.push([
                    cells[0]?.textContent.trim() || '',
                    cells[1]?.textContent.trim() || '',
                    cells[2]?.textContent.trim() || '',
                    cells[3]?.textContent.trim() || '',
                    cells[4]?.textContent.trim() || '',
                    cells[5]?.textContent.trim() || ''
                ]);
            }
        });
        
        if (salesData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Time', 'Product', 'Qty Sold', 'Unit Price', 'Total', 'Customer']],
                body: salesData,
                theme: 'striped',
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 8,
                    cellPadding: 3
                },
                columnStyles: {
                    0: { cellWidth: 20 },
                    1: { cellWidth: 50 },
                    2: { cellWidth: 20, halign: 'center' },
                    3: { cellWidth: 25, halign: 'right' },
                    4: { cellWidth: 25, halign: 'right', fontStyle: 'bold' },
                    5: { cellWidth: 40 }
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Expenses Section
    const expensesTable = document.querySelector('.section-card:nth-of-type(3) .data-table');
    if (expensesTable && expensesTable.rows.length > 1) {
        // Check if we need a new page
        if (yPos > 200) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(16, 185, 129);
        doc.text('💸 Daily Expenses', 20, yPos);
        
        const expensesData = [];
        const expenseRows = expensesTable.querySelectorAll('tbody tr');
        
        expenseRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 3) {
                expensesData.push([
                    cells[0]?.textContent.trim() || '',
                    cells[1]?.textContent.trim() || '',
                    cells[2]?.textContent.trim() || ''
                ]);
            }
        });
        
        if (expensesData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Category', 'Description', 'Amount']],
                body: expensesData,
                theme: 'striped',
                headStyles: {
                    fillColor: [250, 112, 154],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4
                },
                columnStyles: {
                    0: { cellWidth: 40 },
                    1: { cellWidth: 80 },
                    2: { cellWidth: 35, halign: 'right', fontStyle: 'bold' }
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Closing Stock Section
    const closingStockTable = document.querySelector('.section-card:nth-of-type(4) .data-table');
    if (closingStockTable && closingStockTable.rows.length > 1) {
        // Check if we need a new page
        if (yPos > 180) {
            doc.addPage();
            yPos = 20;
        }
        
        doc.setFontSize(14);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(16, 185, 129);
        doc.text('📋 Closing Stock (End of Day)', 20, yPos);
        
        const closingData = [];
        const closingRows = closingStockTable.querySelectorAll('tbody tr');
        let rowCount = 0;
        
        closingRows.forEach(row => {
            if (rowCount < 15) { // Limit to prevent page overflow
                const cells = row.querySelectorAll('td');
                if (cells.length >= 5) {
                    closingData.push([
                        cells[0]?.textContent.trim() || '',
                        cells[1]?.textContent.trim() || '',
                        cells[2]?.textContent.trim() || '',
                        cells[3]?.textContent.trim() || '',
                        cells[4]?.textContent.trim() || ''
                    ]);
                    rowCount++;
                }
            }
        });
        
        if (closingData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Product', 'Closing Stock', 'Unit Price', 'Stock Value', 'Status']],
                body: closingData,
                theme: 'striped',
                headStyles: {
                    fillColor: [79, 172, 254],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold'
                },
                styles: {
                    fontSize: 8,
                    cellPadding: 3
                },
                columnStyles: {
                    0: { cellWidth: 60 },
                    1: { cellWidth: 25, halign: 'center' },
                    2: { cellWidth: 25, halign: 'right' },
                    3: { cellWidth: 30, halign: 'right' },
                    4: { cellWidth: 30, halign: 'center' }
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Executive Summary Section
    const currentPageCount = doc.internal.getNumberOfPages();
    const finalY = doc.lastAutoTable ? doc.lastAutoTable.finalY : yPos;
    
    if (finalY < 220) {
        doc.setFontSize(16);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(16, 185, 129);
        doc.text('ØUÈ Executive Summary', 20, finalY + 20);
        
        // Extract summary data
        let totalSales = 0;
        let totalExpenses = 0;
        let netCash = 0;
        
        metricCards.forEach(card => {
            const label = card.querySelector('.metric-label')?.textContent.toLowerCase() || '';
            const valueText = card.querySelector('.metric-value')?.textContent || '0';
            const value = parseFloat(valueText.replace(/[K,]/g, '')) || 0;
            
            if (label.includes('revenue')) {
                totalSales = value;
            } else if (label.includes('expenses')) {
                totalExpenses = value;
            } else if (label.includes('net cash')) {
                netCash = value;
            }
        });
        
        doc.setFontSize(10);
        doc.setFont(undefined, 'normal');
        doc.setTextColor(0, 0, 0);
        
        const summaryText = [
            `This report covers selected day for ${branch} branch.`,
            `Total Revenue: K${totalSales.toFixed(2)}`,
            `Total Expenses: K${totalExpenses.toFixed(2)}`,
            `Net Cash Flow: K${netCash.toFixed(2)}`,
            `Performance Status: ${netCash >= 0 ? 'Profitable' : 'Loss'}`
        ];
        
        summaryText.forEach((text, index) => {
            doc.text(text, 20, finalY + 35 + (index * 8));
        });
    }
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(107, 114, 128);
        doc.text(`Page ${i} of ${pageCount}`, 170, 285);
        doc.text(`Generated by Sales Management System - ${new Date().toLocaleDateString()}`, 20, 285);
    }
    
    // Save the PDF
    const fileName = `OUE_Daily_Report_${branch}_${new Date(date).toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);
    
    // Show success message
    alert('PDF report generated successfully!');
}
</script>

<?php include '../includes/footer.php'; ?>
