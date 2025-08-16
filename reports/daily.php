<?php
$currentPage = 'daily-report';
session_start();
require_once '../includes/auth.php';
require_once '../includes/header.php';
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
$selected_period = $_GET['period'] ?? 'day'; // Default to 'day'

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

// Helper function to get date range based on period
function getPeriodRange($date, $period) {
    $start = $end = $date;
    switch (strtolower($period)) {
        case 'week':
            $start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            $end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
            break;
        case 'month':
            $start = date('Y-m-01', strtotime($date));
            $end = date('Y-m-t', strtotime($date));
            break;
        case 'quarter':
            $month = (int)date('m', strtotime($date));
            $year = (int)date('Y', strtotime($date));
            $quarter = ceil($month / 3);
            $start = date('Y-m-d', strtotime("$year-" . (($quarter - 1) * 3 + 1) . "-01"));
            $end = date('Y-m-d', strtotime("$start +2 months"));
            $end = date('Y-m-t', strtotime($end));
            break;
        case 'year':
            $start = date('Y-01-01', strtotime($date));
            $end = date('Y-12-31', strtotime($date));
            break;
        default:
            // 'day'
            $start = $end = $date;
    }
    return [$start, $end];
}

if ($selected_branch && $selected_date) {
    list($period_start, $period_end) = getPeriodRange($selected_date, $selected_period);
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
    
    // Get purchases for the selected period
    $purchases_query = "
        SELECT p.name as product_name, pur.quantity, pur.damages, 
               (pur.quantity - COALESCE(pur.damages, 0)) as qty_received,
               pur.purchase_date, (pur.quantity * p.price) as total_amount
        FROM purchases pur
        JOIN products p ON pur.product_id = p.id
        WHERE pur.branch = ? AND DATE(pur.purchase_date) BETWEEN ? AND ?
        ORDER BY pur.purchase_date DESC
    ";
    $stmt = $conn->prepare($purchases_query);
    $stmt->bind_param("sss", $selected_branch, $period_start, $period_end);
    $stmt->execute();
    $purchases_result = $stmt->get_result();
    while ($row = $purchases_result->fetch_assoc()) {
        $purchases[] = $row;
    }

    // Get sales for the selected period
    $sales_query = "
        SELECT TIME(s.sale_date) as sale_time, p.name as product_name, 
               s.quantity, (s.total / s.quantity) as unit_price, s.total as total_amount, 
               'Walk-in Customer' as customer_name
        FROM sales s
        JOIN products p ON s.product_id = p.id
        WHERE s.branch = ? AND DATE(s.sale_date) BETWEEN ? AND ?
        ORDER BY s.sale_date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param("sss", $selected_branch, $period_start, $period_end);
    $stmt->execute();
    $sales_result = $stmt->get_result();
    while ($row = $sales_result->fetch_assoc()) {
        $sales[] = $row;
        $metrics['total_sales'] += $row['total_amount'];
    }

    // Get transfers for the selected period
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
        WHERE (t.source_branch = ? OR t.dest_branch = ?) AND DATE(t.transfer_date) BETWEEN ? AND ?
        ORDER BY t.transfer_date DESC
    ";
    $stmt = $conn->prepare($transfers_query);
    $stmt->bind_param("ssssss", $selected_branch, $selected_branch, $selected_branch, $selected_branch, $period_start, $period_end);
    $stmt->execute();
    $transfers_result = $stmt->get_result();
    while ($row = $transfers_result->fetch_assoc()) {
        $transfers[] = $row;
    }

    // Get expenses for the selected period
    $expenses_query = "
        SELECT category, description, amount
        FROM expenses
        WHERE branch = ? AND DATE(expense_date) BETWEEN ? AND ?
        ORDER BY expense_date DESC
    ";
    $stmt = $conn->prepare($expenses_query);
    $stmt->bind_param("sss", $selected_branch, $period_start, $period_end);
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
        WHERE s.branch = ? AND DATE(s.sale_date) BETWEEN ? AND ?
        GROUP BY s.product_id
        ORDER BY total_qty DESC
        LIMIT 1
    ";
    $stmt = $conn->prepare($top_product_query);
    $stmt->bind_param("sss", $selected_branch, $period_start, $period_end);
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
        table-layout: fixed;
    }
    
    .data-table th {
        background: #f9fafb;
        padding: 0.75rem;
        text-align: left !important;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
    }
    
    .data-table td {
        padding: 0.75rem;
        font-size: 0.875rem;
        color: #111827;
        border-bottom: 1px solid #f3f4f6;
        text-align: left !important;
        vertical-align: top;
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
                    <?php
                    $periods = [
                        'day' => 'Day',
                        'week' => 'Week',
                        'month' => 'Month',
                        'quarter' => 'Quarter',
                        'year' => 'Year'
                    ];
                    foreach ($periods as $key => $label):
                    ?>
                        <button type="button" class="time-filter<?php echo ($selected_period === $key) ? ' active' : ''; ?>" data-period="<?php echo $key; ?>"><?php echo $label; ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php
            // Map period to label
            $periodLabels = [
                'day' => "Today's",
                'week' => "This Week's",
                'month' => "This Month's",
                'quarter' => "This Quarter's",
                'year' => "This Year's"
            ];
            $periodLabel = $periodLabels[$selected_period] ?? "This Period's";
            ?>
            <div class="metrics-grid">
                <div class="metric-card sales">
                    <div class="metric-header">
                        <div class="metric-icon">📊</div>
                    </div>
                    <div class="metric-value"><?php echo count($sales); ?></div>
                    <div class="metric-label"><?php echo $periodLabel; ?> Sales</div>
                </div>

                <div class="metric-card revenue">
                    <div class="metric-header">
                        <div class="metric-icon">💰</div>
                    </div>
                    <div class="metric-value">K<?php echo number_format($metrics['total_sales'], 2); ?></div>
                    <div class="metric-label"><?php echo $periodLabel; ?> Revenue</div>
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
                    <div class="metric-label"><?php echo $periodLabel; ?> Quantity</div>
                </div>

                <div class="metric-card expenses">
                    <div class="metric-header">
                        <div class="metric-icon">💸</div>
                    </div>
                    <div class="metric-value">K<?php echo number_format($metrics['total_expenses'], 2); ?></div>
                    <div class="metric-label"><?php echo $periodLabel; ?> Expenses</div>
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
                                            <span style="background: #fef3c7; color: #92400e; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500;">Low Stock</span>
                                        <?php else: ?>
                                            <span style="background: #dcfce7; color: #059669; padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500;">Good</span>
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
// Time filter functionality: submit form with selected period
document.querySelectorAll('.time-filter').forEach(button => {
    button.addEventListener('click', function() {
        const period = this.getAttribute('data-period');
        const form = document.querySelector('.controls-section');
        // Add or update hidden input for period
        let periodInput = form.querySelector('input[name="period"]');
        if (!periodInput) {
            periodInput = document.createElement('input');
            periodInput.type = 'hidden';
            periodInput.name = 'period';
            form.appendChild(periodInput);
        }
        periodInput.value = period;
        form.submit();
    });
});
</script>

<!-- PDF Generation Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>

<script>
// PDF Export Function
function exportToPDF() {
    // Helper to format currency without decimals, always returns a valid string
    function formatK(val) {
        if (!val) return 'K0';
        // Remove K, commas, spaces, decimals
        let num = parseInt(String(val).replace(/K|,/g, '').split('.')[0], 10);
        return !isNaN(num) ? 'K' + num.toLocaleString() : 'K0';
    }
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    // Get report data
    const branchSelect = document.querySelector('select[name="branch"]');
    const branch = branchSelect.options[branchSelect.selectedIndex].text;
    const date = document.querySelector('input[name="date"]').value;
    const periodInput = document.querySelector('input[name="period"]');
    let period = 'day';
    if (periodInput) {
        period = periodInput.value;
    } else {
        // Try to get from URL if present
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('period')) {
            period = urlParams.get('period');
        }
    }

    // Calculate period label for PDF
    let periodText = '';
    if (period === 'day') {
        // Show selected date in DD/MM/YYYY
        const d = new Date(date);
        periodText = d.toLocaleDateString('en-GB');
    } else {
        // Calculate range for week, month, quarter, year
        const d = new Date(date);
        let start, end;
        if (period === 'week') {
            // Monday to Sunday of the week
            const day = d.getDay();
            const diffToMonday = (day === 0 ? -6 : 1) - day;
            start = new Date(d);
            start.setDate(d.getDate() + diffToMonday);
            end = new Date(start);
            end.setDate(start.getDate() + 6);
        } else if (period === 'month') {
            start = new Date(d.getFullYear(), d.getMonth(), 1);
            end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        } else if (period === 'quarter') {
            const q = Math.floor(d.getMonth() / 3);
            start = new Date(d.getFullYear(), q * 3, 1);
            end = new Date(d.getFullYear(), q * 3 + 3, 0);
        } else if (period === 'year') {
            start = new Date(d.getFullYear(), 0, 1);
            end = new Date(d.getFullYear(), 11, 31);
        } else {
            start = end = d;
        }
        const startStr = start.toLocaleDateString('en-GB');
        const endStr = end.toLocaleDateString('en-GB');
        periodText = `${startStr} - ${endStr}`;
    }

    // Header Section with Green Background
    doc.setFillColor(16, 185, 129);
    doc.rect(0, 0, 210, 35, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont(undefined, 'bold');
    doc.text('Daily Business Report', 20, 20);

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
    doc.text(`Period: ${periodText}`, 20, 68);
    doc.text(`Generated: ${new Date().toLocaleString()}`, 20, 76);

    let yPos = 90;
    
    // Executive Summary Section (Styled)
    // Extract summary data from metric cards
    const metricCards = document.querySelectorAll('.metric-card');
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

    // Dynamically calculate box height to fit all content
    const boxX = 15;
    const boxY = yPos;
    const boxW = 180;
    // Calculate content height
    let contentY = boxY + 24;
    let boxContentHeight = 0;
    // 1. Report period/branch
    boxContentHeight += 8 + 7; // text + line
    // 2. Total Sales
    boxContentHeight += 8 + 7;
    // 3. Total Expenses
    boxContentHeight += 8 + 8;
    // 4. Total Cash (bigger)
    boxContentHeight += 14 + 12; // text + extra space
    const boxH = 24 + boxContentHeight; // 24 for title and top padding
    // Draw the box
    doc.setFillColor(236, 253, 245); // light green background
    doc.setDrawColor(16, 185, 129); // border color
    doc.roundedRect(boxX, boxY, boxW, boxH, 4, 4, 'FD');

    // Section Title
    doc.setFontSize(15);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(16, 185, 129);
    doc.text('Executive Summary', boxX + 6, boxY + 12);

    // Section Content - spaced, color coded, figures aligned right, and guiding lines
    contentY = boxY + 24;
    doc.setFontSize(11);
    doc.setFont(undefined, 'normal');
    const labelX = boxX + 10;
    const valueX = boxX + boxW - 12;
    // 1. Report period/branch (gray)
    doc.setTextColor(107, 114, 128); // slate-500
    doc.text(`This report covers selected day for ${branch} branch.`, labelX, contentY);
    contentY += 8;
    // Draw line below
    doc.setDrawColor(209, 213, 219); // gray-300
    doc.setLineWidth(0.3);
    doc.line(labelX, contentY, valueX, contentY);
    contentY += 7;
    // 2. Total Sales (blue)
    doc.setFont(undefined, 'bold');
    doc.setTextColor(37, 99, 235); // blue-600
    doc.text(`Total Sales:`, labelX, contentY, {align: 'left'});
    doc.setFont(undefined, 'normal');
    doc.text(`K${totalSales.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`, valueX, contentY, {align: 'right'});
    contentY += 8;
    // Draw line below
    doc.setDrawColor(209, 213, 219); // gray-300
    doc.setLineWidth(0.3);
    doc.line(labelX, contentY, valueX, contentY);
    contentY += 7;
    // 3. Total Expenses (red)
    doc.setFont(undefined, 'bold');
    doc.setTextColor(220, 38, 38); // red-600
    doc.text(`Total Expenses:`, labelX, contentY, {align: 'left'});
    doc.setFont(undefined, 'normal');
    doc.text(`K${totalExpenses.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`, valueX, contentY, {align: 'right'});
    contentY += 8;
    // Draw line below
    doc.setDrawColor(209, 213, 219); // gray-300
    doc.setLineWidth(0.3);
    doc.line(labelX, contentY, valueX, contentY);
    contentY += 8;
    // 4. Total Cash (Net Cash Flow) - bold, bigger, green, right-aligned
    doc.setFontSize(14);
    doc.setFont(undefined, 'bold');
    doc.setTextColor(16, 185, 129); // emerald-500
    doc.text(`Total Cash:`, labelX, contentY, {align: 'left'});
    doc.text(`K${netCash.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`, valueX, contentY, {align: 'right'});
    // Add extra space after Total Cash
    contentY += 12;
    yPos = boxY + boxH + 10;
    
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
        doc.text(`Opening Stock (as at ${date})`, 20, yPos);
        
        const openingData = [];
        const openingRows = openingStockTable.querySelectorAll('tbody tr');
        
        // Remove decimals for values in data arrays
        openingRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                openingData.push([
                    cells[0]?.textContent.trim() || '',
                    (cells[1]?.textContent.trim().split('.')[0] || ''),
                    formatK(cells[2]?.textContent.trim()),
                    formatK(cells[3]?.textContent.trim())
                ]);
            }
        });
        
        if (openingData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Product', 'Opening Stock', 'Unit Price', 'Stock Value']],
                body: openingData,
                theme: 'grid',
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 10, // bigger font
                    halign: 'left',
                    valign: 'middle',
                    cellPadding: 3, // reduced padding
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                styles: {
                    fontSize: 11, // bigger font
                    cellPadding: 3, // reduced padding
                    halign: 'left',
                    valign: 'middle',
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                columnStyles: {
                    0: { cellWidth: 70 },
                    1: { cellWidth: 35 },
                    2: { cellWidth: 35 },
                    3: { cellWidth: 40 }
                },
                didParseCell: function (data) {
                    data.cell.styles.halign = 'left';
                    data.cell.styles.cellPadding = 3;
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Sales Transactions Section (Sum totals per product)
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
        doc.text('Sales Totals by Product', 20, yPos);

        // Aggregate sales per product
        const salesRows = salesTable.querySelectorAll('tbody tr');
        const productTotals = {};
        salesRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 6) {
                const product = cells[1]?.textContent.trim() || '';
                const qty = parseInt((cells[2]?.textContent.trim() || '0').replace(/,/g, ''));
                const unitPrice = formatK(cells[3]?.textContent.trim());
                const total = parseInt((cells[4]?.textContent.trim().replace(/K|,/g, '') || '0'));
                if (!productTotals[product]) {
                    productTotals[product] = { qty: 0, total: 0, unitPrice: unitPrice };
                }
                productTotals[product].qty += qty;
                productTotals[product].total += total;
            }
        });

        // Prepare data for table
        const salesData = Object.entries(productTotals).map(([product, data]) => [
            product,
            data.qty,
            data.unitPrice,
            formatK(data.total)
        ]);

        if (salesData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Product', 'Total Qty Sold', 'Unit Price', 'Total Sales']],
                body: salesData,
                theme: 'grid',
                headStyles: {
                    fillColor: [102, 126, 234],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 10,
                    halign: 'left',
                    valign: 'middle',
                    cellPadding: 3,
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                styles: {
                    fontSize: 11,
                    cellPadding: 3,
                    halign: 'left',
                    valign: 'middle',
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                columnStyles: {
                    0: { cellWidth: 70 },
                    1: { cellWidth: 35 },
                    2: { cellWidth: 35 },
                    3: { cellWidth: 40 }
                },
                didParseCell: function (data) {
                    data.cell.styles.halign = 'left';
                    data.cell.styles.cellPadding = 3;
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
        doc.text('Daily Expenses', 20, yPos);
        
        const expensesData = [];
        const expenseRows = expensesTable.querySelectorAll('tbody tr');
        
        expenseRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 3) {
                expensesData.push([
                    cells[0]?.textContent.trim() || '',
                    cells[1]?.textContent.trim() || '',
                    formatK(cells[2]?.textContent.trim())
                ]);
            }
        });
        
        if (expensesData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Category', 'Description', 'Amount']],
                body: expensesData,
                theme: 'grid',
                headStyles: {
                    fillColor: [250, 112, 154],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 10,
                    halign: 'left',
                    valign: 'middle',
                    cellPadding: 3,
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                styles: {
                    fontSize: 11,
                    cellPadding: 3,
                    halign: 'left',
                    valign: 'middle',
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                columnStyles: {
                    0: { cellWidth: 60 },
                    1: { cellWidth: 80 },
                    2: { cellWidth: 35 }
                },
                didParseCell: function (data) {
                    data.cell.styles.halign = 'left';
                    data.cell.styles.cellPadding = 3;
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }
    }
    
    // Closing Stock Section (only products with stock, note for out of stock)
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
        doc.text(`Closing Stock (as at ${date})`, 20, yPos);

        const closingData = [];
        const outOfStock = [];
        const closingRows = closingStockTable.querySelectorAll('tbody tr');
        let rowCount = 0;

        closingRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 5) {
                const product = cells[0]?.textContent.trim() || '';
                const stock = parseInt((cells[1]?.textContent.trim() || '0').replace(/,/g, ''));
                if (stock > 0) {
                    closingData.push([
                        product,
                        (cells[1]?.textContent.trim().split('.')[0] || ''),
                        formatK(cells[2]?.textContent.trim()),
                        formatK(cells[3]?.textContent.trim()),
                        cells[4]?.textContent.trim() || ''
                    ]);
                    rowCount++;
                } else {
                    outOfStock.push(product);
                }
            }
        });

        if (closingData.length > 0) {
            doc.autoTable({
                startY: yPos + 10,
                head: [['Product', 'Closing Stock', 'Unit Price', 'Stock Value', 'Status']],
                body: closingData,
                theme: 'grid',
                headStyles: {
                    fillColor: [79, 172, 254],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 10,
                    halign: 'left',
                    valign: 'middle',
                    cellPadding: 3,
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                styles: {
                    fontSize: 11,
                    cellPadding: 3,
                    halign: 'left',
                    valign: 'middle',
                    lineWidth: 0.2,
                    lineColor: [209,213,219]
                },
                columnStyles: {
                    0: { cellWidth: 60 },
                    1: { cellWidth: 30 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 30 },
                    4: { cellWidth: 30 }
                },
                didParseCell: function (data) {
                    data.cell.styles.halign = 'left';
                    data.cell.styles.cellPadding = 3;
                }
            });
            yPos = doc.lastAutoTable.finalY + 15;
        }

        // Add note for out of stock products
        if (outOfStock.length > 0) {
            // Estimate required height: 6px per product + header + margin
            const perCol = 25;
            const numRows = Math.min(outOfStock.length, perCol);
            const neededHeight = 7 + (numRows * 6) + 18 + 10; // header + rows + margin + buffer
            const pageHeight = 297; // A4 height in mm
            const footerHeight = 20;
            if (yPos + neededHeight > pageHeight - footerHeight) {
                doc.addPage();
                yPos = 20;
            }
            doc.setFontSize(10);
            doc.setTextColor(220, 38, 38); // red
            doc.setFont(undefined, 'bold');
            doc.text('Out of Stock Products:', 20, yPos);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(0, 0, 0);
            // List products in columns if many
            let col = 0;
            let x = 20;
            let y = yPos + 7;
            outOfStock.forEach((prod, idx) => {
                // If near bottom, add new page and reset x/y
                if (y > pageHeight - footerHeight - 10) {
                    doc.addPage();
                    col = 0;
                    x = 20;
                    y = 20 + 7;
                    doc.setFontSize(10);
                    doc.setTextColor(220, 38, 38);
                    doc.setFont(undefined, 'bold');
                    doc.text('Out of Stock Products (cont.):', 20, 20);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(0, 0, 0);
                }
                doc.text(`- ${prod}`, x, y);
                y += 6;
                if ((idx + 1) % perCol === 0) {
                    col++;
                    x += 60;
                    y = yPos + 7;
                }
            });
            // Add extra space after the out-of-stock list to avoid overlap
            yPos = y + 18;
        }
    }
    
    
    // Footer
    const pageCount = doc.internal.getNumberOfPages();
    for (let i = 1; i <= pageCount; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(107, 114, 128);
        doc.text(`Page ${i} of ${pageCount}`, 170, 285);
        // Add clickable link for LedgerLink Pro in the footer
        const footerText1 = 'Generated by ';
        const footerText2 = 'LedgerLink Pro';
        const footerText3 = ' | Sales Management System - ' + new Date().toLocaleDateString();
        let x = 20;
        let y = 285;
        // Draw first part
        doc.text(footerText1, x, y, { baseline: 'top' });
        x += doc.getTextWidth(footerText1);
        // Draw LedgerLink Pro as a link
        doc.setTextColor(37, 99, 235); // blue color for link
        doc.textWithLink(footerText2, x, y, { url: 'https://ledgerlink.pro', baseline: 'top' });
        x += doc.getTextWidth(footerText2);
        // Draw the rest
        doc.setTextColor(107, 114, 128);
        doc.text(footerText3, x, y, { baseline: 'top' });
    }
    
    // Save the PDF
    const fileName = `Report_${branch}_${new Date(date).toISOString().split('T')[0]}.pdf`;
    doc.save(fileName);
    
    // Show success message
    alert('PDF report generated successfully!');
}
</script>

<?php include '../includes/footer.php'; ?>
