<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT id, name, category, price, branches FROM products ORDER BY id ASC');

// Set page variables for header
$pageTitle = 'Products - LedgerLink Inventory';
$currentPage = 'products';
?>
<?php include '../includes/header.php'; ?>

<!-- Custom Products Page Styles -->
<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
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
    
    .stat-card.purple { 
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    }
    .stat-card.pink { 
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); 
    }
    .stat-card.blue { 
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); 
    }
    .stat-card.orange { 
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); 
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
        color: white;
    }
    
    .stat-label {
        font-size: 14px;
        opacity: 0.9;
        color: white;
        font-weight: 500;
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
    
    /* Modern Filter Options Card */
    .filter-options-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        border: 1px solid #e8e8e8;
    }
    
    .filter-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .filter-icon {
        font-size: 20px;
        color: #2196f3;
    }
    
    .filter-title {
        font-size: 18px;
        font-weight: 600;
        color: #333;
        margin: 0;
    }
    
    .filter-content {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    .filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        align-items: end;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .filter-label {
        font-weight: 600;
        color: #555;
        font-size: 14px;
        margin-bottom: 4px;
    }
    
    .filter-select, .filter-date {
        padding: 12px 16px;
        border: 2px solid #e8e8e8;
        border-radius: 8px;
        background: white;
        font-size: 14px;
        color: #333;
        transition: all 0.2s ease;
        box-sizing: border-box;
    }
    
    .filter-select:focus, .filter-date:focus {
        outline: none;
        border-color: #2196f3;
        box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
    }
    
    .filter-date {
        font-family: inherit;
    }
    
    .filter-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-start;
        padding-top: 8px;
    }
    
    .filter-btn {
        background: linear-gradient(135deg, #ef5350, #f44336);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(239, 83, 80, 0.3);
    }
    
    .filter-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 83, 80, 0.4);
    }
    
    .clear-btn {
        background: #f8f9fa;
        color: #6c757d;
        border: 2px solid #e9ecef;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .clear-btn:hover {
        background: #e9ecef;
        color: #495057;
        transform: translateY(-1px);
    }
    
    @media (max-width: 768px) {
        .filter-row {
            grid-template-columns: 1fr;
        }
        
        .filter-actions {
            flex-direction: column;
        }
        
        .filter-btn, .clear-btn {
            justify-content: center;
        }
    }
    
    .products-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .products-header {
        background: #f8f9fa;
        padding: 1.5rem 2rem;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .products-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #333;
        margin: 0;
    }
    
    .add-product-btn {
        background: linear-gradient(135deg, #2196f3, #1976d2);
        color: white;
        border: none;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.875rem;
        cursor: pointer;
        transition: transform 0.2s;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .add-product-btn:hover {
        transform: translateY(-2px);
    }
    
    .table-wrapper {
        overflow-x: auto;
    }
    
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .modern-table thead th {
        background: #f8f9fa;
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        color: #2196f3;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e0e0e0;
    }
    
    .modern-table tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }
    
    .modern-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .modern-table tbody td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
    }
    
    .product-name {
        font-weight: 600;
        color: #333;
        font-size: 1rem;
    }
    
    .product-category {
        background: #e3f2fd;
        color: #1976d2;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 500;
        display: inline-block;
    }
    
    .product-price {
        font-weight: 700;
        color: #4caf50;
        font-size: 1.1rem;
    }
    
    .product-branches {
        color: #666;
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .action-buttons-modern {
        display: flex;
        gap: 0.5rem;
    }
    
    .btn-edit {
        background: #fff3e0;
        color: #f57c00;
        border: 1px solid #ffcc02;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-edit:hover {
        background: #f57c00;
        color: white;
    }
    
    .btn-delete {
        background: #ffebee;
        color: #d32f2f;
        border: 1px solid #f44336;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-delete:hover {
        background: #d32f2f;
        color: white;
    }
</style>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php
    // Calculate statistics
    $conn_stats = getDbConnection();
    $total_products = $conn_stats->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $categories = $conn_stats->query("SELECT COUNT(DISTINCT category) as count FROM products")->fetch_assoc()['count'];
    $active_products = $total_products; // For now, assume all are active
    $inactive_products = 0; // For now
    $conn_stats->close();
    ?>
    
    <div class="stat-card purple">
        <div class="stat-icon">📦</div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $total_products; ?></div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    
    <div class="stat-card pink">
        <div class="stat-icon">💰</div>
        <div class="stat-content">
            <div class="stat-number">K<?php echo number_format($categories * 1000, 2); ?></div>
            <div class="stat-label">Product Value</div>
        </div>
    </div>
    
    <div class="stat-card blue">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $active_products; ?> items</div>
            <div class="stat-label">Active Inventory</div>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-options-card">
    <div class="filter-header">
        <span class="filter-icon">🔍</span>
        <h3 class="filter-title">Filter Options</h3>
    </div>
    <div class="filter-content">
        <div class="filter-row">
            <div class="filter-group">
                <label class="filter-label">Category:</label>
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <option value="Brick">Brick</option>
                    <option value="Water Storage">Water Storage</option>
                    <option value="Paver">Paver</option>
                    <option value="Kerb Stone">Kerb Stone</option>
                    <option value="Block">Block</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Branch:</label>
                <select class="filter-select" id="branchFilter">
                    <option value="">All Branches</option>
                    <option value="Livingstone">Livingstone</option>
                    <option value="Chisamba">Chisamba</option>
                    <option value="Lusaka">Lusaka</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">From Date:</label>
                <input type="date" class="filter-date" id="fromDate" placeholder="dd/mm/yyyy">
            </div>
            
            <div class="filter-group">
                <label class="filter-label">To Date:</label>
                <input type="date" class="filter-date" id="toDate" placeholder="dd/mm/yyyy">
            </div>
        </div>
        
        <div class="filter-actions">
            <button class="filter-btn" onclick="applyFilters()">🔍 Filter</button>
            <button class="clear-btn" onclick="clearFilters()">🗑️ Clear</button>
        </div>
    </div>
</div>

<!-- Products Container -->
<div class="products-container">
    <div class="products-header">
        <h2 class="products-title">Master Product Catalog</h2>
        <button class="add-product-btn" onclick="showAddProductModal()">
            + Add New Product
        </button>
    </div>

<!-- Products Table -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price (K)</th>
                <th>Available Branches</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td class="text-green font-bold">K<?php echo number_format($row['price'], 2); ?></td>
                    <td class="text-sm"><?php echo htmlspecialchars($row['branches']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <?php
                            $editData = [
                                "type" => "Product",
                                "url" => "edit_handler.php",
                                "id" => $row['id'],
                                "fields" => [
                                    [
                                        "name" => "name",
                                        "label" => "Product Name",
                                        "type" => "text",
                                        "required" => true,
                                        "value" => $row['name']
                                    ],
                                    [
                                        "name" => "category",
                                        "label" => "Category",
                                        "type" => "select",
                                        "required" => true,
                                        "value" => $row['category'],
                                        "options" => [
                                            ["value" => "Brick", "text" => "Brick"],
                                            ["value" => "Water Storage", "text" => "Water Storage"],
                                            ["value" => "Paver", "text" => "Paver"],
                                            ["value" => "Kerb Stone", "text" => "Kerb Stone"],
                                            ["value" => "Block", "text" => "Block"]
                                        ]
                                    ],
                                    [
                                        "name" => "price",
                                        "label" => "Price (K)",
                                        "type" => "number",
                                        "step" => "0.01",
                                        "required" => true,
                                        "value" => $row['price']
                                    ],
                                    [
                                        "name" => "branches",
                                        "label" => "Available Branches",
                                        "type" => "text",
                                        "required" => true,
                                        "value" => $row['branches']
                                    ]
                                ]
                            ];
                            $deleteData = [
                                "type" => "Product",
                                "name" => $row['name'],
                                "url" => "delete_handler.php",
                                "id" => $row['id']
                            ];
                            ?>
                            <button class="btn btn-outline btn-sm" data-modal-edit='<?php echo json_encode($editData, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>Edit</button>
                            <button class="btn btn-danger btn-sm" data-modal-delete='<?php echo json_encode($deleteData, JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>Delete</button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Filter functionality
function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value.toLowerCase();
    const branchFilter = document.getElementById('branchFilter').value.toLowerCase();
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    tableRows.forEach(row => {
        const category = row.cells[2].textContent.toLowerCase();
        const branches = row.cells[4].textContent.toLowerCase();
        
        let showRow = true;
        
        // Category filter
        if (categoryFilter && !category.includes(categoryFilter)) {
            showRow = false;
        }
        
        // Branch filter
        if (branchFilter && !branches.includes(branchFilter)) {
            showRow = false;
        }
        
        // Date filters would need additional date column in products table
        // For now, we'll skip date filtering on products
        
        row.style.display = showRow ? '' : 'none';
    });
    
    // Show feedback
    const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none').length;
    console.log(`Showing ${visibleRows} products after filtering`);
}

function clearFilters() {
    // Reset all filter inputs
    document.getElementById('categoryFilter').value = '';
    document.getElementById('branchFilter').value = '';
    document.getElementById('fromDate').value = '';
    document.getElementById('toDate').value = '';
    
    // Show all table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.style.display = '';
    });
    
    console.log('Filters cleared, showing all products');
}

// Product modal functions
function showAddProductModal() {
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    window.modal.showForm('Add New Product', [
        {name: 'name', label: 'Product Name', type: 'text', required: true, placeholder: 'Enter product name'},
        {name: 'category', label: 'Category', type: 'select', required: true, options: [
            {value: 'Brick', text: 'Brick'},
            {value: 'Water Storage', text: 'Water Storage'},
            {value: 'Paver', text: 'Paver'},
            {value: 'Kerb Stone', text: 'Kerb Stone'},
            {value: 'Block', text: 'Block'}
        ]},
        {name: 'price', label: 'Price (K)', type: 'number', step: '0.01', required: true, placeholder: '0.00'},
        {name: 'branches', label: 'Available Branches', type: 'text', required: true, placeholder: 'e.g., Livingstone, Chisamba, Lusaka'}
    ], {submitText: 'Add Product'}).then(data => {
        window.submitForm('add_handler.php', data, 'POST');
    });
}

// Initialize modal system
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        if (!window.modal && typeof ModalSystem !== 'undefined') {
            window.modal = new ModalSystem();
        }
    }, 100);
});
</script>
<?php $conn->close(); ?>
