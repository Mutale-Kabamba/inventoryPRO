<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();

// Filter parameters
$category_filter = $_GET['category'] ?? '';
$branch_filter = $_GET['branch'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = [];
if ($category_filter) {
    $where_conditions[] = "category = '" . $conn->real_escape_string($category_filter) . "'";
}
if ($branch_filter) {
    $where_conditions[] = "branch = '" . $conn->real_escape_string($branch_filter) . "'";
}
if ($date_from) {
    $where_conditions[] = "DATE(expense_date) >= '" . $conn->real_escape_string($date_from) . "'";
}
if ($date_to) {
    $where_conditions[] = "DATE(expense_date) <= '" . $conn->real_escape_string($date_to) . "'";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "SELECT id, category, amount, branch, description, expense_date 
          FROM expenses 
          $where_clause 
          ORDER BY expense_date DESC";

$result = $conn->query($query);

// Calculate totals
$total_expenses = 0;
$expenses_data = [];
while ($row = $result->fetch_assoc()) {
    $expenses_data[] = $row;
    $total_expenses += $row['amount'];
}

$categories = ['Operation', 'Logistics', 'Office & Stationery', 'Home & Farm', 'Miscellaneous', 'Bills'];
$branches = ['Livingstone', 'Chisamba', 'Lusaka'];
$conn->close();

// Set page variables for header
$pageTitle = 'Expenses Report - LedgerLink Inventory';
$currentPage = 'reports';
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Expenses Report Styles -->
<style>
    .expenses-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .expenses-header {
        text-align: center;
        margin-bottom: 3rem;
    }
    
    .expenses-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .expenses-subtitle {
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
        border-color: #ef4444;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
    }
    
    .filter-actions {
        display: flex;
        gap: 0.75rem;
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
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
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
        background: rgba(239, 68, 68, 0.08);
        color: #ef4444;
    }
    
    .time-filter-btn.active {
        background: #ef4444;
        color: white;
        box-shadow: 0 1px 2px rgba(239, 68, 68, 0.2);
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
    
    .summary-card.expenses { 
        --card-bg-start: #667eea;
        --card-bg-end: #764ba2;
    }
    
    .summary-card.transactions { 
        --card-bg-start: #f093fb;
        --card-bg-end: #f5576c;
    }
    
    .summary-card.count { 
        --card-bg-start: #4facfe;
        --card-bg-end: #00f2fe;
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
    
    .summary-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.25rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        line-height: 1;
    }
    
    .summary-label {
        font-size: 0.8125rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        opacity: 0.95;
        line-height: 1.2;
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
    
    .expenses-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .expenses-table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .expenses-table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .expenses-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .expenses-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .category-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
    }
    
    .category-operation { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
    .category-logistics { background: linear-gradient(135deg, #10b981, #059669); }
    .category-office { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .category-home { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .category-miscellaneous { background: linear-gradient(135deg, #6b7280, #4b5563); }
    .category-bills { background: linear-gradient(135deg, #ef4444, #dc2626); }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #ef4444;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .back-link:hover {
        color: #dc2626;
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
        .expenses-container {
            padding: 1rem;
        }
        
        .filter-form {
            grid-template-columns: 1fr;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
        }
        
        .expenses-title {
            font-size: 2rem;
        }
        
        .expenses-table {
            font-size: 0.75rem;
        }
        
        .expenses-table th,
        .expenses-table td {
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

<div class="expenses-container">
    <!-- Header -->
    <div class="expenses-header">
        <h1 class="expenses-title">Expenses Report</h1>
        <p class="expenses-subtitle">Track and analyze business expenditures across all categories</p>
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
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php if ($category_filter == $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
                    <?php endforeach; ?>
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
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="date_to" class="form-label">To Date:</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" class="form-input">
            </div>
            
            <div class="form-group">
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <span>💸</span> Filter
                    </button>
                    <a href="expenses.php" class="btn btn-secondary">
                        <span>🔄</span> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Performance Header with Time Filters -->
    <div class="performance-header">
        <div class="performance-title-section">
            <h2 class="performance-title">📊 Expenses Performance</h2>
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
        <div class="summary-card expenses">
            <div class="summary-icon">💸</div>
            <div class="summary-content">
                <div class="summary-value">K<?php echo number_format($total_expenses, 2); ?></div>
                <div class="summary-label">Total Expenses</div>
            </div>
        </div>
        
        <div class="summary-card transactions">
            <div class="summary-icon">📋</div>
            <div class="summary-content">
                <div class="summary-value"><?php echo count($expenses_data); ?></div>
                <div class="summary-label">Transactions</div>
            </div>
        </div>
        
        <div class="summary-card count">
            <div class="summary-icon">📊</div>
            <div class="summary-content">
                <div class="summary-value"><?php echo count(array_unique(array_column($expenses_data, 'category'))); ?></div>
                <div class="summary-label">Categories</div>
            </div>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="table-card">
        <div class="table-header">
            <h2 class="table-title">
                <span>💰</span> Expense Transactions
            </h2>
        </div>
        
        <?php if (!empty($expenses_data)): ?>
            <table class="expenses-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Amount (ZMW)</th>
                        <th>Branch</th>
                        <th>Description</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses_data as $row): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td>
                                <?php 
                                $category_class = 'category-' . strtolower(str_replace([' ', '&'], ['', ''], $row['category']));
                                ?>
                                <span class="category-badge <?php echo $category_class; ?>">
                                    <?php echo htmlspecialchars($row['category']); ?>
                                </span>
                            </td>
                            <td>K<?php echo number_format($row['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($row['branch']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['expense_date'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">💸</div>
                <h3>No Expense Data Found</h3>
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
    const timeFilterBtns = document.querySelectorAll('.time-filter-btn');
    
    // Set active button based on current filters
    function setActiveButton() {
        const urlParams = new URLSearchParams(window.location.search);
        const dateFrom = urlParams.get('date_from');
        const dateTo = urlParams.get('date_to');
        
        if (dateFrom && dateTo) {
            const today = new Date();
            const fromDate = new Date(dateFrom);
            const toDate = new Date(dateTo);
            
            // Check which period matches current filter
            const dayStart = new Date(today);
            const weekStart = new Date(today); weekStart.setDate(today.getDate() - 6);
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            const quarterStart = new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1);
            const yearStart = new Date(today.getFullYear(), 0, 1);
            
            // Remove active from all buttons first
            timeFilterBtns.forEach(btn => btn.classList.remove('active'));
            
            // Set active button based on date range
            if (fromDate.toDateString() === dayStart.toDateString()) {
                document.querySelector('[data-period="day"]').classList.add('active');
            } else if (fromDate.toDateString() === weekStart.toDateString()) {
                document.querySelector('[data-period="week"]').classList.add('active');
            } else if (fromDate.toDateString() === monthStart.toDateString()) {
                document.querySelector('[data-period="month"]').classList.add('active');
            } else if (fromDate.toDateString() === quarterStart.toDateString()) {
                document.querySelector('[data-period="quarter"]').classList.add('active');
            } else if (fromDate.toDateString() === yearStart.toDateString()) {
                document.querySelector('[data-period="year"]').classList.add('active');
            }
        }
    }
    
    // Set initial active button
    setActiveButton();
    
    timeFilterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Show loading state
            this.style.opacity = '0.6';
            this.style.pointerEvents = 'none';
            
            // Remove active class from all buttons
            timeFilterBtns.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the selected period
            const period = this.dataset.period;
            
            // Calculate date range based on period
            const today = new Date();
            let fromDate, toDate = today;
            
            switch(period) {
                case 'day':
                    fromDate = new Date(today);
                    break;
                case 'week':
                    fromDate = new Date(today);
                    fromDate.setDate(today.getDate() - 6);
                    break;
                case 'month':
                    fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
                    break;
                case 'quarter':
                    const quarter = Math.floor(today.getMonth() / 3);
                    fromDate = new Date(today.getFullYear(), quarter * 3, 1);
                    break;
                case 'year':
                    fromDate = new Date(today.getFullYear(), 0, 1);
                    break;
            }
            
            // Format dates for URL parameters
            const fromDateStr = fromDate.toISOString().split('T')[0];
            const toDateStr = toDate.toISOString().split('T')[0];
            
            // Update URL with new date filters while preserving other filters
            const url = new URL(window.location);
            url.searchParams.set('date_from', fromDateStr);
            url.searchParams.set('date_to', toDateStr);
            
            // Reload page with new filters
            window.location.href = url.toString();
        });
    });
    
    // Add quick filter functionality for categories
    const categorySelect = document.getElementById('category');
    const branchSelect = document.getElementById('branch');
    
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            if (this.value !== '') {
                // Auto-submit form when category changes
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
});
</script>

<?php include '../includes/footer.php'; ?>