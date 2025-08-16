<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();

// Get today's metrics
$today = date('Y-m-d');

// Today's Sales
$result = $conn->query("SELECT SUM(total) AS today_sales FROM sales WHERE DATE(sale_date) = '$today'");
$today_sales = ($row = $result->fetch_assoc()) ? floatval($row['today_sales']) : 0;

// Today's Expenses
$result = $conn->query("SELECT SUM(amount) AS today_expenses FROM expenses WHERE DATE(expense_date) = '$today'");
$today_expenses = ($row = $result->fetch_assoc()) ? floatval($row['today_expenses']) : 0;

// Net Cash
$net_cash = $today_sales - $today_expenses;

// This Week's Sales
$week_start = date('Y-m-d', strtotime('monday this week'));
$result = $conn->query("SELECT SUM(total) AS week_sales FROM sales WHERE DATE(sale_date) >= '$week_start'");
$week_sales = ($row = $result->fetch_assoc()) ? floatval($row['week_sales']) : 0;

// This Month's Sales
$month_start = date('Y-m-01');
$result = $conn->query("SELECT SUM(total) AS month_sales FROM sales WHERE DATE(sale_date) >= '$month_start'");
$month_sales = ($row = $result->fetch_assoc()) ? floatval($row['month_sales']) : 0;

// Total Products
$result = $conn->query("SELECT COUNT(*) AS total_products FROM products");
$total_products = ($row = $result->fetch_assoc()) ? intval($row['total_products']) : 0;

// Low Stock Items (less than 10 units)
$low_stock_items = [];
$products = $conn->query('SELECT id, name FROM products LIMIT 5'); // Limit for performance
while ($product = $products->fetch_assoc()) {
    $pid = $product['id'];
    $total_stock = 0;
    foreach (['Livingstone', 'Chisamba', 'Lusaka'] as $branch) {
        // Calculate stock for this product and branch
        $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $pid AND branch = '$branch'");
        $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
        $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $pid AND branch = '$branch'");
        $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
        $transfers_out = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND source_branch = '$branch'");
        $out = ($row = $transfers_out->fetch_assoc()) ? intval($row['total']) : 0;
        $transfers_in = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND dest_branch = '$branch'");
        $in = ($row = $transfers_in->fetch_assoc()) ? intval($row['total']) : 0;
        $total_stock += $purchased - $sold - $out + $in;
    }
    if ($total_stock < 10) {
        $low_stock_items[] = ['name' => $product['name'], 'stock' => $total_stock];
    }
}

// This Quarter's Sales
$quarter_start = date('Y-m-d', strtotime('first day of january this year'));
$result = $conn->query("SELECT SUM(total) AS quarter_sales FROM sales WHERE DATE(sale_date) >= '$quarter_start'");
$quarter_sales = ($row = $result->fetch_assoc()) ? floatval($row['quarter_sales']) : 0;

// This Quarter's Expenses  
$result = $conn->query("SELECT SUM(amount) AS quarter_expenses FROM expenses WHERE DATE(expense_date) >= '$quarter_start'");
$quarter_expenses = ($row = $result->fetch_assoc()) ? floatval($row['quarter_expenses']) : 0;

// This Week's Expenses
$result = $conn->query("SELECT SUM(amount) AS week_expenses FROM expenses WHERE DATE(expense_date) >= '$week_start'");
$week_expenses = ($row = $result->fetch_assoc()) ? floatval($row['week_expenses']) : 0;

// This Month's Expenses
$result = $conn->query("SELECT SUM(amount) AS month_expenses FROM expenses WHERE DATE(expense_date) >= '$month_start'");
$month_expenses = ($row = $result->fetch_assoc()) ? floatval($row['month_expenses']) : 0;

// This Year's Sales
$year_start = date('Y-01-01');
$result = $conn->query("SELECT SUM(total) AS year_sales FROM sales WHERE DATE(sale_date) >= '$year_start'");
$year_sales = ($row = $result->fetch_assoc()) ? floatval($row['year_sales']) : 0;

// This Year's Expenses
$result = $conn->query("SELECT SUM(amount) AS year_expenses FROM expenses WHERE DATE(expense_date) >= '$year_start'");
$year_expenses = ($row = $result->fetch_assoc()) ? floatval($row['year_expenses']) : 0;

// Today's Sales (already calculated above as $today_sales)
// Today's Expenses (already calculated above as $today_expenses)

$conn->close();

// Set page variables for header
$pageTitle = 'Reports Dashboard - LedgerLink Inventory';
$currentPage = 'reports';
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Dashboard Styles -->
<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .dashboard-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .dashboard-subtitle {
        color: #64748b;
        font-size: 1.1rem;
        font-weight: 400;
    }
    
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 3rem;
    }
    
    .metric-card {
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
        min-height: 120px;
    }
    
    .metric-card:hover {
        transform: translateY(-2px);
    }
    
    .metric-card.sales { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .metric-card.expenses { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .metric-card.profit { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
    }
    .metric-card.products { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
    }
    
    .metric-card::before {
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
    
    .metric-icon {
        font-size: 32px;
        opacity: 0.9;
        min-width: 40px;
    }
    
    .metric-content {
        flex: 1;
    }
    
    .metric-label {
        font-size: 14px;
        opacity: 0.9;
        color: white;
        font-weight: 500;
        margin-bottom: 4px;
        transition: opacity 0.3s ease;
    }
    
    .metric-value {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 0.25rem;
        line-height: 1;
        transition: opacity 0.3s ease;
    }
    
    .metric-change {
        font-size: 12px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        color: rgba(255, 255, 255, 0.8);
        transition: all 0.3s ease;
    }
    
    .insights-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 3rem;
    }
    
    .insights-card {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }
    
    .insights-header {
        display: flex;
        align-items: center;
        justify-content: between;
        margin-bottom: 1.5rem;
    }
    
    .insights-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
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
    
    .period-metrics {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .period-metric {
        text-align: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.75rem;
    }
    
    .period-metric-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #10b981;
        margin-bottom: 0.25rem;
        transition: opacity 0.3s ease;
    }
    
    .period-metric-label {
        font-size: 0.875rem;
        color: #64748b;
        font-weight: 500;
        transition: opacity 0.3s ease;
    }
    
    .alerts-section {
        background: #ffffff;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }
    
    .alerts-header {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        padding: 1.5rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .alerts-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #92400e;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .alert-item {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .alert-item:last-child {
        border-bottom: none;
    }
    
    .alert-product {
        font-weight: 500;
        color: #1e293b;
    }
    
    .alert-stock {
        font-size: 0.875rem;
        font-weight: 600;
        color: #dc2626;
        background: #fef2f2;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
    }
    
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-top: 3rem;
    }
    
    .action-card {
        background: #ffffff;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-decoration: none;
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.75rem;
    }
    
    .action-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }
    
    .action-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        color: white;
    }
    
    .action-card.sales .action-icon { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .action-card.expenses .action-icon { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .action-card.stock .action-icon { background: linear-gradient(135deg, #10b981, #059669); }
    
    .action-title {
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .action-description {
        font-size: 0.875rem;
        color: #64748b;
        margin: 0;
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .insights-grid {
            grid-template-columns: 1fr;
        }
        
        .period-metrics {
            grid-template-columns: 1fr;
        }
        
        .dashboard-title {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 480px) {
        .metrics-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .metric-card {
            padding: 1rem;
            min-height: 100px;
        }
        
        .metric-value {
            font-size: 1.5rem;
        }
    }
</style>

<div class="dashboard-container">
    <!-- Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Analytics Dashboard</h1>
        <p class="dashboard-subtitle">Real-time insights into your business performance</p>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card sales">
            <div class="metric-icon">📈</div>
            <div class="metric-content">
                <div class="metric-value" id="main-sales-value">K<?php echo number_format($week_sales, 2); ?></div>
                <div class="metric-label" id="main-sales-label">This Week's Sales</div>
                <div class="metric-change" id="main-sales-change">
                    <span>💰</span> <span id="main-sales-description">Revenue this week</span>
                </div>
            </div>
        </div>

        <div class="metric-card expenses">
            <div class="metric-icon">💸</div>
            <div class="metric-content">
                <div class="metric-value" id="main-expenses-value">K<?php echo number_format($week_expenses, 2); ?></div>
                <div class="metric-label" id="main-expenses-label">This Week's Expenses</div>
                <div class="metric-change" id="main-expenses-change">
                    <span>💼</span> <span id="main-expenses-description">Money spent this week</span>
                </div>
            </div>
        </div>

        <div class="metric-card profit">
            <div class="metric-icon">💎</div>
            <div class="metric-content">
                <div class="metric-value" id="main-profit-value">
                    K<?php echo number_format($week_sales - $week_expenses, 2); ?>
                </div>
                <div class="metric-label" id="main-profit-label">Net Profit</div>
                <div class="metric-change" id="main-profit-change">
                    <span id="main-profit-icon">📊</span> 
                    <span id="main-profit-description">Profit this week</span>
                </div>
            </div>
        </div>

        <div class="metric-card products">
            <div class="metric-icon">📦</div>
            <div class="metric-content">
                <div class="metric-value"><?php echo $total_products; ?></div>
                <div class="metric-label">Total Products</div>
                <div class="metric-change">
                    <span>🏪</span> Products in inventory
                </div>
            </div>
        </div>
    </div>

    <!-- Insights Section -->
    <div class="insights-grid">
        <!-- Period Performance -->
        <div class="insights-card">
            <div class="insights-header">
                <h2 class="insights-title">
                    <span>📊</span> Period Performance
                </h2>
            </div>
            
            <div class="period-tabs">
                <button class="period-tab" data-period="day">Day</button>
                <button class="period-tab active" data-period="week">Week</button>
                <button class="period-tab" data-period="month">Month</button>
                <button class="period-tab" data-period="quarter">Quarter</button>
                <button class="period-tab" data-period="year">Year</button>
            </div>
            
            <div class="period-metrics">
                <div class="period-metric">
                    <div class="period-metric-value" id="sales-value">K<?php echo number_format($week_sales, 2); ?></div>
                    <div class="period-metric-label" id="sales-label">This Week's Sales</div>
                </div>
                <div class="period-metric">
                    <div class="period-metric-value" id="expenses-value">K<?php echo number_format($week_expenses, 2); ?></div>
                    <div class="period-metric-label" id="expenses-label">This Week's Expenses</div>
                </div>
            </div>
        </div>

        <!-- Stock Alerts -->
        <div class="alerts-section">
            <div class="alerts-header">
                <h2 class="alerts-title">
                    <span>⚠️</span> Stock Alerts
                </h2>
            </div>
            
            <?php if (!empty($low_stock_items)): ?>
                <?php foreach (array_slice($low_stock_items, 0, 5) as $item): ?>
                    <div class="alert-item">
                        <span class="alert-product"><?php echo htmlspecialchars($item['name']); ?></span>
                        <span class="alert-stock"><?php echo $item['stock']; ?> units</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="alert-item">
                    <span class="alert-product" style="color: #059669;">All products well-stocked</span>
                    <span style="color: #059669; font-size: 1.25rem;">✅</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="sales.php" class="action-card sales">
            <div class="action-icon">📊</div>
            <h3 class="action-title">Sales Report</h3>
            <p class="action-description">Detailed sales analysis and trends</p>
        </a>
        
        <a href="expenses.php" class="action-card expenses">
            <div class="action-icon">💰</div>
            <h3 class="action-title">Expenses Report</h3>
            <p class="action-description">Track your expenditures and costs</p>
        </a>
        
        <a href="stock.php" class="action-card stock">
            <div class="action-icon">📦</div>
            <h3 class="action-title">Stock Report</h3>
            <p class="action-description">Inventory levels across branches</p>
        </a>
    </div>
</div>

<script>
// Period Performance Tab Functionality
document.addEventListener('DOMContentLoaded', function() {
    const periodData = {
        day: {
            sales: '<?php echo number_format($today_sales, 2); ?>',
            expenses: '<?php echo number_format($today_expenses, 2); ?>',
            salesLabel: "Today's Sales",
            expensesLabel: "Today's Expenses",
            mainSalesLabel: "Today's Sales",
            mainExpensesLabel: "Today's Expenses",
            mainSalesDescription: "Revenue earned today",
            mainExpensesDescription: "Money spent today",
            mainProfitDescription: "Profit today",
            profit: <?php echo $today_sales - $today_expenses; ?>
        },
        week: {
            sales: '<?php echo number_format($week_sales, 2); ?>',
            expenses: '<?php echo number_format($week_expenses, 2); ?>',
            salesLabel: "This Week's Sales",
            expensesLabel: "This Week's Expenses",
            mainSalesLabel: "This Week's Sales",
            mainExpensesLabel: "This Week's Expenses",
            mainSalesDescription: "Revenue this week",
            mainExpensesDescription: "Money spent this week",
            mainProfitDescription: "Profit this week",
            profit: <?php echo $week_sales - $week_expenses; ?>
        },
        month: {
            sales: '<?php echo number_format($month_sales, 2); ?>',
            expenses: '<?php echo number_format($month_expenses, 2); ?>',
            salesLabel: "This Month's Sales", 
            expensesLabel: "This Month's Expenses",
            mainSalesLabel: "This Month's Sales",
            mainExpensesLabel: "This Month's Expenses",
            mainSalesDescription: "Revenue this month",
            mainExpensesDescription: "Money spent this month",
            mainProfitDescription: "Profit this month",
            profit: <?php echo $month_sales - $month_expenses; ?>
        },
        quarter: {
            sales: '<?php echo number_format($quarter_sales, 2); ?>',
            expenses: '<?php echo number_format($quarter_expenses, 2); ?>',
            salesLabel: "This Quarter's Sales",
            expensesLabel: "This Quarter's Expenses",
            mainSalesLabel: "This Quarter's Sales",
            mainExpensesLabel: "This Quarter's Expenses",
            mainSalesDescription: "Revenue this quarter",
            mainExpensesDescription: "Money spent this quarter",
            mainProfitDescription: "Profit this quarter",
            profit: <?php echo $quarter_sales - $quarter_expenses; ?>
        },
        year: {
            sales: '<?php echo number_format($year_sales, 2); ?>',
            expenses: '<?php echo number_format($year_expenses, 2); ?>',
            salesLabel: "This Year's Sales",
            expensesLabel: "This Year's Expenses",
            mainSalesLabel: "This Year's Sales",
            mainExpensesLabel: "This Year's Expenses",
            mainSalesDescription: "Revenue this year",
            mainExpensesDescription: "Money spent this year",
            mainProfitDescription: "Profit this year",
            profit: <?php echo $year_sales - $year_expenses; ?>
        }
    };
    
    const tabs = document.querySelectorAll('.period-tab');
    
    // Period Performance elements
    const salesValue = document.getElementById('sales-value');
    const expensesValue = document.getElementById('expenses-value');
    const salesLabel = document.getElementById('sales-label');
    const expensesLabel = document.getElementById('expenses-label');
    
    // Main card elements
    const mainSalesLabel = document.getElementById('main-sales-label');
    const mainSalesValue = document.getElementById('main-sales-value');
    const mainSalesDescription = document.getElementById('main-sales-description');
    const mainExpensesLabel = document.getElementById('main-expenses-label');
    const mainExpensesValue = document.getElementById('main-expenses-value');
    const mainExpensesDescription = document.getElementById('main-expenses-description');
    const mainProfitLabel = document.getElementById('main-profit-label');
    const mainProfitValue = document.getElementById('main-profit-value');
    const mainProfitDescription = document.getElementById('main-profit-description');
    const mainProfitIcon = document.getElementById('main-profit-icon');
    const mainProfitChange = document.getElementById('main-profit-change');
    
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
                salesValue, expensesValue, salesLabel, expensesLabel,
                mainSalesLabel, mainSalesValue, mainSalesDescription,
                mainExpensesLabel, mainExpensesValue, mainExpensesDescription,
                mainProfitValue, mainProfitDescription
            ];
            
            elementsToUpdate.forEach(element => {
                if (element) element.style.opacity = '0.5';
            });
            
            setTimeout(() => {
                // Update Period Performance section
                salesValue.textContent = 'K' + data.sales;
                expensesValue.textContent = 'K' + data.expenses;
                salesLabel.textContent = data.salesLabel;
                expensesLabel.textContent = data.expensesLabel;
                
                // Update main cards
                mainSalesLabel.textContent = data.mainSalesLabel;
                mainSalesValue.textContent = 'K' + data.sales;
                mainSalesDescription.textContent = data.mainSalesDescription;
                
                mainExpensesLabel.textContent = data.mainExpensesLabel;
                mainExpensesValue.textContent = 'K' + data.expenses;
                mainExpensesDescription.textContent = data.mainExpensesDescription;
                
                // Update profit card
                const profit = data.profit;
                mainProfitValue.textContent = 'K' + profit.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                mainProfitDescription.textContent = data.mainProfitDescription;
                
                // Update profit styling based on value
                mainProfitChange.className = 'metric-change ' + (profit >= 0 ? 'positive' : 'negative');
                mainProfitIcon.textContent = profit >= 0 ? '📊' : '📉';
                
                // Restore opacity
                elementsToUpdate.forEach(element => {
                    if (element) element.style.opacity = '1';
                });
            }, 150);
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>