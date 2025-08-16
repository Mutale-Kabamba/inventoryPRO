<?php
session_start();
require_once '../includes/auth.php';
require_once '../config/db.php';
requireLogin();

// Get dashboard data
$conn = getDbConnection();

// Get total products
$productResult = $conn->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $productResult ? $productResult->fetch_assoc()['total'] : 0;

// Get today's sales
$todaySalesResult = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE DATE(sale_date) = CURDATE()");
$todaySales = $todaySalesResult ? $todaySalesResult->fetch_assoc() : ['count' => 0, 'revenue' => 0];

// Get today's expenses
$todayExpensesResult = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) = CURDATE()");
$todayExpenses = $todayExpensesResult ? $todayExpensesResult->fetch_assoc()['total'] : 0;

// Calculate net cash today
$netCash = $todaySales['revenue'] - $todayExpenses;

// Get today's activities count (sales + purchases + transfers + expenses)
$activitiesResult = $conn->query("
    SELECT 
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) = CURDATE()) +
    (SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) = CURDATE()) +
    (SELECT COUNT(*) FROM transfers WHERE DATE(transfer_date) = CURDATE()) +
    (SELECT COUNT(*) FROM expenses WHERE DATE(expense_date) = CURDATE()) as total
");
$todayActivities = $activitiesResult ? $activitiesResult->fetch_assoc()['total'] : 0;

// Get period-based statistics
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');
$quarter_start = date('Y-m-d', strtotime('first day of january this year'));
$year_start = date('Y-01-01');

// Week statistics
$weekSalesResult = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE DATE(sale_date) >= '$week_start'");
$weekSales = $weekSalesResult ? $weekSalesResult->fetch_assoc() : ['count' => 0, 'revenue' => 0];

$weekExpensesResult = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) >= '$week_start'");
$weekExpenses = $weekExpensesResult ? $weekExpensesResult->fetch_assoc()['total'] : 0;

$weekActivitiesResult = $conn->query("
    SELECT 
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) >= '$week_start') +
    (SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) >= '$week_start') +
    (SELECT COUNT(*) FROM transfers WHERE DATE(transfer_date) >= '$week_start') +
    (SELECT COUNT(*) FROM expenses WHERE DATE(expense_date) >= '$week_start') as total
");
$weekActivities = $weekActivitiesResult ? $weekActivitiesResult->fetch_assoc()['total'] : 0;

// Month statistics
$monthSalesResult = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE DATE(sale_date) >= '$month_start'");
$monthSales = $monthSalesResult ? $monthSalesResult->fetch_assoc() : ['count' => 0, 'revenue' => 0];

$monthExpensesResult = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) >= '$month_start'");
$monthExpenses = $monthExpensesResult ? $monthExpensesResult->fetch_assoc()['total'] : 0;

$monthActivitiesResult = $conn->query("
    SELECT 
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) >= '$month_start') +
    (SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) >= '$month_start') +
    (SELECT COUNT(*) FROM transfers WHERE DATE(transfer_date) >= '$month_start') +
    (SELECT COUNT(*) FROM expenses WHERE DATE(expense_date) >= '$month_start') as total
");
$monthActivities = $monthActivitiesResult ? $monthActivitiesResult->fetch_assoc()['total'] : 0;

// Quarter statistics
$quarterSalesResult = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE DATE(sale_date) >= '$quarter_start'");
$quarterSales = $quarterSalesResult ? $quarterSalesResult->fetch_assoc() : ['count' => 0, 'revenue' => 0];

$quarterExpensesResult = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) >= '$quarter_start'");
$quarterExpenses = $quarterExpensesResult ? $quarterExpensesResult->fetch_assoc()['total'] : 0;

$quarterActivitiesResult = $conn->query("
    SELECT 
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) >= '$quarter_start') +
    (SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) >= '$quarter_start') +
    (SELECT COUNT(*) FROM transfers WHERE DATE(transfer_date) >= '$quarter_start') +
    (SELECT COUNT(*) FROM expenses WHERE DATE(expense_date) >= '$quarter_start') as total
");
$quarterActivities = $quarterActivitiesResult ? $quarterActivitiesResult->fetch_assoc()['total'] : 0;

// Year statistics
$yearSalesResult = $conn->query("SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as revenue FROM sales WHERE DATE(sale_date) >= '$year_start'");
$yearSales = $yearSalesResult ? $yearSalesResult->fetch_assoc() : ['count' => 0, 'revenue' => 0];

$yearExpensesResult = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM expenses WHERE DATE(expense_date) >= '$year_start'");
$yearExpenses = $yearExpensesResult ? $yearExpensesResult->fetch_assoc()['total'] : 0;

$yearActivitiesResult = $conn->query("
    SELECT 
    (SELECT COUNT(*) FROM sales WHERE DATE(sale_date) >= '$year_start') +
    (SELECT COUNT(*) FROM purchases WHERE DATE(purchase_date) >= '$year_start') +
    (SELECT COUNT(*) FROM transfers WHERE DATE(transfer_date) >= '$year_start') +
    (SELECT COUNT(*) FROM expenses WHERE DATE(expense_date) >= '$year_start') as total
");
$yearActivities = $yearActivitiesResult ? $yearActivitiesResult->fetch_assoc()['total'] : 0;

// Set page variables for header
$pageTitle = 'Dashboard - LedgerLink Inventory';
$currentPage = 'dashboard';
?>
<?php include '../includes/header.php'; ?>
<!-- Custom Dashboard Styles -->
<link rel="stylesheet" href="modal.css">
<style>
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }
    
    .welcome {
        background: #ffffff;
        border-radius: 1rem;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
    }
    
    .welcome h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.5rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .welcome p {
        color: #64748b;
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }
    
    .welcome .date {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .stats-grid {
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
    
    .stat-card.products { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .stat-card.sales { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .stat-card.activity { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
    }
    .stat-card.cash { 
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
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
        line-height: 1;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        color: white;
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .stat-subtitle {
        font-size: 12px;
        opacity: 0.8;
        color: white;
        font-weight: 400;
    }
    
    .revenue-expense-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 3rem;
    }
    
    .revenue-card {
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 16px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .expense-card {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border-radius: 16px;
        padding: 24px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        transition: transform 0.2s ease;
    }
    
    .revenue-card:hover,
    .expense-card:hover {
        transform: translateY(-2px);
    }
    
    .revenue-card::before,
    .expense-card::before {
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
    
    .revenue-card h3,
    .expense-card h3 {
        font-size: 16px;
        font-weight: 600;
        color: white;
        margin-bottom: 8px;
        opacity: 0.9;
    }
    
    .revenue-card .value,
    .expense-card .value {
        font-size: 32px;
        font-weight: 700;
        color: white;
        margin-bottom: 4px;
        line-height: 1;
    }
    
    .revenue-card .subtitle,
    .expense-card .subtitle {
        font-size: 14px;
        color: white;
        opacity: 0.8;
        font-weight: 400;
    }
    
    .section-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
        margin-top: 0;
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
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-bottom: 3rem;
    }
    
    .management-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
    }
    
    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
        font-weight: 500;
        transition: all 0.2s;
        background: white;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        font-size: 1rem;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }
    
    .action-btn.blue {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border: none;
    }
    
    .action-btn.green {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
    }
    
    .action-btn.purple {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: white;
        border: none;
    }
    
    .action-btn.red {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        border: none;
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .revenue-expense-grid {
            grid-template-columns: 1fr;
        }
        
        .actions-grid,
        .management-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    /* Advanced Mini POS Styles */
    .dashboard-mini-pos {
        max-width: 900px;
        width: 90vw;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .pos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem 4rem 1.5rem 1.5rem; /* Adjust padding */
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.75rem 0.75rem 0 0;
        gap: 1rem;
        position: relative;
    }
    
    .pos-info h3 {
        margin: 0 0 0.25rem 0;
        font-size: 1.4rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .pos-branch {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.75rem;
        background: rgba(255, 255, 255, 0.1);
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        backdrop-filter: blur(8px);
    }
    
    .pos-branch label {
        font-size: 0.9rem;
        opacity: 0.95;
        font-weight: 500;
        min-width: fit-content;
    }
    
    .pos-branch select {
        padding: 0.4rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid rgba(255, 255, 255, 0.3);
        background: rgba(255, 255, 255, 0.2);
        color: white;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        backdrop-filter: blur(4px);
        min-width: 140px;
    }
    
    .pos-branch select:hover {
        background: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.4);
    }
    
    .pos-branch select:focus {
        outline: none;
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
    }
    
    .pos-total {
        text-align: right;
        min-width: 200px; /* Increase minimum width */
        flex-shrink: 0; /* Prevent shrinking */
        padding-right: 1rem; /* Add some padding from the edge */
    }
    
    .total-breakdown {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }
    
    .subtotal-row,
    .discount-row {
        display: flex;
        justify-content: space-between;
        gap: 2rem;
        margin-bottom: 0.25rem;
    }
    
    .total-amount {
        font-size: 1.5rem;
        font-weight: bold;
        color: #fbbf24;
    }
    
    .pos-product-select {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .pos-product-select select {
        width: 100%;
        padding: 0.875rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 1rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .pos-product-select select:hover {
        border-color: #9ca3af;
        background: #f9fafb;
    }
    
    .pos-product-select select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        background: #fefefe;
    }
    
    .product-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        max-height: 200px;
        overflow-y: auto;
        display: none;
    }
    
    .suggestion-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        cursor: pointer;
        transition: background-color 0.2s;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .suggestion-item:hover {
        background-color: #f8fafc;
    }
    
    .suggestion-item:last-child {
        border-bottom: none;
    }
    
    .product-name {
        font-weight: 500;
        color: #374151;
    }
    
    .product-price {
        color: #059669;
        font-weight: 600;
    }
    
    .no-results {
        color: #6b7280;
        font-style: italic;
        justify-content: center;
    }
    
    .pos-cart {
        background: #f8fafc;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .cart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .cart-header h4 {
        margin: 0;
        color: #374151;
        font-size: 1.1rem;
    }
    
    .clear-cart-btn {
        padding: 0.375rem 0.75rem;
        background: #ef4444;
        color: white;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .clear-cart-btn:hover:not(:disabled) {
        background: #dc2626;
    }
    
    .clear-cart-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .cart-items {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .empty-cart {
        text-align: center;
        color: #6b7280;
        padding: 2rem;
        font-style: italic;
    }
    
    .cart-item {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 1rem;
        align-items: center;
        background: white;
        padding: 0.75rem;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .item-info {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .item-name {
        font-weight: 500;
        color: #374151;
    }
    
    .item-price {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .item-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .qty-btn {
        width: 2rem;
        height: 2rem;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .qty-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .qty-input {
        width: 4rem;
        text-align: center;
        padding: 0.25rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
    }
    
    .remove-btn {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .remove-btn:hover {
        background: #fee2e2;
    }
    
    .item-total {
        font-weight: 600;
        color: #059669;
        text-align: right;
    }
    
    .pos-discount {
        background: #fefce8;
        border: 1px solid #fde047;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .discount-header h4 {
        margin: 0 0 0.75rem 0;
        color: #a16207;
        font-size: 1.1rem;
    }
    
    .discount-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }
    
    .discount-controls select,
    .discount-controls input {
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
    }
    
    .discount-controls .discount-select {
        padding: 0.875rem;
        font-size: 1rem;
        border: 2px solid #d1d5db;
        border-radius: 0.5rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        width: 180px;
        flex-shrink: 0;
    }
    
    .discount-controls .discount-select:hover {
        border-color: #9ca3af;
        background: #f9fafb;
    }
    
    .discount-controls .discount-select:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }
    
    .discount-controls .discount-input {
        padding: 0.875rem;
        width: 200px;
        font-size: 1rem;
        border: 2px solid #d1d5db;
        border-radius: 0.5rem;
        transition: all 0.2s;
        background: white;
        flex-shrink: 0;
    }
    
    .discount-controls .discount-input:focus {
        outline: none;
        border-color: #f59e0b;
        box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
    }
    
    .apply-discount-btn {
        padding: 0.5rem 0.75rem;
        background: #fbbf24;
        color: #92400e;
        border: none;
        border-radius: 0.375rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    .apply-discount-btn:hover {
        background: #f59e0b;
    }
    
    .pos-customer {
        margin-bottom: 1.5rem;
    }
    
    .customer-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .customer-row input {
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.95rem;
    }
    
    .pos-summary {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .summary-header h4 {
        margin: 0 0 0.75rem 0;
        color: #166534;
        font-size: 1.1rem;
    }
    
    .summary-breakdown {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.95rem;
        color: #374151;
    }
    
    .summary-total {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
        font-size: 1.1rem;
        color: #059669;
        border-top: 1px solid #bbf7d0;
        padding-top: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .pos-checkout-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    @media (max-width: 768px) {
        .dashboard-mini-pos {
            width: 95vw;
        }
        
        .pos-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }
        
        .pos-total {
            text-align: center;
        }
        
        .cart-item {
            grid-template-columns: 1fr;
            gap: 0.5rem;
        }
        
        .discount-controls {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .discount-controls .discount-input {
            width: 100%;
        }
        
        .customer-row {
            grid-template-columns: 1fr;
        }
        
        .summary-row,
        .summary-total {
            font-size: 0.9rem;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid,
        .actions-grid,
        .management-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Enhanced Modal Styling */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.8);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { 
            opacity: 0; 
            transform: translateY(-20px) scale(0.95); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }
    
    .modal {
        background: white;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        max-width: 500px;
        width: 90vw;
        max-height: 90vh;
        overflow: hidden;
        animation: slideIn 0.3s ease-out;
        border: 1px solid rgba(226, 232, 240, 0.8);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-bottom: none;
        position: relative;
        overflow: hidden;
    }
    
    .modal-header::before {
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
    
    .modal-header h3 {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .close-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.25rem;
        transition: all 0.2s;
        z-index: 10;
        flex-shrink: 0; /* Prevent shrinking */
    }
    
    .close-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
    }
    
    /* Specific styles for POS modal close button */
    .dashboard-mini-pos .close-btn {
        top: 1rem;
        right: 1rem;
        background: rgba(255, 255, 255, 0.25);
        backdrop-filter: blur(8px);
        z-index: 1000;
        position: absolute;
    }
    
    .dashboard-mini-pos .close-btn:hover {
        background: rgba(255, 255, 255, 0.4);
    }
    
    .modal-body {
        padding: 2rem;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .modal-body form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .modal-body label {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        display: block;
        font-size: 0.95rem;
    }
    
    .modal-body input[type="text"],
    .modal-body input[type="number"],
    .modal-body select,
    .modal-body textarea {
        width: 100%;
        padding: 0.875rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.2s;
        background: #ffffff;
        box-sizing: border-box;
    }
    
    .modal-body input[type="text"]:focus,
    .modal-body input[type="number"]:focus,
    .modal-body select:focus,
    .modal-body textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        background: #fefefe;
    }
    
    .modal-body textarea {
        resize: vertical;
        min-height: 100px;
    }
    
    /* Checkbox styling for branches */
    .modal-body input[type="checkbox"] {
        width: auto;
        margin-right: 0.5rem;
        transform: scale(1.2);
        accent-color: #667eea;
    }
    
    .modal-body .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    .modal-body .checkbox-item {
        display: flex;
        align-items: center;
        font-size: 0.95rem;
        color: #4b5563;
    }
    
    .modal-body .checkbox-item input[type="checkbox"] {
        margin: 0 0.5rem 0 0;
    }
    
    .modal-footer {
        background: #f8fafc;
        padding: 1.5rem 2rem;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        min-width: 120px;
        justify-content: center;
    }
    
    .btn-secondary {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #cbd5e1;
    }
    
    .btn-secondary:hover {
        background: #e2e8f0;
        color: #475569;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: 1px solid transparent;
    }
    
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        filter: brightness(1.05);
    }
    
    .btn-primary:active {
        transform: translateY(0);
    }
    
    /* Form validation styling */
    .modal-body input:invalid,
    .modal-body select:invalid {
        border-color: #ef4444;
    }
    
    .modal-body input:valid,
    .modal-body select:valid {
        border-color: #10b981;
    }
    
    /* Loading state */
    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .btn.loading {
        position: relative;
        color: transparent;
    }
    
    .btn.loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin: -10px 0 0 -10px;
        border: 2px solid transparent;
        border-top: 2px solid currentColor;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        color: inherit;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Responsive modal */
    @media (max-width: 640px) {
        .modal {
            width: 95vw;
            margin: 1rem;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1rem 1.5rem;
            flex-direction: column-reverse;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .modal-body .checkbox-group {
            grid-template-columns: 1fr;
        }
    }
    
    /* Purchase Modal Specific Styling */
    .purchase-modal .modal-header {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
    }
    
    .purchase-modal .modal-header::before {
        background: rgba(139, 92, 246, 0.2);
    }
    
    .purchase-modal-btn {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%) !important;
        border: 1px solid transparent !important;
    }
    
    .purchase-modal-btn:hover {
        background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
        box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4) !important;
    }
    
    .purchase-modal .modal-body input[type="text"]:focus,
    .purchase-modal .modal-body input[type="number"]:focus,
    .purchase-modal .modal-body select:focus,
    .purchase-modal .modal-body textarea:focus {
        border-color: #8b5cf6 !important;
        box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.15) !important;
    }
    
    .purchase-modal .modal-body input:valid,
    .purchase-modal .modal-body select:valid {
        border-color: #8b5cf6 !important;
    }
    
    /* Product Modal Specific Styling */
    .product-modal .modal-header {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    }
    
    .product-modal-btn {
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%) !important;
    }
    
    .product-modal-btn:hover {
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4) !important;
    }
    
    /* Expense Modal Specific Styling */
    .expense-modal .modal-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }
    
    .expense-modal .modal-header::before {
        background: rgba(239, 68, 68, 0.2);
    }
    
    .expense-modal-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        border: 1px solid transparent !important;
    }
    
    .expense-modal-btn:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%) !important;
        box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4) !important;
    }
    
    .expense-modal .modal-body input[type="text"]:focus,
    .expense-modal .modal-body input[type="number"]:focus,
    .expense-modal .modal-body select:focus,
    .expense-modal .modal-body textarea:focus {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }
    
    /* Success Modal Styling */
    .success-modal-overlay {
        background: rgba(16, 185, 129, 0.1);
        backdrop-filter: blur(12px);
    }
    
    .success-modal {
        background: linear-gradient(145deg, #ffffff 0%, #f0fdf4 100%);
        border: 2px solid #10b981;
        box-shadow: 0 25px 50px -12px rgba(16, 185, 129, 0.3);
        max-width: 600px;
    }
    
    .success-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .success-header::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }
    
    .success-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        animation: bounceIn 0.8s ease-out;
    }
    
    @keyframes bounceIn {
        0% { transform: scale(0); opacity: 0; }
        50% { transform: scale(1.2); opacity: 0.8; }
        100% { transform: scale(1); opacity: 1; }
    }
    
    .success-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.75rem;
        font-weight: 700;
    }
    
    .success-subtitle {
        margin: 0;
        opacity: 0.9;
        font-size: 1rem;
    }
    
    .success-body {
        padding: 2rem;
    }
    
    .success-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .summary-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
    .summary-item .label {
        font-weight: 600;
        color: #64748b;
        font-size: 0.95rem;
    }
    
    .summary-item .value {
        font-weight: 700;
        color: #1e293b;
        font-size: 1rem;
    }
    
    .financial-summary {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .financial-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0;
        font-size: 1rem;
        color: #374151;
    }
    
    .financial-row.discount-row {
        color: #dc2626;
        font-weight: 600;
    }
    
    .financial-row.total-row {
        border-top: 2px solid #10b981;
        margin-top: 0.5rem;
        padding-top: 1rem;
        font-size: 1.25rem;
        font-weight: 700;
        color: #10b981;
    }
    
    .success-footer {
        background: #f0fdf4;
        padding: 1.5rem 2rem;
        border-top: 1px solid #bbf7d0;
        text-align: center;
    }
    
    .success-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .success-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    }
    
    /* Warning Modal Styling */
    .warning-modal-overlay {
        background: rgba(245, 158, 11, 0.1);
        backdrop-filter: blur(12px);
    }
    
    .warning-modal {
        background: linear-gradient(145deg, #ffffff 0%, #fffbeb 100%);
        border: 2px solid #f59e0b;
        box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.3);
        max-width: 550px;
    }
    
    .warning-header {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 2rem;
        text-align: center;
    }
    
    .warning-icon {
        font-size: 3.5rem;
        margin-bottom: 1rem;
        animation: shake 0.8s ease-in-out;
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
    
    .warning-header h2 {
        margin: 0 0 0.5rem 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .warning-subtitle {
        margin: 0;
        opacity: 0.9;
    }
    
    .warning-body {
        padding: 2rem;
    }
    
    .status-summary {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .status-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        border-radius: 8px;
    }
    
    .status-item:last-child {
        margin-bottom: 0;
    }
    
    .success-status {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
    }
    
    .failed-status {
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }
    
    .status-icon {
        font-size: 1.25rem;
    }
    
    .status-text {
        font-weight: 600;
        font-size: 0.95rem;
    }
    
    .warning-message {
        background: white;
        padding: 1rem;
        border-radius: 8px;
        border: 1px solid #fed7aa;
        margin: 0;
        color: #92400e;
        font-weight: 500;
    }
    
    .warning-footer {
        background: #fffbeb;
        padding: 1.5rem 2rem;
        border-top: 1px solid #fed7aa;
        text-align: center;
    }
    
    .warning-btn {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 1rem 2rem;
        font-size: 1.1rem;
        font-weight: 600;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }
    
    .warning-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
    }
    
    /* Responsive Success/Warning Modals */
    @media (max-width: 640px) {
        .success-modal, .warning-modal {
            width: 95vw;
            margin: 1rem;
        }
        
        .summary-grid {
            grid-template-columns: 1fr;
        }
        
        .success-icon, .warning-icon {
            font-size: 3rem;
        }
        
        .success-header h2, .warning-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="dashboard-container">

<!-- Welcome Section -->
<div class="welcome" style="display: flex; align-items: center; justify-content: space-between;">
    <div>
        <h2>Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></p>
        <p class="date">Today is <?php echo date('Y-m-d'); ?></p>
    </div>
    <div style="display: flex; align-items: center; margin-left: 24px;">
        <span style="font-size: 1.1rem; color: #64748b; margin-right: 8px;">Time:</span>
        <span id="live-clock" style="font-size: 2rem; font-weight: 600; color: #2563eb; letter-spacing: 1px;"></span>
        <span id="clock-ampm" style="font-size: 1.1rem; color: #64748b; margin-left: 6px;"></span>
    </div>
</div>

<!-- Period Performance Section -->
<div class="performance-section">
    <div class="performance-header">
        <h2 class="performance-title">
            <span>📊</span> Performance Overview
        </h2>
    </div>
    
    <div class="period-tabs">
        <button class="period-tab" data-period="day">Day</button>
        <button class="period-tab active" data-period="week">Week</button>
        <button class="period-tab" data-period="month">Month</button>
        <button class="period-tab" data-period="quarter">Quarter</button>
        <button class="period-tab" data-period="year">Year</button>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <!-- Products Card -->
        <div class="stat-card products">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $totalProducts; ?></div>
                <div class="stat-label">Products</div>
                <div class="stat-subtitle">Total products in system</div>
            </div>
        </div>

        <!-- Sales Card -->
        <div class="stat-card sales">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
                <div class="stat-value" id="sales-count"><?php echo $weekSales['count']; ?></div>
                <div class="stat-label" id="sales-label">This Week's Sales</div>
                <div class="stat-subtitle" id="sales-subtitle">Sales transactions</div>
            </div>
        </div>

        <!-- Activity Card -->
        <div class="stat-card activity">
            <div class="stat-icon">⚡</div>
            <div class="stat-content">
                <div class="stat-value" id="activity-count"><?php echo $weekActivities; ?></div>
                <div class="stat-label" id="activity-label">This Week's Activity</div>
                <div class="stat-subtitle" id="activity-subtitle">Total activities</div>
            </div>
        </div>

        <!-- Net Cash Card -->
        <div class="stat-card cash">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value" id="net-cash">K<?php echo number_format($weekSales['revenue'] - $weekExpenses, 2); ?></div>
                <div class="stat-label" id="net-label">Net Cash</div>
                <div class="stat-subtitle" id="net-subtitle">Revenue - Expenses</div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue and Expenses Cards -->
<div class="revenue-expense-grid">
    <!-- Revenue -->
    <div class="revenue-card">
        <h3 id="revenue-title">This Week's Revenue</h3>
        <p class="value" id="revenue-value">K<?php echo number_format($weekSales['revenue'], 2); ?></p>
        <p class="subtitle">Total sales revenue</p>
    </div>

    <!-- Expenses -->
    <div class="expense-card">
        <h3 id="expense-title">This Week's Expenses</h3>
        <p class="value" id="expense-value">K<?php echo number_format($weekExpenses, 2); ?></p>
        <p class="subtitle">Total expenses</p>
    </div>
</div>

<!-- Quick Actions -->
<div>
    <h3 class="section-title">Quick Actions</h3>
    <div class="actions-grid">
        <button onclick="showAddProductModal()" class="action-btn blue">
            <div>📦 Add New Product</div>
        </button>
        <button onclick="showAddSaleModal()" class="action-btn green">
            <div>📈 Record Sale</div>
        </button>
        <button onclick="showAddPurchaseModal()" class="action-btn purple">
            <div>📥 Record Purchase</div>
        </button>
        <button onclick="showAddExpenseModal()" class="action-btn red">
            <div>💸 Record Expense</div>
        </button>
    </div>
</div>

<!-- Management Section -->
<div>
    <h3 class="section-title">Management</h3>
    <div class="management-grid">
        <a href="../products/list.php" class="action-btn">
            <div>Manage Products</div>
        </a>
        <a href="../stock/view.php" class="action-btn">
            <div>Stock Management</div>
        </a>
        <a href="../sales/list.php" class="action-btn">
            <div>View Transactions</div>
        </a>
        <a href="../reports/dashboard.php" class="action-btn">
            <div>Reports & Analytics</div>
        </a>
    </div>
</div>

</div>

<script>
// Live Clock with AM/PM
function updateLiveClock() {
    var clock = document.getElementById('live-clock');
    var ampmSpan = document.getElementById('clock-ampm');
    if (!clock || !ampmSpan) return;
    var now = new Date();
    var h = now.getHours();
    var m = now.getMinutes();
    var s = now.getSeconds();
    var ampm = h >= 12 ? 'PM' : 'AM';
    var displayH = h % 12;
    displayH = displayH ? displayH : 12; // 0 should be 12
    displayH = displayH < 10 ? '0' + displayH : displayH;
    m = m < 10 ? '0' + m : m;
    s = s < 10 ? '0' + s : s;
    clock.textContent = displayH + ':' + m + ':' + s;
    ampmSpan.textContent = ampm;
}
setInterval(updateLiveClock, 1000);
document.addEventListener('DOMContentLoaded', updateLiveClock);
// Period Performance Tab Functionality
document.addEventListener('DOMContentLoaded', function() {
    const periodData = {
        day: {
            salesCount: '<?php echo $todaySales['count']; ?>',
            salesRevenue: '<?php echo number_format($todaySales['revenue'], 2); ?>',
            expenses: '<?php echo number_format($todayExpenses, 2); ?>',
            activities: '<?php echo $todayActivities; ?>',
            netCash: <?php echo $todaySales['revenue'] - $todayExpenses; ?>,
            salesLabel: "Today's Sales",
            activityLabel: "Today's Activity",
            netLabel: "Net Cash Today",
            salesSubtitle: "Sales transactions today",
            activitySubtitle: "Total activities today",
            netSubtitle: "Revenue - Expenses",
            revenueTitle: "Today's Revenue",
            expenseTitle: "Today's Expenses"
        },
        week: {
            salesCount: '<?php echo $weekSales['count']; ?>',
            salesRevenue: '<?php echo number_format($weekSales['revenue'], 2); ?>',
            expenses: '<?php echo number_format($weekExpenses, 2); ?>',
            activities: '<?php echo $weekActivities; ?>',
            netCash: <?php echo $weekSales['revenue'] - $weekExpenses; ?>,
            salesLabel: "This Week's Sales",
            activityLabel: "This Week's Activity",
            netLabel: "Net Cash This Week",
            salesSubtitle: "Sales transactions this week",
            activitySubtitle: "Total activities this week",
            netSubtitle: "Revenue - Expenses",
            revenueTitle: "This Week's Revenue",
            expenseTitle: "This Week's Expenses"
        },
        month: {
            salesCount: '<?php echo $monthSales['count']; ?>',
            salesRevenue: '<?php echo number_format($monthSales['revenue'], 2); ?>',
            expenses: '<?php echo number_format($monthExpenses, 2); ?>',
            activities: '<?php echo $monthActivities; ?>',
            netCash: <?php echo $monthSales['revenue'] - $monthExpenses; ?>,
            salesLabel: "This Month's Sales",
            activityLabel: "This Month's Activity",
            netLabel: "Net Cash This Month",
            salesSubtitle: "Sales transactions this month",
            activitySubtitle: "Total activities this month",
            netSubtitle: "Revenue - Expenses",
            revenueTitle: "This Month's Revenue",
            expenseTitle: "This Month's Expenses"
        },
        quarter: {
            salesCount: '<?php echo $quarterSales['count']; ?>',
            salesRevenue: '<?php echo number_format($quarterSales['revenue'], 2); ?>',
            expenses: '<?php echo number_format($quarterExpenses, 2); ?>',
            activities: '<?php echo $quarterActivities; ?>',
            netCash: <?php echo $quarterSales['revenue'] - $quarterExpenses; ?>,
            salesLabel: "This Quarter's Sales",
            activityLabel: "This Quarter's Activity",
            netLabel: "Net Cash This Quarter",
            salesSubtitle: "Sales transactions this quarter",
            activitySubtitle: "Total activities this quarter",
            netSubtitle: "Revenue - Expenses",
            revenueTitle: "This Quarter's Revenue",
            expenseTitle: "This Quarter's Expenses"
        },
        year: {
            salesCount: '<?php echo $yearSales['count']; ?>',
            salesRevenue: '<?php echo number_format($yearSales['revenue'], 2); ?>',
            expenses: '<?php echo number_format($yearExpenses, 2); ?>',
            activities: '<?php echo $yearActivities; ?>',
            netCash: <?php echo $yearSales['revenue'] - $yearExpenses; ?>,
            salesLabel: "This Year's Sales",
            activityLabel: "This Year's Activity",
            netLabel: "Net Cash This Year",
            salesSubtitle: "Sales transactions this year",
            activitySubtitle: "Total activities this year",
            netSubtitle: "Revenue - Expenses",
            revenueTitle: "This Year's Revenue",
            expenseTitle: "This Year's Expenses"
        }
    };
    
    const tabs = document.querySelectorAll('.period-tab');
    
    // Get all elements that need to be updated
    const salesCount = document.getElementById('sales-count');
    const salesLabel = document.getElementById('sales-label');
    const salesSubtitle = document.getElementById('sales-subtitle');
    const activityCount = document.getElementById('activity-count');
    const activityLabel = document.getElementById('activity-label');
    const activitySubtitle = document.getElementById('activity-subtitle');
    const netCash = document.getElementById('net-cash');
    const netLabel = document.getElementById('net-label');
    const netSubtitle = document.getElementById('net-subtitle');
    const revenueTitle = document.getElementById('revenue-title');
    const revenueValue = document.getElementById('revenue-value');
    const expenseTitle = document.getElementById('expense-title');
    const expenseValue = document.getElementById('expense-value');
    
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
                salesCount, salesLabel, salesSubtitle,
                activityCount, activityLabel, activitySubtitle,
                netCash, netLabel, netSubtitle,
                revenueTitle, revenueValue, expenseTitle, expenseValue
            ];
            
            elementsToUpdate.forEach(element => {
                if (element) element.style.opacity = '0.5';
            });
            
            setTimeout(() => {
                // Update values
                salesCount.textContent = data.salesCount;
                salesLabel.textContent = data.salesLabel;
                salesSubtitle.textContent = data.salesSubtitle;
                
                activityCount.textContent = data.activities;
                activityLabel.textContent = data.activityLabel;
                activitySubtitle.textContent = data.activitySubtitle;
                
                const netCashFormatted = data.netCash.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                netCash.textContent = 'K' + netCashFormatted;
                netLabel.textContent = data.netLabel;
                netSubtitle.textContent = data.netSubtitle;
                
                revenueTitle.textContent = data.revenueTitle;
                revenueValue.textContent = 'K' + data.salesRevenue;
                expenseTitle.textContent = data.expenseTitle;
                expenseValue.textContent = 'K' + data.expenses;
                
                // Restore opacity
                elementsToUpdate.forEach(element => {
                    if (element) element.style.opacity = '1';
                });
            }, 150);
        });
    });
});

// Modal functions for Quick Actions
function showAddProductModal() {
    fetch('../products/add.php')
        .then(response => response.text())
        .then(html => {
            // Extract the form content from the response
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const form = doc.querySelector('form');
            
            if (form) {
                showModal('Add Product', form.innerHTML, '../products/add_handler.php');
            } else {
                alert('Error loading product form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading product form');
        });
}

// Mini POS System Class for Dashboard
class DashboardMiniPOS {
    constructor() {
        this.cart = [];
        this.products = [];
        this.branches = ['Livingstone', 'Chisamba', 'Lusaka'];
        this.currentBranch = this.branches[0];
        this.customerName = '';
        this.notes = '';
        this.discountType = 'none';
        this.discountValue = 0;
        this.loadData();
    }
    
    async loadData() {
        try {
            const response = await fetch('../config/get_products.php');
            const products = await response.json();
            this.products = products.map(product => ({
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                searchText: product.name.toLowerCase()
            }));
        } catch (error) {
            console.error('Error loading data:', error);
            this.products = [
                {id: 1, name: 'Sample Product 1', price: 25.00, searchText: 'sample product 1'},
                {id: 2, name: 'Sample Product 2', price: 15.50, searchText: 'sample product 2'}
            ];
        }
    }
    
    show() {
        const posHTML = `
            <div class="modal-overlay" id="quickActionModal" onclick="closeModal()">
                <div class="modal dashboard-mini-pos" onclick="event.stopPropagation()">
                    <div class="modal-header pos-header">
                        <div class="pos-info">
                            <h3>🏪 Quick Sale POS</h3>
                            <div class="pos-branch">
                                <label>Branch:</label>
                                <select id="dashboardPosBranch" onchange="dashboardMiniPOS.setBranch(this.value)">
                                    ${this.branches.map(branch => 
                                        `<option value="${branch}" ${branch === this.currentBranch ? 'selected' : ''}>${branch}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Product Selection -->
                        <div class="pos-product-select">
                            <select id="dashboardPosProductSelect" onchange="dashboardMiniPOS.addSelectedProduct(this.value)">
                                <option value="">🛍️ Select a product to add...</option>
                                ${this.products.map(product => 
                                    `<option value="${product.id}">${product.name} - K${product.price.toFixed(2)}</option>`
                                ).join('')}
                            </select>
                        </div>
                        
                        <!-- Cart Items -->
                        <div class="pos-cart">
                            <div class="cart-header">
                                <h4>🛒 Cart Items</h4>
                                <button class="clear-cart-btn" onclick="dashboardMiniPOS.clearCart()" ${this.cart.length === 0 ? 'disabled' : ''}>
                                    Clear All
                                </button>
                            </div>
                            <div class="cart-items" id="dashboardCartItems">
                                ${this.cart.length === 0 ? '<div class="empty-cart">Cart is empty. Search and add products above.</div>' : ''}
                            </div>
                        </div>
                        
                        <!-- Discount Section -->
                        <div class="pos-discount">
                            <div class="discount-header">
                                <h4>💰 Discount</h4>
                            </div>
                            <div class="discount-controls">
                                <select id="dashboardDiscountType" onchange="dashboardMiniPOS.setDiscountType(this.value)" class="discount-select">
                                    <option value="none">No Discount</option>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount (K)</option>
                                </select>
                                <input type="number" id="dashboardDiscountValue" placeholder="Enter amount" 
                                       oninput="dashboardMiniPOS.setDiscountValue(this.value)" 
                                       ${this.discountType === 'none' ? 'disabled' : ''} class="discount-input">
                                <button class="apply-discount-btn" onclick="dashboardMiniPOS.applyQuickDiscount(5)">5%</button>
                                <button class="apply-discount-btn" onclick="dashboardMiniPOS.applyQuickDiscount(10)">10%</button>
                            </div>
                        </div>
                        
                        <!-- Customer Info -->
                        <div class="pos-customer">
                            <div class="customer-row">
                                <input type="text" id="dashboardPosCustomer" placeholder="👤 Customer name (optional)" 
                                       value="${this.customerName}" oninput="dashboardMiniPOS.setCustomer(this.value)">
                                <input type="text" id="dashboardPosNotes" placeholder="📝 Sale notes (optional)" 
                                       value="${this.notes}" oninput="dashboardMiniPOS.setNotes(this.value)">
                            </div>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="pos-summary">
                            <div class="summary-header">
                                <h4>💰 Order Summary</h4>
                            </div>
                            <div class="summary-breakdown">
                                <div class="summary-row">
                                    <span>Subtotal:</span>
                                    <span id="dashboardPosSubtotal">K0.00</span>
                                </div>
                                <div class="summary-row">
                                    <span>Discount:</span>
                                    <span id="dashboardPosDiscount">-K0.00</span>
                                </div>
                                <div class="summary-total">
                                    <span>Total:</span>
                                    <span id="dashboardPosSummaryTotal">K0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">
                            ✖️ Cancel
                        </button>
                        <button type="button" class="btn btn-primary pos-checkout-btn" onclick="dashboardMiniPOS.checkout()" 
                                ${this.cart.length === 0 ? 'disabled' : ''}>
                            💳 Complete Sale
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', posHTML);
        this.updateDisplay();
    }
    
    addSelectedProduct(productId) {
        if (!productId) return;
        
        const product = this.products.find(p => p.id == productId);
        if (!product) return;
        
        const existingItem = this.cart.find(item => item.id == productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1
            });
        }
        
        // Reset the dropdown to default option
        document.getElementById('dashboardPosProductSelect').value = '';
        
        this.updateDisplay();
    }
    
    addToCart(productId) {
        const product = this.products.find(p => p.id == productId);
        if (!product) return;
        
        const existingItem = this.cart.find(item => item.id == productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                quantity: 1
            });
        }
        
        this.updateDisplay();
    }
    
    updateQuantity(productId, newQuantity) {
        const item = this.cart.find(item => item.id == productId);
        if (!item) return;
        
        if (newQuantity <= 0) {
            this.removeFromCart(productId);
        } else {
            item.quantity = parseInt(newQuantity);
            this.updateDisplay();
        }
    }
    
    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id != productId);
        this.updateDisplay();
    }
    
    clearCart() {
        if (confirm('Clear all items from cart?')) {
            this.cart = [];
            this.updateDisplay();
        }
    }
    
    setBranch(branch) {
        this.currentBranch = branch;
    }
    
    setCustomer(name) {
        this.customerName = name;
    }
    
    setNotes(notes) {
        this.notes = notes;
    }
    
    setDiscountType(type) {
        this.discountType = type;
        const discountInput = document.getElementById('dashboardDiscountValue');
        
        if (type === 'none') {
            this.discountValue = 0;
            discountInput.disabled = true;
            discountInput.value = '';
        } else {
            discountInput.disabled = false;
            discountInput.placeholder = type === 'percentage' ? '0-100' : '0.00';
        }
        
        this.updateDisplay();
    }
    
    setDiscountValue(value) {
        this.discountValue = parseFloat(value) || 0;
        this.updateDisplay();
    }
    
    applyQuickDiscount(percentage) {
        document.getElementById('dashboardDiscountType').value = 'percentage';
        document.getElementById('dashboardDiscountValue').value = percentage;
        this.setDiscountType('percentage');
        this.setDiscountValue(percentage);
    }
    
    calculateDiscount(subtotal) {
        if (this.discountType === 'none' || this.discountValue <= 0) {
            return 0;
        }
        
        if (this.discountType === 'percentage') {
            const percentage = Math.min(Math.max(this.discountValue, 0), 100);
            return (subtotal * percentage) / 100;
        } else if (this.discountType === 'fixed') {
            return Math.min(this.discountValue, subtotal);
        }
        
        return 0;
    }
    
    updateDisplay() {
        const cartContainer = document.getElementById('dashboardCartItems');
        if (this.cart.length === 0) {
            cartContainer.innerHTML = '<div class="empty-cart">Cart is empty. Search and add products above.</div>';
        } else {
            cartContainer.innerHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">K${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="item-controls">
                        <button class="qty-btn" onclick="dashboardMiniPOS.updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <input type="number" class="qty-input" value="${item.quantity}" min="1" 
                               onchange="dashboardMiniPOS.updateQuantity(${item.id}, this.value)">
                        <button class="qty-btn" onclick="dashboardMiniPOS.updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        <button class="remove-btn" onclick="dashboardMiniPOS.removeFromCart(${item.id})" title="Remove item">🗑️</button>
                    </div>
                    <div class="item-total">K${(item.price * item.quantity).toFixed(2)}</div>
                </div>
            `).join('');
        }
        
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = this.calculateDiscount(subtotal);
        const total = subtotal - discount;
        
        const subtotalEl = document.getElementById('dashboardPosSubtotal');
        const discountEl = document.getElementById('dashboardPosDiscount');
        const totalEl = document.getElementById('dashboardPosTotal');
        const summaryTotalEl = document.getElementById('dashboardPosSummaryTotal');
        
        if (subtotalEl) subtotalEl.textContent = `K${subtotal.toFixed(2)}`;
        if (discountEl) discountEl.textContent = `-K${discount.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `K${total.toFixed(2)}`;
        if (summaryTotalEl) summaryTotalEl.textContent = `K${total.toFixed(2)}`;
        
        const clearBtn = document.querySelector('.clear-cart-btn');
        const checkoutBtn = document.querySelector('.pos-checkout-btn');
        
        if (clearBtn) clearBtn.disabled = this.cart.length === 0;
        if (checkoutBtn) checkoutBtn.disabled = this.cart.length === 0;
    }
    
    async checkout() {
        if (this.cart.length === 0) {
            alert('Cart is empty!');
            return;
        }
        
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = this.calculateDiscount(subtotal);
        const total = subtotal - discount;
        
        try {
            const checkoutBtn = document.querySelector('.pos-checkout-btn');
            const originalText = checkoutBtn.innerHTML;
            checkoutBtn.innerHTML = '<span>⏳</span> Processing...';
            checkoutBtn.disabled = true;
            
            let successCount = 0;
            for (const item of this.cart) {
                const itemSubtotal = item.price * item.quantity;
                const itemDiscountRatio = subtotal > 0 ? discount / subtotal : 0;
                const itemDiscount = itemSubtotal * itemDiscountRatio;
                const itemTotal = itemSubtotal - itemDiscount;
                
                const sale = {
                    product_id: item.id,
                    quantity: item.quantity,
                    unit_price: item.price,
                    total: itemTotal.toFixed(2),
                    branch: this.currentBranch,
                    customer_name: this.customerName,
                    notes: `${this.notes}${discount > 0 ? ` | Discount Applied: K${itemDiscount.toFixed(2)}` : ''}`
                };
                
                console.log('Submitting sale data:', sale);
                
                const success = await this.submitSale(sale);
                console.log('Sale result for item', item.id, ':', success);
                if (success) successCount++;
            }
            
            closeModal();
            
            if (successCount === this.cart.length) {
                this.showSuccessModal(this.cart.length, subtotal, discount, total);
            } else {
                this.showPartialSuccessModal(successCount, this.cart.length);
            }
            
        } catch (error) {
            console.error('Checkout error:', error);
            alert('❌ Error processing sale. Please try again.');
        }
    }
    
    submitSale(saleData) {
        console.log('submitSale called with data:', saleData);
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../sales/add_handler_simple.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                console.log('XHR Response:', xhr.status, xhr.responseText);
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        console.log('Parsed response:', response);
                        resolve(response.success || false);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        const responseText = xhr.responseText.toLowerCase();
                        const success = responseText.includes('success') || responseText.includes('added');
                        console.log('Fallback parsing result:', success);
                        resolve(success);
                    }
                } else {
                    console.error('HTTP error:', xhr.status);
                    resolve(false);
                }
            };
            
            xhr.onerror = function() {
                console.error('Network error');
                resolve(false);
            };
            
            const formData = new URLSearchParams();
            for (const [key, value] of Object.entries(saleData)) {
                formData.append(key, value);
            }
            console.log('Sending form data:', formData.toString());
            
            xhr.send(formData);
        });
    }
    
    showSuccessModal(itemCount, subtotal, discount, total) {
        const currentTime = new Date().toLocaleString();
        const successHTML = `
            <div class="modal-overlay success-modal-overlay" id="successModal" onclick="this.remove(); location.reload();">
                <div class="modal success-modal" onclick="event.stopPropagation()">
                    <div class="success-header">
                        <div class="success-icon">✅</div>
                        <h2>Sale Completed Successfully!</h2>
                        <p class="success-subtitle">Transaction processed at ${currentTime}</p>
                    </div>
                    
                    <div class="success-body">
                        <div class="success-summary">
                            <div class="summary-grid">
                                <div class="summary-item">
                                    <span class="label">Items Sold:</span>
                                    <span class="value">${itemCount} ${itemCount === 1 ? 'item' : 'items'}</span>
                                </div>
                                <div class="summary-item">
                                    <span class="label">Branch:</span>
                                    <span class="value">${this.currentBranch}</span>
                                </div>
                                ${this.customerName ? `
                                <div class="summary-item">
                                    <span class="label">Customer:</span>
                                    <span class="value">${this.customerName}</span>
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="financial-summary">
                                <div class="financial-row">
                                    <span>Subtotal:</span>
                                    <span>K${subtotal.toFixed(2)}</span>
                                </div>
                                ${discount > 0 ? `
                                <div class="financial-row discount-row">
                                    <span>Discount Applied:</span>
                                    <span>-K${discount.toFixed(2)}</span>
                                </div>
                                ` : ''}
                                <div class="financial-row total-row">
                                    <span>Total Amount:</span>
                                    <span>K${total.toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="success-footer">
                        <button class="btn success-btn" onclick="this.closest('#successModal').remove(); location.reload();">
                            🎉 Awesome! Continue
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', successHTML);
        
        // Clear cart after successful sale
        this.cart = [];
        this.customerName = '';
        this.notes = '';
        this.discountType = 'none';
        this.discountValue = 0;
    }
    
    showPartialSuccessModal(successCount, totalCount) {
        const failedCount = totalCount - successCount;
        const partialHTML = `
            <div class="modal-overlay warning-modal-overlay" id="warningModal" onclick="this.remove(); location.reload();">
                <div class="modal warning-modal" onclick="event.stopPropagation()">
                    <div class="warning-header">
                        <div class="warning-icon">⚠️</div>
                        <h2>Partial Sale Completion</h2>
                        <p class="warning-subtitle">Some items could not be processed</p>
                    </div>
                    
                    <div class="warning-body">
                        <div class="status-summary">
                            <div class="status-item success-status">
                                <span class="status-icon">✅</span>
                                <span class="status-text">${successCount} items processed successfully</span>
                            </div>
                            <div class="status-item failed-status">
                                <span class="status-icon">❌</span>
                                <span class="status-text">${failedCount} items failed to process</span>
                            </div>
                        </div>
                        <p class="warning-message">
                            Please check your inventory levels and try again for the failed items.
                        </p>
                    </div>
                    
                    <div class="warning-footer">
                        <button class="btn warning-btn" onclick="this.closest('#warningModal').remove(); location.reload();">
                            🔄 Review & Retry
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', partialHTML);
    }
}

// Initialize Dashboard Mini POS
const dashboardMiniPOS = new DashboardMiniPOS();

function showAddSaleModal() {
    dashboardMiniPOS.show();
}

function showAddPurchaseModal() {
    fetch('../purchases/add.php')
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const form = doc.querySelector('form');
            
            if (form) {
                showModal('Record Purchase', form.innerHTML, '../purchases/add_handler.php');
            } else {
                alert('Error loading purchase form');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading purchase form');
        });
}

function showAddExpenseModal() {
    console.log('Add expense modal clicked');
    
    const modalHTML = `
        <div class="modal-overlay" id="expenseModal" onclick="closeExpenseModal(event)">
            <div class="modal expense-modal" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3>💸 Record Expense</h3>
                    <button class="modal-close" onclick="closeExpenseModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="expenseForm" onsubmit="submitExpense(event)">
                        <div class="form-group">
                            <label for="expenseCategory">Category *</label>
                            <select id="expenseCategory" name="category" required>
                                <option value="">Select Category</option>
                                <option value="Operation">Operation</option>
                                <option value="Logistics">Logistics</option>
                                <option value="Office & Stationery">Office & Stationery</option>
                                <option value="Home & Farm">Home & Farm</option>
                                <option value="Miscellaneous">Miscellaneous</option>
                                <option value="Bills">Bills</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="expenseAmount">Amount (ZMW) *</label>
                            <input type="number" id="expenseAmount" name="amount" min="0" step="0.01" required placeholder="0.00">
                        </div>
                        
                        <div class="form-group">
                            <label for="expenseBranch">Branch *</label>
                            <select id="expenseBranch" name="branch" required>
                                <option value="">Select Branch</option>
                                <option value="Livingstone">Livingstone</option>
                                <option value="Chisamba">Chisamba</option>
                                <option value="Lusaka">Lusaka</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="expenseDescription">Description (optional)</label>
                            <input type="text" id="expenseDescription" name="description" placeholder="Enter expense description">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-modal btn-modal-secondary" onclick="closeExpenseModal()">
                        ✖️ Cancel
                    </button>
                    <button type="submit" form="expenseForm" class="btn-modal btn-modal-danger">
                        💸 Record Expense
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function closeExpenseModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const modal = document.getElementById('expenseModal');
    if (modal) {
        modal.remove();
    }
}

function submitExpense(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]') || document.querySelector('button[form="expenseForm"]');
    
    // Disable submit button and show loading
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Processing...';
    }
    
    fetch('../expenses/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(text => {
        // Try to parse as JSON, fallback to checking text content
        try {
            const data = JSON.parse(text);
            if (data.success) {
                handleExpenseSuccess();
            } else {
                showExpenseError(data.message || 'Error recording expense. Please try again.');
            }
        } catch (e) {
            // Check if the response contains success indicators
            if (text.toLowerCase().includes('success') || text.toLowerCase().includes('recorded')) {
                handleExpenseSuccess();
            } else if (text.toLowerCase().includes('error') || text.toLowerCase().includes('failed')) {
                showExpenseError('Error recording expense. Please check all fields and try again.');
            } else {
                // If we can't determine, assume success if no obvious error
                handleExpenseSuccess();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showExpenseError('Network error. Please check your connection and try again.');
        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '💸 Record Expense';
        }
    });
}

function handleExpenseSuccess() {
    closeExpenseModal();
    showDashboardSuccessMessage('Expense recorded successfully!');
    // Reload page after a short delay to refresh data
    setTimeout(() => {
        location.reload();
    }, 1500);
}

function showExpenseError(message) {
    showDashboardErrorMessage(message);
    // Re-enable submit button
    const submitBtn = document.querySelector('button[form="expenseForm"]');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💸 Record Expense';
    }
}

function showDashboardSuccessMessage(message) {
    const messageHTML = `
        <div class="dashboard-success-toast" id="dashboardSuccessToast">
            <div class="toast-content">
                <span class="toast-icon">✅</span>
                <span class="toast-message">${message}</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', messageHTML);
    
    setTimeout(() => {
        const toast = document.getElementById('dashboardSuccessToast');
        if (toast) toast.remove();
    }, 3000);
}

function showDashboardErrorMessage(message) {
    const messageHTML = `
        <div class="dashboard-error-toast" id="dashboardErrorToast">
            <div class="toast-content">
                <span class="toast-icon">❌</span>
                <span class="toast-message">${message}</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', messageHTML);
    
    setTimeout(() => {
        const toast = document.getElementById('dashboardErrorToast');
        if (toast) toast.remove();
    }, 4000);
}

function showModal(title, content, actionUrl) {
    // Add appropriate icons for different modals
    const icons = {
        'Add Product': '📦',
        'Record Purchase': '📥',
        'Record Expense': '💸'
    };
    
    // Add specific styling classes for different modal types
    const modalClasses = {
        'Add Product': 'product-modal',
        'Record Purchase': 'purchase-modal',
        'Record Expense': 'expense-modal'
    };
    
    const icon = icons[title] || '📋';
    const modalClass = modalClasses[title] || 'default-modal';
    
    // Create modal HTML
    const modalHtml = `
        <div class="modal-overlay" id="quickActionModal" onclick="closeModal()">
            <div class="modal ${modalClass}" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <h3>${icon} ${title}</h3>
                    <button class="close-btn" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="quickActionForm" action="${actionUrl}" method="POST">
                        ${content}
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        ✖️ Cancel
                    </button>
                    <button type="submit" form="quickActionForm" class="btn btn-primary ${modalClass}-btn">
                        ${title === 'Record Purchase' ? '📥 Record Purchase' : '💾 Save'}
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Enhance form elements
    setTimeout(() => {
        const form = document.getElementById('quickActionForm');
        if (form) {
            // Remove any existing submit buttons from the loaded form
            const existingSubmitButtons = form.querySelectorAll('input[type="submit"], button[type="submit"], input[value*="Add"], input[value*="Record"], input[value*="Save"]');
            existingSubmitButtons.forEach(button => {
                button.remove();
            });
            
            // Add floating labels effect
            const inputs = form.querySelectorAll('input[type="text"], input[type="number"], textarea');
            inputs.forEach(input => {
                if (input.placeholder) {
                    input.setAttribute('data-placeholder', input.placeholder);
                }
            });
            
            // Style checkbox groups better
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            if (checkboxes.length > 0) {
                const checkboxContainer = checkboxes[0].closest('div');
                if (checkboxContainer) {
                    checkboxContainer.classList.add('checkbox-group');
                    checkboxes.forEach(checkbox => {
                        const wrapper = document.createElement('div');
                        wrapper.className = 'checkbox-item';
                        checkbox.parentNode.insertBefore(wrapper, checkbox);
                        wrapper.appendChild(checkbox);
                        wrapper.appendChild(document.createTextNode(' ' + checkbox.nextSibling.textContent.trim()));
                        if (checkbox.nextSibling.textContent) {
                            checkbox.nextSibling.remove();
                        }
                    });
                }
            }
        }
    }, 50);
    
    // Handle form submission with enhanced UX
    document.getElementById('quickActionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = document.querySelector('.btn-primary');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '⏳ Saving...';
        
        const formData = new FormData(this);
        
        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(result => {
            if (result.includes('success') || result.includes('added') || result.includes('recorded')) {
                // Success animation
                submitBtn.innerHTML = '✅ Success!';
                submitBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                
                setTimeout(() => {
                    closeModal();
                    location.reload();
                }, 1000);
            } else {
                // Error state
                submitBtn.innerHTML = '❌ Error';
                submitBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.style.background = '';
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                }, 2000);
                
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.style.cssText = `
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    color: #dc2626;
                    padding: 1rem;
                    border-radius: 8px;
                    margin-top: 1rem;
                    animation: slideIn 0.3s ease-out;
                `;
                errorDiv.innerHTML = `<strong>Error:</strong> ${result}`;
                this.appendChild(errorDiv);
                
                setTimeout(() => errorDiv.remove(), 5000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            submitBtn.innerHTML = '❌ Network Error';
            submitBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.style.background = '';
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }, 2000);
        });
    });
}

function closeModal() {
    const modal = document.getElementById('quickActionModal');
    if (modal) {
        modal.remove();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
