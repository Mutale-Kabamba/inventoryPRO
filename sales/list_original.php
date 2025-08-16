<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT s.id, p.name AS product_name, s.quantity, s.branch, s.unit_price, s.total, s.sale_date, s.product_id, s.customer_name, s.notes FROM sales s JOIN products p ON s.product_id = p.id ORDER BY s.sale_date DESC');

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
        margin-left: 0.5rem;
    }
    
    .btn-success {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .btn-success:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
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
    
    .filters-card {
        background: #ffffff;
        border-radius: 1rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 2rem;
    }
    
    .filters-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    .filters-header h3 {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .filters-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .filter-group label {
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    
    .filter-group input,
    .filter-group select {
        padding: 0.5rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: all 0.2s;
    }
    
    .filter-group input:focus,
    .filter-group select:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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
    
    .table-responsive {
        overflow-x: auto;
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
    
    .customer-name {
        color: #4b5563;
        font-style: italic;
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
    
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        background: transparent;
    }
    
    .btn:hover {
        transform: translateY(-1px);
    }
    
    .btn-outline {
        border: 1px solid #d1d5db;
        color: #374151;
        background: #ffffff;
    }
    
    .btn-outline:hover {
        background: #f9fafb;
        border-color: #9ca3af;
    }
    
    .btn-success {
        background: #059669;
        color: white;
        border-color: #059669;
    }
    
    .btn-success:hover {
        background: #047857;
        border-color: #047857;
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
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
    
    .hidden-row {
        display: none !important;
    }
    
    /* Custom Modal Styles */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .modal-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .modal-container {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        transform: scale(0.95) translateY(20px);
        transition: all 0.3s ease;
    }
    
    .modal-overlay.active .modal-container {
        transform: scale(1) translateY(0);
    }
    
    .modal-header {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: 1rem 1rem 0 0;
    }
    
    .modal-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        cursor: pointer;
        color: #6b7280;
        padding: 0.25rem;
        border-radius: 0.375rem;
        transition: all 0.2s;
    }
    
    .modal-close:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .modal-body {
        padding: 2rem;
    }
    
    .modal-form-group {
        margin-bottom: 1.5rem;
    }
    
    .modal-form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
    }
    
    .modal-form-group input,
    .modal-form-group select,
    .modal-form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.2s;
        box-sizing: border-box;
    }
    
    .modal-form-group input:focus,
    .modal-form-group select:focus,
    .modal-form-group textarea:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    .modal-form-group textarea {
        resize: vertical;
        min-height: 80px;
    }
    
    .modal-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        gap: 0.75rem;
        justify-content: flex-end;
        background: #f9fafb;
        border-radius: 0 0 1rem 1rem;
    }
    
    .modal-btn {
        padding: 0.75rem 1.5rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .modal-btn-primary {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }
    
    .modal-btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .modal-btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #d1d5db;
    }
    
    .modal-btn-secondary:hover {
        background: #e5e7eb;
    }
    
    .modal-btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
    }
    
    .modal-btn-danger:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .modal-info-content {
        color: #374151;
        line-height: 1.6;
    }
    
    .modal-info-content strong {
        color: #1f2937;
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
        
        .filters-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* Mini POS Styles */
    .mini-pos {
        max-width: 100%;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .pos-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
    }
    
    .pos-info h3 {
        margin: 0 0 0.5rem 0;
        font-size: 1.25rem;
        font-weight: 600;
    }
    
    .pos-branch {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }
    
    .pos-branch select {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-size: 0.875rem;
    }
    
    .pos-branch select option {
        background: #667eea;
        color: white;
    }
    
    .pos-total {
        text-align: right;
    }
    
    .total-label {
        font-size: 0.875rem;
        opacity: 0.9;
        margin-bottom: 0.25rem;
    }
    
    .total-amount {
        font-size: 2rem;
        font-weight: bold;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        padding-top: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .total-breakdown {
        margin-bottom: 0.5rem;
    }
    
    .subtotal-row, .discount-row {
        display: flex;
        justify-content: space-between;
        font-size: 0.875rem;
        margin-bottom: 0.25rem;
        opacity: 0.9;
    }
    
    .discount-row {
        color: #ef4444;
    }
    
    .pos-product-select {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 2px solid #e2e8f0;
    }
    
    .pos-product-select label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        font-size: 1.1rem;
    }
    
    .product-select-row {
        display: flex;
        gap: 0.75rem;
        align-items: stretch;
    }
    
    .product-select-row select {
        flex: 1;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        background: white;
        transition: all 0.2s ease;
    }
    
    .product-select-row select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .add-product-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    
    .add-product-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .add-product-btn:disabled {
        background: #d1d5db;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }
    
    .pos-search {
        position: relative;
        margin-bottom: 1.5rem;
    }
    
    .pos-search input {
        width: 100%;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.2s ease;
        background: #f9fafb;
    }
    
    .pos-search input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .product-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        display: none;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .product-suggestion {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 1rem;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.2s ease;
    }
    
    .product-suggestion:hover {
        background: #f8fafc;
    }
    
    .product-suggestion:last-child {
        border-bottom: none;
    }
    
    .product-name {
        font-weight: 500;
        color: #374151;
    }
    
    .product-price {
        font-weight: 600;
        color: #059669;
    }
    
    .no-results {
        padding: 1rem;
        text-align: center;
        color: #6b7280;
        font-style: italic;
    }
    
    .pos-cart {
        background: #f9fafb;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        min-height: 200px;
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
        background: #ef4444;
        color: white;
        border: none;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }
    
    .clear-cart-btn:hover:not(:disabled) {
        background: #dc2626;
    }
    
    .clear-cart-btn:disabled {
        background: #d1d5db;
        cursor: not-allowed;
    }
    
    .empty-cart {
        text-align: center;
        color: #6b7280;
        font-style: italic;
        padding: 2rem;
        background: white;
        border-radius: 8px;
        border: 2px dashed #d1d5db;
    }
    
    .cart-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: white;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    
    .cart-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .cart-item:last-child {
        margin-bottom: 0;
    }
    
    .item-info {
        flex: 1;
    }
    
    .item-name {
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    
    .item-price {
        font-size: 0.875rem;
        color: #6b7280;
    }
    
    .item-controls {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0 1rem;
    }
    
    .qty-btn {
        width: 32px;
        height: 32px;
        border: 1px solid #d1d5db;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        color: #374151;
        transition: all 0.2s ease;
    }
    
    .qty-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .qty-input {
        width: 60px;
        text-align: center;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-weight: 600;
    }
    
    .remove-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 4px;
        transition: background-color 0.2s ease;
        font-size: 1rem;
    }
    
    .remove-btn:hover {
        background: #fef2f2;
    }
    
    .item-total {
        font-weight: bold;
        color: #059669;
        font-size: 1.1rem;
        min-width: 80px;
        text-align: right;
    }
    
    .pos-customer {
        margin-bottom: 1rem;
    }
    
    .pos-discount {
        background: #f0f9ff;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        border: 1px solid #e0f2fe;
    }
    
    .discount-header {
        margin-bottom: 1rem;
    }
    
    .discount-header h4 {
        margin: 0;
        color: #0369a1;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .discount-controls {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        align-items: center;
    }
    
    .discount-controls select,
    .discount-controls input {
        padding: 0.5rem 0.75rem;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 0.875rem;
        transition: border-color 0.2s ease;
    }
    
    .discount-controls input:focus,
    .discount-controls select:focus {
        outline: none;
        border-color: #0369a1;
        box-shadow: 0 0 0 3px rgba(3, 105, 161, 0.1);
    }
    
    .discount-controls input:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }
    
    .apply-discount-btn {
        background: #0ea5e9;
        color: white;
        border: none;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s ease;
        margin-top: 0.75rem;
    }
    
    .apply-discount-btn:hover {
        background: #0284c7;
    }
    
    .apply-discount-btn:active {
        transform: translateY(1px);
    }
    
    .customer-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    .customer-row input {
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: border-color 0.2s ease;
    }
    
    .customer-row input:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .pos-checkout-btn:disabled {
        background: #d1d5db !important;
        cursor: not-allowed !important;
    }
    
    /* POS Mobile Styles */
    @media (max-width: 768px) {
        .pos-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        .customer-row {
            grid-template-columns: 1fr;
        }
        
        .cart-item {
            flex-direction: column;
            gap: 1rem;
            align-items: stretch;
        }
        
        .item-controls {
            justify-content: center;
            margin: 0;
        }
        
        .item-total {
            text-align: center;
            font-size: 1.25rem;
        }
    }
</style>

<div class="sales-container">
    <!-- Header -->
    <div class="sales-header">
        <div class="header-content">
            <h1>📈 Sales Management</h1>
            <p>Track and manage your sales transactions across all branches</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline btn-sm" onclick="exportSales()">
                <span>📊</span> Export Data
            </button>
            <button class="btn btn-success" onclick="showAddSaleModal()">
                <span>➕</span> Record New Sale
            </button>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="filters-card">
        <div class="filters-header">
            <h3>🔍 Filters & Search</h3>
            <button class="btn btn-sm btn-outline" onclick="clearFilters()">Clear All</button>
        </div>
        <div class="filters-grid">
            <div class="filter-group">
                <label>Search Sales</label>
                <input type="text" id="searchInput" placeholder="Search products, customers..." onkeyup="filterSales()">
            </div>
            <div class="filter-group">
                <label>Branch</label>
                <select id="branchFilter" onchange="filterSales()">
                    <option value="">All Branches</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Date Range</label>
                <select id="dateFilter" onchange="filterSales()">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="quarter">This Quarter</option>
                    <option value="year">This Year</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Amount Range</label>
                <select id="amountFilter" onchange="filterSales()">
                    <option value="">All Amounts</option>
                    <option value="0-100">K0 - K100</option>
                    <option value="100-500">K100 - K500</option>
                    <option value="500-1000">K500 - K1,000</option>
                    <option value="1000+">K1,000+</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Period Performance Section -->
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
                <div class="stat-icon">📈</div>
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
                <div class="stat-icon">📦</div>
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
            <div class="table-responsive">
                <table class="sales-table" id="salesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Quantity</th>
                            <th>Unit Price (K)</th>
                            <th>Branch</th>
                            <th>Total (K)</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        <?php $result->data_seek(0); while ($row = $result->fetch_assoc()): ?>
                            <tr data-branch="<?php echo htmlspecialchars($row['branch']); ?>" 
                                data-date="<?php echo $row['sale_date']; ?>" 
                                data-amount="<?php echo $row['total']; ?>"
                                data-search="<?php echo strtolower(htmlspecialchars($row['product_name'] . ' ' . $row['customer_name'] . ' ' . $row['branch'])); ?>">
                                <td>#<?php echo $row['id']; ?></td>
                                <td class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td class="customer-name">
                                    <?php echo !empty($row['customer_name']) ? htmlspecialchars($row['customer_name']) : '<span style="color: #9ca3af;">-</span>'; ?>
                                </td>
                                <td><?php echo number_format($row['quantity']); ?></td>
                                <td>K<?php echo number_format($row['unit_price'], 2); ?></td>
                                <td>
                                    <span class="branch-badge"><?php echo htmlspecialchars($row['branch']); ?></span>
                                </td>
                                <td class="total-amount">K<?php echo number_format($row['total'], 2); ?></td>
                                <td class="sale-date"><?php echo date('M j, Y g:i A', strtotime($row['sale_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-outline btn-sm" onclick="showSaleDetails(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>', '<?php echo htmlspecialchars($row['customer_name']); ?>', '<?php echo htmlspecialchars($row['notes']); ?>', <?php echo $row['quantity']; ?>, <?php echo $row['unit_price']; ?>, '<?php echo htmlspecialchars($row['branch']); ?>', <?php echo $row['total']; ?>, '<?php echo $row['sale_date']; ?>')">
                                            👁️ View
                                        </button>
                                        <button class="btn btn-outline btn-sm" onclick="showEditSaleModal(<?php echo $row['id']; ?>, <?php echo $row['product_id']; ?>, <?php echo $row['quantity']; ?>, '<?php echo htmlspecialchars($row['branch']); ?>', <?php echo $row['total']; ?>, '<?php echo htmlspecialchars($row['customer_name']); ?>', '<?php echo htmlspecialchars($row['notes']); ?>')">
                                            ✏️ Edit
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="showDeleteSaleModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['product_name']).' - '.date('M j, Y', strtotime($row['sale_date'])); ?>')">
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📈</div>
                <h3>No Sales Found</h3>
                <p>Start recording sales to see them here.</p>
                <button class="btn btn-success" onclick="showAddSaleModal()" style="margin-top: 1rem;">
                    <span>➕</span> Record Your First Sale
                </button>
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
    // Wait for header script to load and initialize modal system
    let modalCheckAttempts = 0;
    const maxAttempts = 20;
    
    function checkModalSystem() {
        modalCheckAttempts++;
        
        if (window.modal && typeof window.modal.showForm === 'function') {
            console.log('Modal system loaded successfully');
            return;
        }
        
        if (modalCheckAttempts < maxAttempts) {
            setTimeout(checkModalSystem, 100);
        } else {
            console.error('Modal system failed to load after', maxAttempts * 100, 'ms');
            // Create a basic modal fallback
            window.modal = {
                showForm: function(title, fields, options) {
                    return new Promise((resolve, reject) => {
                        alert('Modal system not available. Please refresh the page.');
                        reject('Modal system not available');
                    });
                },
                showInfo: function(title, content) {
                    alert(title + '\n\n' + content.replace(/<[^>]*>/g, ''));
                },
                showConfirmation: function(title, message, warning, confirmText) {
                    return new Promise((resolve) => {
                        const result = confirm(title + '\n\n' + message + '\n\n' + warning);
                        resolve(result);
                    });
                }
            };
        }
    }
    
    checkModalSystem();
});

// Mini POS System
class MiniPOS {
    constructor() {
        this.cart = [];
        this.products = <?php echo json_encode(array_map(function($product) { 
            return [
                'id' => $product['id'], 
                'name' => $product['name'], 
                'price' => $product['price'],
                'searchText' => strtolower($product['name'])
            ]; 
        }, $products)); ?>;
        
        this.branches = <?php echo json_encode($branches); ?>;
        this.currentBranch = this.branches[0] || '';
        this.customerName = '';
        this.notes = '';
        this.discountType = 'none'; // none, percentage, fixed
        this.discountValue = 0;
    }
    
    show() {
        const posHTML = `
            <div class="mini-pos">
                <!-- POS Header -->
                <div class="pos-header">
                    <div class="pos-info">
                        <h3>🏪 Point of Sale</h3>
                        <div class="pos-branch">
                            <label>Branch:</label>
                            <select id="posBranch" onchange="miniPOS.setBranch(this.value)">
                                ${this.branches.map(branch => 
                                    `<option value="${branch}" ${branch === this.currentBranch ? 'selected' : ''}>${branch}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="pos-total">
                        <div class="total-breakdown">
                            <div class="subtotal-row">
                                <span>Subtotal:</span>
                                <span id="posSubtotal">K0.00</span>
                            </div>
                            <div class="discount-row">
                                <span>Discount:</span>
                                <span id="posDiscount">K0.00</span>
                            </div>
                        </div>
                        <div class="total-amount" id="posTotal">K0.00</div>
                    </div>
                </div>
                
                <!-- Product Selection -->
                <div class="pos-product-select">
                    <input type="text" id="posProductSearch" placeholder="� Search products..." 
                           oninput="miniPOS.searchProducts(this.value)" autocomplete="off">
                    <div class="product-suggestions" id="productSuggestions"></div>
                </div>
                
                <!-- Cart Items -->
                <div class="pos-cart">
                    <div class="cart-header">
                        <h4>🛒 Cart Items</h4>
                        <button class="clear-cart-btn" onclick="miniPOS.clearCart()" ${this.cart.length === 0 ? 'disabled' : ''}>
                            Clear All
                        </button>
                    </div>
                    <div class="cart-items" id="cartItems">
                        ${this.cart.length === 0 ? '<div class="empty-cart">Cart is empty. Select products above to add them.</div>' : ''}
                    </div>
                </div>
                
                <!-- Discount Section -->
                <div class="pos-discount">
                    <div class="discount-header">
                        <h4>💰 Discount</h4>
                    </div>
                    <div class="discount-controls">
                        <select id="discountType" onchange="miniPOS.setDiscountType(this.value)">
                            <option value="none">No Discount</option>
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Amount (K)</option>
                        </select>
                        <input type="number" id="discountValue" placeholder="0" 
                               oninput="miniPOS.setDiscountValue(this.value)" 
                               ${this.discountType === 'none' ? 'disabled' : ''}>
                        <button class="apply-discount-btn" onclick="miniPOS.applyQuickDiscount(5)">5%</button>
                        <button class="apply-discount-btn" onclick="miniPOS.applyQuickDiscount(10)">10%</button>
                        <button class="apply-discount-btn" onclick="miniPOS.applyQuickDiscount(15)">15%</button>
                    </div>
                </div>
                
                <!-- Customer Info -->
                <div class="pos-customer">
                    <div class="customer-row">
                        <input type="text" id="posCustomer" placeholder="👤 Customer name (optional)" 
                               value="${this.customerName}" oninput="miniPOS.setCustomer(this.value)">
                        <input type="text" id="posNotes" placeholder="📝 Sale notes (optional)" 
                               value="${this.notes}" oninput="miniPOS.setNotes(this.value)">
                    </div>
                </div>
            </div>
        `;
        
        customModal.title.innerHTML = '🏪 Mini POS System';
        customModal.body.innerHTML = posHTML;
        customModal.footer.innerHTML = `
            <button class="modal-btn modal-btn-secondary" onclick="customModal.close()">
                <span>✖️</span> Cancel
            </button>
            <button class="modal-btn modal-btn-primary pos-checkout-btn" onclick="miniPOS.checkout()" 
                    ${this.cart.length === 0 ? 'disabled' : ''}>
                <span>💳</span> Complete Sale
            </button>
        `;
        
        customModal.open();
        
        this.updateDisplay();
    }
    
    selectProduct(productId) {
        const addBtn = document.querySelector('.add-product-btn');
        this.selectedProductId = productId;
        
        if (productId) {
            addBtn.disabled = false;
        } else {
            addBtn.disabled = true;
        }
    }
    
    addSelectedProduct() {
        if (this.selectedProductId) {
            this.addToCart(this.selectedProductId);
            // Reset selection
            document.getElementById('productSelect').value = '';
            document.querySelector('.add-product-btn').disabled = true;
            this.selectedProductId = null;
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
    
    addToCart(productId) {
        const product = this.products.find(p => p.id == productId);
        if (!product) return;
        
        // Check if product already in cart
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
        
        // Clear search
        document.getElementById('posProductSearch').value = '';
        document.getElementById('productSuggestions').style.display = 'none';
        
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
    
    setDiscountType(type) {
        this.discountType = type;
        const discountInput = document.getElementById('discountValue');
        
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
        document.getElementById('discountType').value = 'percentage';
        document.getElementById('discountValue').value = percentage;
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
        // Update cart items
        const cartContainer = document.getElementById('cartItems');
        if (this.cart.length === 0) {
            cartContainer.innerHTML = '<div class="empty-cart">Cart is empty. Search and add products above.</div>';
        } else {
            cartContainer.innerHTML = this.cart.map(item => `
                <div class="cart-item">
                    <div class="item-info">
                        <div class="item-name">${item.name}</div>
                        <div class="item-price">K${parseFloat(item.price).toFixed(2)} each</div>
                    </div>
                    <div class="item-controls">
                        <button class="qty-btn" onclick="miniPOS.updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <input type="number" class="qty-input" value="${item.quantity}" min="1" 
                               onchange="miniPOS.updateQuantity(${item.id}, this.value)">
                        <button class="qty-btn" onclick="miniPOS.updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        <button class="remove-btn" onclick="miniPOS.removeFromCart(${item.id})" title="Remove item">🗑️</button>
                    </div>
                    <div class="item-total">K${(item.price * item.quantity).toFixed(2)}</div>
                </div>
            `).join('');
        }
        
        // Calculate totals
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = this.calculateDiscount(subtotal);
        const total = subtotal - discount;
        
        // Update display
        const subtotalEl = document.getElementById('posSubtotal');
        const discountEl = document.getElementById('posDiscount');
        const totalEl = document.getElementById('posTotal');
        
        if (subtotalEl) subtotalEl.textContent = `K${subtotal.toFixed(2)}`;
        if (discountEl) discountEl.textContent = `-K${discount.toFixed(2)}`;
        if (totalEl) totalEl.textContent = `K${total.toFixed(2)}`;
        
        // Update buttons
        const clearBtn = document.querySelector('.clear-cart-btn');
        const checkoutBtn = document.querySelector('.pos-checkout-btn');
        
        if (clearBtn) clearBtn.disabled = this.cart.length === 0;
        if (checkoutBtn) checkoutBtn.disabled = this.cart.length === 0;
    }
    
    setBranch(branch) {
        this.currentBranch = branch;
    }
    
    setCustomer(customer) {
        this.customerName = customer;
    }
    
    setNotes(notes) {
        this.notes = notes;
    }
    
    async checkout() {
        if (this.cart.length === 0) {
            alert('Cart is empty!');
            return;
        }
        
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const discount = this.calculateDiscount(subtotal);
        const total = subtotal - discount;
        
        // Process each item in cart as separate sales
        const sales = this.cart.map(item => {
            const itemSubtotal = item.price * item.quantity;
            const itemDiscountRatio = subtotal > 0 ? discount / subtotal : 0;
            const itemDiscount = itemSubtotal * itemDiscountRatio;
            const itemTotal = itemSubtotal - itemDiscount;
            
            return {
                product_id: item.id,
                quantity: item.quantity,
                unit_price: item.price,
                total: itemTotal.toFixed(2),
                branch: this.currentBranch,
                customer_name: this.customerName,
                notes: `${this.notes}${discount > 0 ? ` | Discount Applied: K${itemDiscount.toFixed(2)}` : ''}`
            };
        });
        
        try {
            // Show processing
            const checkoutBtn = document.querySelector('.pos-checkout-btn');
            const originalText = checkoutBtn.innerHTML;
            checkoutBtn.innerHTML = '<span>⏳</span> Processing...';
            checkoutBtn.disabled = true;
            
            // Submit each sale
            let successCount = 0;
            for (const sale of sales) {
                const success = await this.submitSale(sale);
                if (success) successCount++;
            }
            
            // Show success and reload
            customModal.close();
            
            if (successCount === sales.length) {
                alert(`✅ Sale completed successfully!\n\nItems: ${this.cart.length}\nSubtotal: K${subtotal.toFixed(2)}\nDiscount: K${discount.toFixed(2)}\nTotal: K${total.toFixed(2)}`);
                window.location.reload();
            } else {
                alert(`⚠️ ${successCount}/${sales.length} items processed successfully. Please check and retry failed items.`);
                window.location.reload();
            }
            
        } catch (error) {
            console.error('Checkout error:', error);
            alert('❌ Error processing sale. Please try again.');
            
            // Restore button
            const checkoutBtn = document.querySelector('.pos-checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.innerHTML = originalText;
                checkoutBtn.disabled = false;
            }
        }
    }
    
    submitSale(saleData) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_handler.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            resolve(true);
                        } else {
                            console.error('Sale failed:', response.message);
                            resolve(false);
                        }
                    } catch (e) {
                        console.error('Invalid response:', xhr.responseText);
                        resolve(false);
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
            
            // Convert data to form format
            const formData = new URLSearchParams();
            for (const [key, value] of Object.entries(saleData)) {
                formData.append(key, value);
            }
            
            xhr.send(formData);
        });
    }
}

// Initialize Mini POS
const miniPOS = new MiniPOS();

// Sales modal functions
function showAddSaleModal() {
    miniPOS.show();
}

function showEditSaleModal(id, productId, quantity, branch, total, customerName = '', notes = '') {
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name'] . ' - K' . number_format($product['price'], 2)]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    const unitPrice = quantity > 0 ? (total / quantity).toFixed(2) : 0;
    
    customModal.showForm('✏️ Edit Sale', [
        {name: 'id', label: 'ID', type: 'hidden', value: id},
        {name: 'product_id', label: 'Product', type: 'select', required: true, value: productId, options: products},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, value: quantity},
        {name: 'branch', label: 'Branch', type: 'select', required: true, value: branch, options: branches},
        {name: 'unit_price', label: 'Unit Price (K)', type: 'number', step: '0.01', required: true, value: unitPrice},
        {name: 'customer_name', label: 'Customer Name', type: 'text', value: customerName, placeholder: 'Customer name (optional)'},
        {name: 'notes', label: 'Notes', type: 'textarea', value: notes, placeholder: 'Sale notes (optional)'}
    ], {submitText: 'Update Sale'}).then(data => {
        // Calculate total from unit price and quantity
        data.total = (parseFloat(data.unit_price || 0) * parseInt(data.quantity || 0)).toFixed(2);
        
        // Create a simple form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edit_handler.php';
        
        for (const [key, value] of Object.entries(data)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }).catch(error => {
        console.log('Modal cancelled or closed');
    });
}

function showSaleDetails(id, productName, customerName, notes, quantity, unitPrice, branch, total, saleDate) {
    const formattedDate = new Date(saleDate).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const detailsHtml = `
        <div style="display: grid; gap: 1.5rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div>
                    <strong>🏷️ Product:</strong><br>
                    <span style="color: #059669; font-weight: 600;">${productName}</span>
                </div>
                <div>
                    <strong>👤 Customer:</strong><br>
                    <span>${customerName || 'Not specified'}</span>
                </div>
                <div>
                    <strong>🏢 Branch:</strong><br>
                    <span style="background: #e0f2fe; color: #0369a1; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.8rem;">${branch}</span>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; background: #f8fafc; padding: 1rem; border-radius: 0.5rem;">
                <div>
                    <strong>📦 Quantity:</strong><br>
                    <span style="font-size: 1.2rem; color: #059669; font-weight: 700;">${quantity}</span>
                </div>
                <div>
                    <strong>💵 Unit Price:</strong><br>
                    <span style="font-size: 1.2rem; color: #059669; font-weight: 700;">K${parseFloat(unitPrice).toFixed(2)}</span>
                </div>
                <div>
                    <strong>💰 Total Amount:</strong><br>
                    <span style="font-size: 1.4rem; font-weight: bold; color: #059669;">K${parseFloat(total).toFixed(2)}</span>
                </div>
            </div>
            <div>
                <strong>📅 Sale Date:</strong><br>
                <span style="color: #4b5563;">${formattedDate}</span>
            </div>
            ${notes ? `<div><strong>📝 Notes:</strong><br><div style="background: #f0f9ff; padding: 0.75rem; border-radius: 0.5rem; border-left: 4px solid #0ea5e9; margin-top: 0.5rem;">${notes}</div></div>` : ''}
        </div>
    `;
    
    customModal.showInfo(`�️ Sale Details - #${id}`, detailsHtml);
}

function showDeleteSaleModal(id, name) {
    customModal.showConfirmation(
        '🗑️ Delete Sale',
        `Are you sure you want to delete this sale: ${name}?`,
        'This action cannot be undone.',
        'Delete Sale'
    ).then(confirmed => {
        if (confirmed) {
            // Create a simple form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete_handler.php';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }).catch(error => {
        console.log('Modal cancelled or closed');
    });
}

// Filter and Search Functions
function filterSales() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const branchFilter = document.getElementById('branchFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const amountFilter = document.getElementById('amountFilter').value;
    
    const rows = document.querySelectorAll('#salesTableBody tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let showRow = true;
        
        // Search filter
        if (searchTerm && !row.dataset.search.includes(searchTerm)) {
            showRow = false;
        }
        
        // Branch filter
        if (branchFilter && row.dataset.branch !== branchFilter) {
            showRow = false;
        }
        
        // Date filter
        if (dateFilter && !isDateInRange(row.dataset.date, dateFilter)) {
            showRow = false;
        }
        
        // Amount filter
        if (amountFilter && !isAmountInRange(parseFloat(row.dataset.amount), amountFilter)) {
            showRow = false;
        }
        
        if (showRow) {
            row.classList.remove('hidden-row');
            visibleCount++;
        } else {
            row.classList.add('hidden-row');
        }
    });
    
    // Update table header to show filter results
    const tableTitle = document.querySelector('.table-title');
    if (searchTerm || branchFilter || dateFilter || amountFilter) {
        tableTitle.innerHTML = `<span>📈</span> Sales Transactions (${visibleCount} found)`;
    } else {
        tableTitle.innerHTML = `<span>📈</span> Recent Sales Transactions`;
    }
}

function isDateInRange(dateString, range) {
    const saleDate = new Date(dateString);
    const today = new Date();
    
    switch (range) {
        case 'today':
            return saleDate.toDateString() === today.toDateString();
        case 'week':
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            return saleDate >= weekAgo;
        case 'month':
            const monthAgo = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
            return saleDate >= monthAgo;
        case 'quarter':
            const quarterAgo = new Date(today.getFullYear(), today.getMonth() - 3, today.getDate());
            return saleDate >= quarterAgo;
        case 'year':
            const yearAgo = new Date(today.getFullYear() - 1, today.getMonth(), today.getDate());
            return saleDate >= yearAgo;
        default:
            return true;
    }
}

function isAmountInRange(amount, range) {
    switch (range) {
        case '0-100':
            return amount >= 0 && amount <= 100;
        case '100-500':
            return amount > 100 && amount <= 500;
        case '500-1000':
            return amount > 500 && amount <= 1000;
        case '1000+':
            return amount > 1000;
        default:
            return true;
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('branchFilter').value = '';
    document.getElementById('dateFilter').value = '';
    document.getElementById('amountFilter').value = '';
    filterSales();
}

function exportSales() {
    const rows = document.querySelectorAll('#salesTableBody tr:not(.hidden-row)');
    
    if (rows.length === 0) {
        alert('No sales data to export');
        return;
    }
    
    let csvContent = 'ID,Product,Customer,Quantity,Unit Price,Branch,Total,Date\n';
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const rowData = [
            cells[0].textContent.trim(), // ID
            `"${cells[1].textContent.trim()}"`, // Product
            `"${cells[2].textContent.trim()}"`, // Customer
            cells[3].textContent.trim(), // Quantity
            cells[4].textContent.trim(), // Unit Price
            `"${cells[5].textContent.trim()}"`, // Branch
            cells[6].textContent.trim(), // Total
            `"${cells[7].textContent.trim()}"` // Date
        ];
        csvContent += rowData.join(',') + '\n';
    });
    
    // Add summary at the end
    const totalAmount = Array.from(rows).reduce((sum, row) => {
        const amountText = row.querySelector('td:nth-child(7)').textContent;
        const amount = parseFloat(amountText.replace('K', '').replace(',', ''));
        return sum + amount;
    }, 0);
    
    csvContent += '\nSummary:\n';
    csvContent += `Total Sales:,${rows.length}\n`;
    csvContent += `Total Revenue:,"K${totalAmount.toFixed(2)}"\n`;
    csvContent += `Export Date:,"${new Date().toLocaleString()}"\n`;
    
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `sales_export_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}
</script>

<!-- Custom Modals -->
<div id="modalOverlay" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Modal Title</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Modal content will be inserted here -->
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- Modal buttons will be inserted here -->
        </div>
    </div>
</div>

<script>
// Custom Modal System
class CustomModal {
    constructor() {
        this.overlay = document.getElementById('modalOverlay');
        this.title = document.getElementById('modalTitle');
        this.body = document.getElementById('modalBody');
        this.footer = document.getElementById('modalFooter');
        this.currentResolve = null;
        this.currentReject = null;
        
        // Close modal when clicking overlay
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.overlay.classList.contains('active')) {
                this.close();
            }
        });
    }
    
    open() {
        this.overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        this.overlay.classList.remove('active');
        document.body.style.overflow = '';
        if (this.currentReject) {
            this.currentReject('Modal closed');
            this.currentReject = null;
            this.currentResolve = null;
        }
    }
    
    showInfo(title, content) {
        this.title.innerHTML = title;
        this.body.innerHTML = `<div class="modal-info-content">${content}</div>`;
        this.footer.innerHTML = `
            <button class="modal-btn modal-btn-primary" onclick="closeModal()">
                <span>👍</span> OK
            </button>
        `;
        this.open();
    }
    
    showConfirmation(title, message, warning, confirmText) {
        return new Promise((resolve, reject) => {
            this.currentResolve = resolve;
            this.currentReject = reject;
            
            this.title.innerHTML = title;
            this.body.innerHTML = `
                <div class="modal-info-content">
                    <p style="margin-bottom: 1rem;">${message}</p>
                    <p style="color: #ef4444; font-weight: 500; font-size: 0.875rem;">${warning}</p>
                </div>
            `;
            this.footer.innerHTML = `
                <button class="modal-btn modal-btn-secondary" onclick="customModal.resolveConfirmation(false)">
                    <span>✖️</span> Cancel
                </button>
                <button class="modal-btn modal-btn-danger" onclick="customModal.resolveConfirmation(true)">
                    <span>🗑️</span> ${confirmText}
                </button>
            `;
            this.open();
        });
    }
    
    resolveConfirmation(result) {
        if (this.currentResolve) {
            this.currentResolve(result);
            this.currentResolve = null;
            this.currentReject = null;
        }
        this.close();
    }
    
    showForm(title, fields, options = {}) {
        return new Promise((resolve, reject) => {
            this.currentResolve = resolve;
            this.currentReject = reject;
            
            this.title.innerHTML = title;
            
            let formHTML = '<form id="modalForm">';
            fields.forEach(field => {
                if (field.type === 'hidden') {
                    formHTML += `<input type="hidden" name="${field.name}" value="${field.value || ''}">`;
                    return;
                }
                
                formHTML += `<div class="modal-form-group">`;
                formHTML += `<label for="${field.name}">${field.label}${field.required ? ' *' : ''}</label>`;
                
                if (field.type === 'select') {
                    formHTML += `<select name="${field.name}" id="${field.name}" ${field.required ? 'required' : ''}>`;
                    if (field.options) {
                        field.options.forEach(option => {
                            const selected = field.value == option.value ? 'selected' : '';
                            formHTML += `<option value="${option.value}" ${selected}>${option.text}</option>`;
                        });
                    }
                    formHTML += `</select>`;
                } else if (field.type === 'textarea') {
                    formHTML += `<textarea name="${field.name}" id="${field.name}" 
                        placeholder="${field.placeholder || ''}" ${field.required ? 'required' : ''}>${field.value || ''}</textarea>`;
                } else {
                    formHTML += `<input type="${field.type}" name="${field.name}" id="${field.name}" 
                        value="${field.value || ''}" placeholder="${field.placeholder || ''}" 
                        ${field.step ? `step="${field.step}"` : ''} ${field.required ? 'required' : ''}>`;
                }
                formHTML += `</div>`;
            });
            formHTML += '</form>';
            
            this.body.innerHTML = formHTML;
            this.footer.innerHTML = `
                <button class="modal-btn modal-btn-secondary" onclick="customModal.close()">
                    <span>✖️</span> Cancel
                </button>
                <button class="modal-btn modal-btn-primary" onclick="customModal.submitForm()">
                    <span>💾</span> ${options.submitText || 'Submit'}
                </button>
            `;
            this.open();
            
            // Focus first input
            setTimeout(() => {
                const firstInput = this.body.querySelector('input:not([type="hidden"]), select, textarea');
                if (firstInput) firstInput.focus();
            }, 100);
        });
    }
    
    submitForm() {
        const form = document.getElementById('modalForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        if (this.currentResolve) {
            this.currentResolve(data);
            this.currentResolve = null;
            this.currentReject = null;
        }
        this.close();
    }
}

// Initialize custom modal
const customModal = new CustomModal();

// Global function for closing modal
function closeModal() {
    customModal.close();
}
</script>

<?php $conn->close(); ?>
