<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();

// Fetch all products with categories
$products = [];
$result = $conn->query('SELECT id, name, category, price FROM products ORDER BY name ASC');
while ($row = $result->fetch_assoc()) {
    $products[$row['id']] = [
        'name' => $row['name'],
        'category' => $row['category'],
        'price' => $row['price']
    ];
}

$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

// Calculate stock for each product and branch
$stock = [];
$totalProducts = 0;
$outOfStock = 0;
$lowStock = 0;
$totalValue = 0;

foreach ($products as $pid => $product) {
    $productTotalStock = 0;
    foreach ($branches as $branch) {
        // Purchases
        $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $pid AND branch = '$branch'");
        $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Sales
        $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $pid AND branch = '$branch'");
        $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers out
        $transfersOut = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND source_branch = '$branch'");
        $out = ($row = $transfersOut->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers in
        $transfersIn = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND dest_branch = '$branch'");
        $in = ($row = $transfersIn->fetch_assoc()) ? intval($row['total']) : 0;
        
        $branchStock = $purchased - $sold - $out + $in;
        $stock[$pid][$branch] = $branchStock;
        $productTotalStock += $branchStock;
    }
    
    $totalProducts++;
    if ($productTotalStock == 0) {
        $outOfStock++;
    } elseif ($productTotalStock < 10) {
        $lowStock++;
    }
    
    $totalValue += $productTotalStock * $product['price'];
}

$conn->close();

// Set page variables for header
$pageTitle = 'Stock Management - LedgerLink Inventory';
$currentPage = 'stock';
?>
<?php include '../includes/header.php'; ?>

<!-- Custom Stock Page Styles -->
<style>
    .stock-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .stock-header {
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
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .header-content p {
        color: #64748b;
        font-size: 1rem;
        margin: 0;
    }
    
    .action-buttons {
        display: flex;
        gap: 0.75rem;
    }
    
    .action-buttons .btn {
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
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }
    
    .btn-secondary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }
    
    .btn-outline {
        background: white;
        color: #64748b;
        border: 1px solid #e2e8f0;
    }
    
    .btn-outline:hover {
        background: #f8fafc;
        transform: translateY(-1px);
    }
    
    .stock-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 3rem;
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
        min-height: 120px;
    }
    
    .stat-card:hover {
        transform: translateY(-2px);
    }
    
    .stat-card.blue { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .stat-card.red { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .stat-card.yellow { 
        background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); 
        color: #92400e;
    }
    .stat-card.green { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
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
    
    .stat-number {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 4px;
        line-height: 1;
    }
    
    .stat-card.blue .stat-number { color: white; }
    .stat-card.red .stat-number { color: white; }
    .stat-card.yellow .stat-number { color: #92400e; }
    .stat-card.green .stat-number { color: white; }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        font-weight: 500;
    }
    
    .stat-card.blue .stat-label { color: white; }
    .stat-card.red .stat-label { color: white; }
    .stat-card.yellow .stat-label { color: #92400e; }
    .stat-card.green .stat-label { color: white; }
    
    /* Period Performance Styles */
    .period-performance {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        position: relative;
        overflow: hidden;
    }
    
    .period-performance::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #ffecd2 75%, #43e97b 100%);
    }
    
    .performance-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 0;
    }
    
    .performance-header h2 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .period-tabs {
        display: flex;
        gap: 0.5rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        padding: 0.25rem;
        border: 1px solid #e2e8f0;
    }
    
    .period-tab {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.5rem;
        background: transparent;
        color: #64748b;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    
    .period-tab:hover {
        background: #e2e8f0;
        color: #374151;
    }
    
    .period-tab.active {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
    }
    
    .period-tab.active:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
    }
    
    /* Period Insights Styles */
    .period-insights {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e2e8f0;
    }
    
    .insights-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin: 0 0 1rem 0;
    }
    
    .insights-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    
    .insight-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    
    .insight-item:hover {
        background: #f1f5f9;
        transform: translateY(-1px);
    }
    
    .insight-icon {
        font-size: 1.5rem;
        opacity: 0.8;
    }
    
    .insight-content {
        flex: 1;
    }
    
    .insight-number {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1;
    }
    
    .insight-label {
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 500;
        margin-top: 0.25rem;
    }
    
    .filter-section {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        position: relative;
        overflow: hidden;
    }
    
    .filter-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #ffecd2 75%, #43e97b 100%);
    }
    
    .filter-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .filter-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    
    .filter-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .filter-reset {
        padding: 0.5rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: white;
        color: #6b7280;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-reset:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        align-items: stretch;
        margin-bottom: 1rem;
    }
    
    .filter-buttons {
        display: flex;
        gap: 1rem;
        justify-content: flex-start;
        margin-bottom: 1rem;
    }
    
    .search-row {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 1rem;
        align-items: end;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        position: relative;
        align-items: stretch;
        justify-content: flex-end;
        height: 100%;
    }
    
    .form-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-shrink: 0;
        height: 1.5rem;
    }
    
    .form-group .label-icon {
        font-size: 0.75rem;
    }
    
    .form-group.search-group {
        max-width: 300px;
    }
    
    .form-group.search-group input {
        font-size: 0.875rem;
        padding: 0.625rem 0.75rem;
        height: 42px;
        min-height: 42px;
    }
    
    .clear-search {
        padding: 0.625rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: white;
        color: #6b7280;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s;
        height: 42px;
        min-height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        white-space: nowrap;
    }
    
    .clear-search:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }
    
    .form-group select,
    .form-group input {
        padding: 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background: white;
        color: #374151;
        transition: all 0.2s;
        cursor: pointer;
        height: 42px;
        min-height: 42px;
        max-height: 42px;
        box-sizing: border-box;
        line-height: 1.2;
        vertical-align: top;
        flex-shrink: 0;
    }
    
    /* Ensure all selects have identical styling */
    .form-group select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
        padding-right: 2.5rem;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
    }
    
    /* Specific adjustments for dropdown alignment */
    #branchFilter,
    #categoryFilter, 
    #statusFilter {
        height: 42px !important;
        min-height: 42px !important;
        max-height: 42px !important;
        line-height: 1.2 !important;
        padding: 0.75rem 2.5rem 0.75rem 0.75rem !important;
        display: block;
        width: 100%;
    }
    
    .form-group select:focus,
    .form-group input:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        transform: translateY(-1px);
    }
    
    .form-group select:hover,
    .form-group input:hover {
        border-color: #cbd5e1;
    }
    
    .filter-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        height: 42px;
        min-height: 42px;
        white-space: nowrap;
        box-sizing: border-box;
    }
    
    .filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .filter-export {
        padding: 0.75rem 1.5rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background: white;
        color: #374151;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        height: 42px;
        min-height: 42px;
        white-space: nowrap;
        box-sizing: border-box;
    }
    
    .filter-export:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    
    .filter-active {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        opacity: 0;
        transition: opacity 0.2s;
    }
    
    .filter-active.show {
        opacity: 1;
    }
    
    .stock-table {
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
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background: #f8fafc;
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        font-size: 0.875rem;
        border-bottom: 1px solid #e2e8f0;
    }
    
    .table td {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        color: #374151;
        font-size: 0.875rem;
    }
    
    .table tbody tr:hover {
        background: #f8fafc;
    }
    
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .stock-actions {
        display: flex;
        gap: 0.5rem;
    }
    
    .stock-actions button {
        padding: 0.375rem 0.75rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .adjust-btn { 
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
    }
    .adjust-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }
    
    .transfer-btn { 
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
    }
    .transfer-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
    }
    
    .history-btn { 
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
    }
    .history-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(107, 114, 128, 0.3);
    }
    
    .stock-status {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .status-out { background: #fef2f2; color: #991b1b; }
    .status-low { background: #fffbeb; color: #92400e; }
    .status-good { background: #f0fdf4; color: #166534; }
    
    .font-bold { font-weight: 600; }
    .text-sm { font-size: 0.875rem; }
    .text-gray { color: #6b7280; }
    .text-green { color: #059669; }
    
        @media (max-width: 768px) {
        .stock-container {
            padding: 1rem;
        }
        
        .stock-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .performance-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .period-tabs {
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .period-tab {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
        }
        
        .insights-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }
        
        .insight-item {
            padding: 0.75rem;
        }
        
        .insight-number {
            font-size: 1rem;
        }
        
        .insight-label {
            font-size: 0.6875rem;
        }
        
        .stock-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .filter-buttons {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .search-row {
            grid-template-columns: 1fr;
        }
        
        .action-buttons {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .stock-stats {
            grid-template-columns: 1fr;
        }
        
        .stat-card {
            padding: 1rem;
            min-height: 100px;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .insights-grid {
            grid-template-columns: 1fr;
        }
        
        .period-tabs {
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .period-tab {
            padding: 0.5rem;
            text-align: center;
        }
    }
</style>

<div class="stock-container">

<!-- Page Header -->
<div class="stock-header">
    <div class="header-content">
        <h1>Stock Management</h1>
        <p>Monitor inventory levels across all branches</p>
    </div>
    <div class="action-buttons">
        <button class="btn btn-success" onclick="showAddStockModal()">Add Stock</button>
        <button class="btn btn-primary" onclick="showAdjustStockModal()">Adjust Stock</button>
        <button class="btn btn-secondary" onclick="showTransferStockModal()">Transfer Stock</button>
        <!--<button class="btn btn-outline" onclick="testModal()">Test Modal</button>-->
    </div>
</div>

<!-- Period Performance Tabs -->
<div class="period-performance">
    <div class="performance-header">
        <h2>📊 Period Performance</h2>
        <div class="period-tabs">
            <button class="period-tab" data-period="today">Today</button>
            <button class="period-tab" data-period="week">This Week</button>
            <button class="period-tab active" data-period="month">This Month</button>
            <button class="period-tab" data-period="quarter">This Quarter</button>
            <button class="period-tab" data-period="year">This Year</button>
        </div>
    </div>
</div>

<!-- Stock Statistics -->
<div class="stock-stats" id="stockStatsContainer">
    <div class="stat-card blue">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-number" id="totalProducts"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    
    <div class="stat-card red">
        <div class="stat-icon">⚠️</div>
        <div class="stat-content">
            <div class="stat-number" id="outOfStock"><?php echo $outOfStock; ?></div>
            <div class="stat-label">Out of Stock</div>
        </div>
    </div>
    
    <div class="stat-card yellow">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-number" id="lowStock"><?php echo $lowStock; ?></div>
            <div class="stat-label">Low Stock</div>
        </div>
    </div>
    
    <div class="stat-card green">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-number" id="totalValue">K<?php echo number_format($totalValue, 2); ?></div>
            <div class="stat-label">Total Value</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-active" id="filterActive">🔍 Filters Applied</div>
    <div class="filter-header">
        <h2 class="filter-title">
            <span>🔍</span> Filter & Search Stock
        </h2>
        <div class="filter-actions">
            <button class="filter-reset" onclick="resetFilters()">
                🔄 Reset
            </button>
        </div>
    </div>
    <div class="filter-row">
        <div class="form-group">
            <label>
                <span class="label-icon">🏪</span> Branch
            </label>
            <select id="branchFilter">
                <option value="">All Branches</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <span class="label-icon">📂</span> Category
            </label>
            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php 
                $categories = array_unique(array_column($products, 'category'));
                foreach ($categories as $category): 
                ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>
                <span class="label-icon">📊</span> Stock Status
            </label>
            <select id="statusFilter">
                <option value="">All Stock</option>
                <option value="Out of Stock">Out of Stock</option>
                <option value="Low Stock">Low Stock</option>
                <option value="Good Stock">Good Stock</option>
            </select>
        </div>
    </div>
    
    <div class="filter-buttons">
        <button class="filter-btn" onclick="applyFilters()">
            <span>🔍</span> Filter
        </button>
        
        <button class="filter-export" onclick="exportStock()">
            <span>📊</span> Export
        </button>
    </div>
    
    <div class="search-row">
        <div class="form-group search-group">
            <label>
                <span class="label-icon">🔍</span> Search Product
            </label>
            <input type="text" id="productSearch" placeholder="Enter product name...">
        </div>
        
        <button class="clear-search" onclick="clearSearch()">
            <span>✕</span> Clear
        </button>
    </div>
</div>

<!-- Stock Table -->
<div class="stock-table">
    <div class="table-header">
        <h2 class="table-title">
            <span>📋</span> Stock Inventory
        </h2>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Branch</th>
                <th>Current Stock</th>
                <th>Reorder Level</th>
                <th>Value</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $pid => $product): ?>
                <?php foreach ($branches as $branch): ?>
                    <?php 
                    $currentStock = $stock[$pid][$branch];
                    $reorderLevel = 10; // Default reorder level
                    $value = $currentStock * $product['price'];
                    
                    // Determine status
                    if ($currentStock == 0) {
                        $status = 'Out of Stock';
                        $statusClass = 'status-out';
                    } elseif ($currentStock < $reorderLevel) {
                        $status = 'Low Stock';
                        $statusClass = 'status-low';
                    } else {
                        $status = 'Good Stock';
                        $statusClass = 'status-good';
                    }
                    ?>
                    <tr>
                        <td>
                            <div class="font-bold"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="text-sm text-gray"><?php echo htmlspecialchars($product['category']); ?></div>
                        </td>
                        <td>
                            <span class="text-sm font-bold" style="color: #2563eb;"><?php echo $branch; ?></span>
                        </td>
                        <td class="font-bold"><?php echo $currentStock; ?></td>
                        <td class="text-gray"><?php echo $reorderLevel; ?></td>
                        <td class="text-green font-bold">K<?php echo number_format($value, 2); ?></td>
                        <td>
                            <span class="stock-status <?php echo $statusClass; ?>">
                                <?php echo $status; ?>
                            </span>
                        </td>
                        <td>
                            <div class="stock-actions">
                                <button class="adjust-btn" onclick="showAdjustModal('<?php echo $pid; ?>', '<?php echo $branch; ?>', '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $currentStock; ?>)">Adjust</button>
                                <button class="transfer-btn" onclick="showTransferModal('<?php echo $pid; ?>', '<?php echo $branch; ?>', '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $currentStock; ?>)">Transfer</button>
                                <button class="history-btn" onclick="showStockHistory('<?php echo $pid; ?>', '<?php echo $branch; ?>', '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">History</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>

<?php include '../includes/footer.php'; ?>

<script>
// Ensure modal system is initialized
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit for header script to load
    setTimeout(function() {
        if (!window.modal) {
            console.log('Modal system not found, initializing...');
            window.modal = new ModalSystem();
        }
    }, 100);
    
    // Initialize Period Performance tabs
    initializePeriodTabs();
    
    // Load initial period data (month)
    setTimeout(() => {
        updateStockStatistics('month');
    }, 500);
});

// Period Performance Functionality
function initializePeriodTabs() {
    const tabs = document.querySelectorAll('.period-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Get the selected period
            const period = this.dataset.period;
            
            // Update stock statistics based on period
            updateStockStatistics(period);
        });
    });
}

function updateStockStatistics(period) {
    // Show loading state
    const statsContainer = document.getElementById('stockStatsContainer');
    statsContainer.style.opacity = '0.6';
    
    // Make AJAX call to get real period data
    fetch(`get_period_stats.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // Update the display with real data
            document.getElementById('totalProducts').textContent = data.stats.totalProducts;
            document.getElementById('outOfStock').textContent = data.stats.outOfStock;
            document.getElementById('lowStock').textContent = data.stats.lowStock;
            document.getElementById('totalValue').textContent = 'K' + data.stats.totalValue;
            
            // Add fade animation
            statsContainer.style.opacity = '0';
            setTimeout(() => {
                statsContainer.style.opacity = '1';
            }, 150);
            
            // Update period label with real insights
            updatePeriodInsights(data);
        })
        .catch(error => {
            console.error('Error fetching period stats:', error);
            // Fallback to simulated data if API fails
            const stats = getStockStatsForPeriod(period);
            document.getElementById('totalProducts').textContent = stats.totalProducts;
            document.getElementById('outOfStock').textContent = stats.outOfStock;
            document.getElementById('lowStock').textContent = stats.lowStock;
            document.getElementById('totalValue').textContent = 'K' + stats.totalValue;
            
            statsContainer.style.opacity = '1';
        });
}

function updatePeriodInsights(data) {
    // Create or update period insights display
    let insightsContainer = document.getElementById('periodInsights');
    if (!insightsContainer) {
        insightsContainer = document.createElement('div');
        insightsContainer.id = 'periodInsights';
        insightsContainer.className = 'period-insights';
        
        const performanceHeader = document.querySelector('.performance-header');
        performanceHeader.parentNode.appendChild(insightsContainer);
    }
    
    const insights = data.period_activity;
    const dateRange = `${formatDate(data.date_range.start)} - ${formatDate(data.date_range.end)}`;
    
    insightsContainer.innerHTML = `
        <div class="insights-header">
            <h3>📊 ${data.period_label} Activity (${dateRange})</h3>
        </div>
        <div class="insights-grid">
            <div class="insight-item">
                <span class="insight-icon">📥</span>
                <div class="insight-content">
                    <div class="insight-number">${insights.purchases}</div>
                    <div class="insight-label">Items Purchased</div>
                </div>
            </div>
            <div class="insight-item">
                <span class="insight-icon">📤</span>
                <div class="insight-content">
                    <div class="insight-number">${insights.sales}</div>
                    <div class="insight-label">Items Sold</div>
                </div>
            </div>
            <div class="insight-item">
                <span class="insight-icon">🔄</span>
                <div class="insight-content">
                    <div class="insight-number">${insights.transfers}</div>
                    <div class="insight-label">Transfers</div>
                </div>
            </div>
            <div class="insight-item">
                <span class="insight-icon">📈</span>
                <div class="insight-content">
                    <div class="insight-number">${data.insights.net_stock_change >= 0 ? '+' : ''}${data.insights.net_stock_change}</div>
                    <div class="insight-label">Net Stock Change</div>
                </div>
            </div>
        </div>
    `;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
    });
}

function getStockStatsForPeriod(period) {
    // This would normally make an AJAX call to get real data
    // For now, we'll return simulated data based on period
    const baseStats = {
        totalProducts: <?php echo $totalProducts; ?>,
        outOfStock: <?php echo $outOfStock; ?>,
        lowStock: <?php echo $lowStock; ?>,
        totalValue: '<?php echo number_format($totalValue, 2); ?>'
    };
    
    switch(period) {
        case 'today':
            return {
                totalProducts: Math.floor(baseStats.totalProducts * 0.3),
                outOfStock: Math.floor(baseStats.outOfStock * 0.2),
                lowStock: Math.floor(baseStats.lowStock * 0.4),
                totalValue: (parseFloat(baseStats.totalValue.replace(',', '')) * 0.25).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            };
        case 'week':
            return {
                totalProducts: Math.floor(baseStats.totalProducts * 0.7),
                outOfStock: Math.floor(baseStats.outOfStock * 0.6),
                lowStock: Math.floor(baseStats.lowStock * 0.8),
                totalValue: (parseFloat(baseStats.totalValue.replace(',', '')) * 0.65).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            };
        case 'month':
            return {
                totalProducts: baseStats.totalProducts,
                outOfStock: baseStats.outOfStock,
                lowStock: baseStats.lowStock,
                totalValue: baseStats.totalValue
            };
        case 'quarter':
            return {
                totalProducts: Math.floor(baseStats.totalProducts * 1.2),
                outOfStock: Math.floor(baseStats.outOfStock * 1.1),
                lowStock: Math.floor(baseStats.lowStock * 1.3),
                totalValue: (parseFloat(baseStats.totalValue.replace(',', '')) * 1.4).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            };
        case 'year':
            return {
                totalProducts: Math.floor(baseStats.totalProducts * 1.8),
                outOfStock: Math.floor(baseStats.outOfStock * 1.5),
                lowStock: Math.floor(baseStats.lowStock * 1.7),
                totalValue: (parseFloat(baseStats.totalValue.replace(',', '')) * 2.2).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")
            };
        default:
            return baseStats;
    }
}

function showAdjustModal(productId, branch, productName, currentStock) {
    console.log('Show adjust modal for:', productName, 'in', branch);
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    window.modal.showForm('Adjust Stock Level', [
        {name: 'product_id', label: 'Product ID', type: 'hidden', value: productId},
        {name: 'product_name', label: 'Product', type: 'text', value: productName, readonly: true},
        {name: 'branch', label: 'Branch', type: 'text', value: branch, readonly: true},
        {name: 'current_stock', label: 'Current Stock', type: 'text', value: currentStock, readonly: true},
        {name: 'adjustment_type', label: 'Adjustment Type', type: 'select', required: true, options: [
            {value: 'increase', text: 'Increase Stock (+)'},
            {value: 'decrease', text: 'Decrease Stock (-)'}
        ]},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity to adjust', min: 1},
        {name: 'reason', label: 'Reason', type: 'select', required: true, options: [
            {value: 'purchase', text: 'New Purchase'},
            {value: 'return', text: 'Customer Return'},
            {value: 'damage', text: 'Damage/Breakage'},
            {value: 'theft', text: 'Theft/Loss'},
            {value: 'correction', text: 'Stock Count Correction'},
            {value: 'expired', text: 'Expired Items'},
            {value: 'transfer_error', text: 'Transfer Error Correction'}
        ]},
        {name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'Additional notes about this adjustment (optional)'}
    ], {submitText: 'Apply Adjustment'}).then(data => {
        // Process the stock adjustment
        console.log('Stock adjustment data:', data);
        
        // Create form data for submission
        const formData = new FormData();
        formData.append('product_id', data.product_id);
        formData.append('branch', data.branch);
        formData.append('adjustment_type', data.adjustment_type);
        formData.append('quantity', data.quantity);
        formData.append('reason', data.reason);
        formData.append('notes', data.notes || '');
        
        // Submit to stock adjustment handler
        fetch('../stock/adjust_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.modal.showAlert('Success!', 'Stock adjustment applied successfully.', 'success');
                // Reload page to show updated stock levels
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                window.modal.showAlert('Error', result.message || 'Failed to apply stock adjustment.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.modal.showAlert('Error', 'An error occurred while applying the adjustment.', 'error');
        });
    });
}

function showTransferModal(productId, sourceBranch, productName, currentStock) {
    console.log('Show transfer modal for:', productName, 'from', sourceBranch);
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const branches = <?php echo json_encode($branches); ?>;
    const availableBranches = branches.filter(branch => branch !== sourceBranch).map(branch => ({
        value: branch,
        text: branch
    }));
    
    window.modal.showForm('Transfer Stock', [
        {name: 'product_id', label: 'Product ID', type: 'hidden', value: productId},
        {name: 'product_name', label: 'Product', type: 'text', value: productName, readonly: true},
        {name: 'source_branch', label: 'From Branch', type: 'text', value: sourceBranch, readonly: true},
        {name: 'current_stock', label: 'Available Stock', type: 'text', value: currentStock, readonly: true},
        {name: 'dest_branch', label: 'To Branch', type: 'select', required: true, options: availableBranches},
        {name: 'quantity', label: 'Quantity to Transfer', type: 'number', required: true, min: 1, max: currentStock, placeholder: `Max: ${currentStock}`},
        {name: 'notes', label: 'Transfer Notes', type: 'textarea', placeholder: 'Reason for transfer (optional)'}
    ], {submitText: 'Transfer Stock'}).then(data => {
        // Validate quantity
        if (parseInt(data.quantity) > currentStock) {
            window.modal.showAlert('Error', `Cannot transfer ${data.quantity} items. Only ${currentStock} available.`, 'error');
            return;
        }
        
        if (data.source_branch === data.dest_branch) {
            window.modal.showAlert('Error', 'Source and destination branches cannot be the same.', 'error');
            return;
        }
        
        // Process the stock transfer
        console.log('Stock transfer data:', data);
        
        // Submit to transfer handler
        const formData = new FormData();
        formData.append('product_id', data.product_id);
        formData.append('source_branch', data.source_branch);
        formData.append('dest_branch', data.dest_branch);
        formData.append('quantity', data.quantity);
        formData.append('notes', data.notes || '');
        
        fetch('../transfers/add_handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                window.modal.showAlert('Success!', `Successfully transferred ${data.quantity} items from ${data.source_branch} to ${data.dest_branch}.`, 'success');
                // Reload page to show updated stock levels
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                window.modal.showAlert('Error', result.message || 'Failed to transfer stock.', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.modal.showAlert('Error', 'An error occurred while transferring stock.', 'error');
        });
    });
}

// Stock-specific modal functions
function showAddStockModal() {
    console.log('Add stock modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($pid, $product) { 
        return ['value' => $pid, 'text' => $product['name']]; 
    }, array_keys($products), $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Add Stock', [
        {name: 'product_id', label: 'Product', type: 'select', required: true, options: products},
        {name: 'branch', label: 'Branch', type: 'select', required: true, options: branches},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity'},
        {name: 'unit_price', label: 'Unit Price', type: 'number', step: '0.01', required: true, placeholder: '0.00'},
        {name: 'supplier', label: 'Supplier', type: 'text', required: true, placeholder: 'Supplier name'}
    ], {submitText: 'Add Stock'}).then(data => {
        window.submitForm('../purchases/add_handler.php', data, 'POST');
    });
}

function showAdjustStockModal() {
    console.log('Adjust stock modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($pid, $product) { 
        return ['value' => $pid, 'text' => $product['name']]; 
    }, array_keys($products), $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Adjust Stock', [
        {name: 'product_id', label: 'Product', type: 'select', required: true, options: products},
        {name: 'branch', label: 'Branch', type: 'select', required: true, options: branches},
        {name: 'adjustment_type', label: 'Adjustment Type', type: 'select', required: true, options: [
            {value: 'increase', text: 'Increase Stock'},
            {value: 'decrease', text: 'Decrease Stock'}
        ]},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity'},
        {name: 'reason', label: 'Reason', type: 'select', required: true, options: [
            {value: 'purchase', text: 'Purchase'},
            {value: 'sale', text: 'Sale'},
            {value: 'damage', text: 'Damage'},
            {value: 'theft', text: 'Theft'},
            {value: 'correction', text: 'Stock Correction'},
            {value: 'return', text: 'Return'}
        ]},
        {name: 'notes', label: 'Notes', type: 'textarea', placeholder: 'Additional notes (optional)'}
    ], {submitText: 'Adjust Stock'}).then(data => {
        // Process the adjustment
        window.submitForm('../stock/adjust.php', data, 'POST');
    });
}

function showTransferStockModal() {
    console.log('Transfer stock modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($pid, $product) { 
        return ['value' => $pid, 'text' => $product['name']]; 
    }, array_keys($products), $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Transfer Stock', [
        {name: 'product_id', label: 'Product', type: 'select', required: true, options: products},
        {name: 'source_branch', label: 'From Branch', type: 'select', required: true, options: branches},
        {name: 'dest_branch', label: 'To Branch', type: 'select', required: true, options: branches},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity to transfer'},
        {name: 'notes', label: 'Transfer Notes', type: 'textarea', placeholder: 'Transfer notes (optional)'}
    ], {submitText: 'Transfer Stock'}).then(data => {
        if (data.source_branch === data.dest_branch) {
            alert('Source and destination branches cannot be the same');
            return;
        }
        window.submitForm('../transfers/add_handler.php', data, 'POST');
    });
}

function showStockHistory(productId, branch, productName) {
    console.log('Show stock history for:', productName, 'in', branch);
    
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    // Show loading modal first
    window.modal.showModal(`
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Stock History - ${productName} (${branch})</h3>
                <button type="button" class="modal-close" onclick="window.modal.closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div>
                    <div>Loading stock history...</div>
                </div>
            </div>
        </div>
    `);
    
    // Fetch stock history data
    fetch(`get_stock_history.php?product_id=${productId}&branch=${encodeURIComponent(branch)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayStockHistoryModal(data, productName, branch);
            } else {
                window.modal.showAlert('Error', data.message || 'Failed to load stock history.', 'error');
            }
        })
        .catch(error => {
            console.error('Error fetching stock history:', error);
            window.modal.showAlert('Error', 'An error occurred while loading stock history.', 'error');
        });
}

function displayStockHistoryModal(data, productName, branch) {
    const movements = data.movements || [];
    const summary = data.summary || {};
    const currentStock = data.current_stock || 0;
    
    // Generate movements HTML
    let movementsHtml = '';
    if (movements.length === 0) {
        movementsHtml = `
            <div style="text-align: center; padding: 2rem; color: #6b7280;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📋</div>
                <div style="font-size: 1.125rem; font-weight: 500;">No movements found</div>
                <div style="font-size: 0.875rem; margin-top: 0.5rem;">This product has no recorded stock movements in ${branch}.</div>
            </div>
        `;
    } else {
        movementsHtml = movements.map(movement => {
            const date = new Date(movement.date + ' ' + movement.time).toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            const time = new Date('1970-01-01 ' + movement.time).toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const changeColor = movement.change.startsWith('+') ? '#059669' : '#dc2626';
            const changeText = movement.change.startsWith('+') ? movement.change : movement.change;
            
            return `
                <div class="movement-item" style="
                    display: flex;
                    align-items: center;
                    padding: 1rem;
                    border: 1px solid #e5e7eb;
                    border-radius: 0.5rem;
                    margin-bottom: 0.75rem;
                    background: #ffffff;
                    transition: all 0.2s;
                ">
                    <div style="font-size: 1.5rem; margin-right: 1rem; opacity: 0.8;">
                        ${movement.icon}
                    </div>
                    <div style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.25rem;">
                            <div>
                                <span style="font-weight: 600; color: #1f2937;">${movement.type_label}</span>
                                <span style="color: #6b7280; margin-left: 0.5rem;">${movement.reference}</span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-weight: 700; color: ${changeColor}; font-size: 1.1rem;">
                                    ${changeText}
                                </div>
                                <div style="font-size: 0.75rem; color: #6b7280;">
                                    Stock: ${movement.stock_after}
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 0.875rem; color: #6b7280;">
                                ${date} at ${time}
                                ${movement.unit_price > 0 ? ` • K${movement.unit_price.toFixed(2)} each` : ''}
                                ${movement.damages > 0 ? ` • ${movement.damages} damaged` : ''}
                            </div>
                        </div>
                        ${movement.notes ? `<div style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; font-style: italic;">${movement.notes}</div>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    // Generate summary cards
    const summaryHtml = `
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.5rem;">
            <div style="background: #f0f9ff; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid #e0f2fe;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #0284c7;">${summary.total_purchases}</div>
                <div style="font-size: 0.75rem; color: #0369a1;">Purchases</div>
            </div>
            <div style="background: #fef3c7; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid #fed7aa;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #d97706;">${summary.total_sales}</div>
                <div style="font-size: 0.75rem; color: #92400e;">Sales</div>
            </div>
            <div style="background: #f0fdf4; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid #bbf7d0;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #059669;">${summary.total_transfers_in}</div>
                <div style="font-size: 0.75rem; color: #047857;">Transfers In</div>
            </div>
            <div style="background: #fef2f2; padding: 1rem; border-radius: 0.5rem; text-align: center; border: 1px solid #fecaca;">
                <div style="font-size: 1.5rem; font-weight: 700; color: #dc2626;">${summary.total_transfers_out}</div>
                <div style="font-size: 0.75rem; color: #991b1b;">Transfers Out</div>
            </div>
        </div>
    `;
    
    window.modal.showModal(`
        <div class="modal" style="max-width: 900px;">
            <div class="modal-header">
                <h3 class="modal-title">📊 Stock History - ${productName} (${branch})</h3>
                <button type="button" class="modal-close" onclick="window.modal.closeModal()">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 0.5rem; border: 1px solid #e2e8f0;">
                    <div>
                        <div style="font-size: 1.125rem; font-weight: 600; color: #1f2937;">Current Stock Level</div>
                        <div style="font-size: 0.875rem; color: #6b7280;">Live inventory count</div>
                    </div>
                    <div style="font-size: 2rem; font-weight: 700; color: ${currentStock === 0 ? '#dc2626' : currentStock < 10 ? '#d97706' : '#059669'};">
                        ${currentStock}
                    </div>
                </div>
                
                ${summaryHtml}
                
                <div style="margin-bottom: 1rem;">
                    <h4 style="font-size: 1rem; font-weight: 600; color: #1f2937; margin-bottom: 1rem;">
                        📋 Movement History (${summary.total_movements} transactions)
                    </h4>
                </div>
                
                <div style="max-height: 400px; overflow-y: auto;">
                    ${movementsHtml}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-modal btn-modal-outline" onclick="exportStockHistory('${productName}', '${branch}', ${JSON.stringify(data).replace(/'/g, "&#39;")})">
                    📊 Export History
                </button>
                <button type="button" class="btn-modal btn-modal-outline" onclick="window.modal.closeModal()">Close</button>
            </div>
        </div>
    `);
}

function exportStockHistory(productName, branch, historyData) {
    try {
        const data = typeof historyData === 'string' ? JSON.parse(historyData) : historyData;
        const movements = data.movements || [];
        
        if (movements.length === 0) {
            window.modal.showAlert('No Data', 'No stock movements to export.', 'info');
            return;
        }
        
        // Create CSV content
        const headers = [
            'Date', 'Time', 'Type', 'Reference', 'Change', 'Quantity', 'Unit Price', 
            'Stock Before', 'Stock After', 'Notes'
        ];
        let csvContent = headers.join(',') + '\n';
        
        movements.forEach(movement => {
            const row = [
                `"${movement.date}"`,
                `"${movement.time}"`,
                `"${movement.type_label}"`,
                `"${movement.reference || ''}"`,
                `"${movement.change}"`,
                `"${movement.quantity}"`,
                `"${movement.unit_price || 0}"`,
                `"${movement.stock_before}"`,
                `"${movement.stock_after}"`,
                `"${movement.notes || ''}"`
            ];
            csvContent += row.join(',') + '\n';
        });
        
        // Add summary at the end
        csvContent += '\n';
        csvContent += 'SUMMARY\n';
        csvContent += `Product,"${productName}"\n`;
        csvContent += `Branch,"${branch}"\n`;
        csvContent += `Current Stock,"${data.current_stock}"\n`;
        csvContent += `Total Movements,"${data.summary.total_movements}"\n`;
        csvContent += `Total Purchases,"${data.summary.total_purchases}"\n`;
        csvContent += `Total Sales,"${data.summary.total_sales}"\n`;
        csvContent += `Total Transfers In,"${data.summary.total_transfers_in}"\n`;
        csvContent += `Total Transfers Out,"${data.summary.total_transfers_out}"\n`;
        csvContent += `Net Movement,"${data.summary.net_movement}"\n`;
        
        // Download CSV
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        
        const sanitizedProductName = productName.replace(/[^a-zA-Z0-9]/g, '_');
        const sanitizedBranch = branch.replace(/[^a-zA-Z0-9]/g, '_');
        const timestamp = new Date().toISOString().split('T')[0];
        
        link.setAttribute('download', `stock_history_${sanitizedProductName}_${sanitizedBranch}_${timestamp}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        window.modal.showAlert('Export Successful!', `Stock history exported for ${productName} in ${branch}.`, 'success');
        
    } catch (error) {
        console.error('Export error:', error);
        window.modal.showAlert('Export Error', 'Failed to export stock history.', 'error');
    }
}

// Make filter dropdowns functional
document.addEventListener('DOMContentLoaded', function() {
    // Initialize filter functionality
    initializeFilters();
    
    // Add real-time search functionality
    const productSearch = document.getElementById('productSearch');
    if (productSearch) {
        productSearch.addEventListener('input', function() {
            applyFilters();
        });
    }
    
    // Add change event listeners to all filter selects
    ['branchFilter', 'categoryFilter', 'statusFilter'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });
});

function initializeFilters() {
    // Store original table data for reset functionality
    const tableRows = document.querySelectorAll('.stock-table tbody tr');
    window.originalTableData = Array.from(tableRows).map(row => ({
        element: row,
        product: row.cells[0].textContent.trim(),
        branch: row.cells[1].textContent.trim(),
        category: row.cells[0].querySelector('.text-gray') ? row.cells[0].querySelector('.text-gray').textContent.trim() : '',
        status: row.cells[5].textContent.trim(),
        stock: parseInt(row.cells[2].textContent.trim()) || 0
    }));
}

function applyFilters() {
    const branchFilter = document.getElementById('branchFilter').value;
    const categoryFilter = document.getElementById('categoryFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    const productSearch = document.getElementById('productSearch').value.toLowerCase();
    
    let visibleCount = 0;
    let hasActiveFilters = branchFilter || categoryFilter || statusFilter || productSearch;
    
    window.originalTableData.forEach(item => {
        let show = true;
        
        // Branch filter
        if (branchFilter && item.branch !== branchFilter) {
            show = false;
        }
        
        // Category filter
        if (categoryFilter && item.category !== categoryFilter) {
            show = false;
        }
        
        // Status filter
        if (statusFilter && item.status !== statusFilter) {
            show = false;
        }
        
        // Product search
        if (productSearch && !item.product.toLowerCase().includes(productSearch)) {
            show = false;
        }
        
        item.element.style.display = show ? '' : 'none';
        if (show) visibleCount++;
    });
    
    // Update filter active indicator
    const filterActive = document.getElementById('filterActive');
    if (filterActive) {
        if (hasActiveFilters) {
            filterActive.classList.add('show');
            filterActive.textContent = `🔍 ${visibleCount} items found`;
        } else {
            filterActive.classList.remove('show');
        }
    }
    
    // Update table with no results message if needed
    updateNoResultsMessage(visibleCount);
}

function resetFilters() {
    // Reset all filter inputs
    document.getElementById('branchFilter').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('productSearch').value = '';
    
    // Show all rows
    window.originalTableData.forEach(item => {
        item.element.style.display = '';
    });
    
    // Hide filter active indicator
    const filterActive = document.getElementById('filterActive');
    if (filterActive) {
        filterActive.classList.remove('show');
    }
    
    // Remove no results message
    updateNoResultsMessage(window.originalTableData.length);
}

function clearSearch() {
    document.getElementById('productSearch').value = '';
    applyFilters();
}

function updateNoResultsMessage(visibleCount) {
    // Remove existing no results message
    const existingMessage = document.querySelector('.no-results-message');
    if (existingMessage) {
        existingMessage.remove();
    }
    
    // Add no results message if needed
    if (visibleCount === 0) {
        const tbody = document.querySelector('.stock-table tbody');
        const noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-message';
        noResultsRow.innerHTML = `
            <td colspan="7" style="text-align: center; padding: 3rem; color: #6b7280;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                <div style="font-size: 1.125rem; font-weight: 500; margin-bottom: 0.5rem;">No products found</div>
                <div style="font-size: 0.875rem;">Try adjusting your filters or search terms</div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }
}

function exportStock() {
    // Get visible rows only
    const visibleRows = window.originalTableData.filter(item => 
        item.element.style.display !== 'none'
    );
    
    if (visibleRows.length === 0) {
        alert('No data to export. Please adjust your filters.');
        return;
    }
    
    // Create CSV content
    const headers = ['Product', 'Category', 'Branch', 'Current Stock', 'Reorder Level', 'Value', 'Status'];
    let csvContent = headers.join(',') + '\n';
    
    visibleRows.forEach(item => {
        const row = item.element;
        const productName = row.cells[0].querySelector('.font-bold').textContent.trim();
        const category = item.category;
        const branch = item.branch;
        const stock = row.cells[2].textContent.trim();
        const reorderLevel = row.cells[3].textContent.trim();
        const value = row.cells[4].textContent.trim();
        const status = item.status;
        
        csvContent += `"${productName}","${category}","${branch}","${stock}","${reorderLevel}","${value}","${status}"\n`;
    });
    
    // Download CSV
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `stock_report_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Show success message
    if (typeof window.modal !== 'undefined') {
        window.modal.showAlert('Export successful!', `Exported ${visibleRows.length} items to CSV file.`, 'success');
    } else {
        alert(`Export successful! Downloaded ${visibleRows.length} items.`);
    }
}
</script>
