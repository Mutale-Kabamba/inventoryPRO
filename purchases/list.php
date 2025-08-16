<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$conn = getDbConnection();
$result = $conn->query('SELECT p.id, pr.name AS product_name, p.quantity, p.unit_price, p.total_cost, p.supplier, p.branch, p.damages, p.purchase_date, p.product_id FROM purchases p JOIN products pr ON p.product_id = pr.id ORDER BY p.purchase_date DESC');

// Get products for modal forms
$products = [];
$productResult = $conn->query('SELECT id, name, price FROM products ORDER BY name ASC');
while ($productRow = $productResult->fetch_assoc()) {
    $products[] = $productRow;
}

$branches = ['Livingstone', 'Chisamba', 'Lusaka'];

// Calculate statistics for different periods
$stats = [];

// Today's purchases
$today = date('Y-m-d');
$todayQuery = "SELECT COUNT(*) as count, SUM(total_cost) as revenue, SUM(quantity) as quantity FROM purchases WHERE DATE(purchase_date) = '$today'";
$todayResult = $conn->query($todayQuery);
$todayData = $todayResult->fetch_assoc();
$stats['today_purchases'] = $todayData['count'] ?? 0;
$stats['today_cost'] = $todayData['revenue'] ?? 0;
$stats['today_quantity'] = $todayData['quantity'] ?? 0;

// This week's purchases
$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekQuery = "SELECT COUNT(*) as count, SUM(total_cost) as revenue, SUM(quantity) as quantity FROM purchases WHERE DATE(purchase_date) >= '$weekStart'";
$weekResult = $conn->query($weekQuery);
$weekData = $weekResult->fetch_assoc();
$stats['week_purchases'] = $weekData['count'] ?? 0;
$stats['week_cost'] = $weekData['revenue'] ?? 0;
$stats['week_quantity'] = $weekData['quantity'] ?? 0;

// This month's purchases
$monthStart = date('Y-m-01');
$monthQuery = "SELECT COUNT(*) as count, SUM(total_cost) as revenue, SUM(quantity) as quantity FROM purchases WHERE DATE(purchase_date) >= '$monthStart'";
$monthResult = $conn->query($monthQuery);
$monthData = $monthResult->fetch_assoc();
$stats['month_purchases'] = $monthData['count'] ?? 0;
$stats['month_cost'] = $monthData['revenue'] ?? 0;
$stats['month_quantity'] = $monthData['quantity'] ?? 0;

// This quarter's purchases
$quarterStart = date('Y-m-d', strtotime(date('Y') . '-' . (ceil(date('n') / 3) * 3 - 2) . '-01'));
$quarterQuery = "SELECT COUNT(*) as count, SUM(total_cost) as revenue, SUM(quantity) as quantity FROM purchases WHERE DATE(purchase_date) >= '$quarterStart'";
$quarterResult = $conn->query($quarterQuery);
$quarterData = $quarterResult->fetch_assoc();
$stats['quarter_purchases'] = $quarterData['count'] ?? 0;
$stats['quarter_cost'] = $quarterData['revenue'] ?? 0;
$stats['quarter_quantity'] = $quarterData['quantity'] ?? 0;

// This year's purchases
$yearStart = date('Y-01-01');
$yearQuery = "SELECT COUNT(*) as count, SUM(total_cost) as revenue, SUM(quantity) as quantity FROM purchases WHERE DATE(purchase_date) >= '$yearStart'";
$yearResult = $conn->query($yearQuery);
$yearData = $yearResult->fetch_assoc();
$stats['year_purchases'] = $yearData['count'] ?? 0;
$stats['year_cost'] = $yearData['revenue'] ?? 0;
$stats['year_quantity'] = $yearData['quantity'] ?? 0;

// Set page variables for header
$pageTitle = 'Purchases - LedgerLink Inventory';
$currentPage = 'purchases';
?>
<?php include '../includes/header.php'; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="card-header">
        <div>
            <h1>Purchases</h1>
            <p>Track inventory purchases and supplier information</p>
        </div>
        <button class="btn btn-primary" onclick="showAddPurchaseModal()">Record New Purchase</button>
    </div>
</div>

<!-- Period Performance -->
<div class="period-performance-card">
    <div class="performance-header">
        <h3 class="performance-title">Period Performance</h3>
        <div class="period-tabs">
            <button class="period-tab active" data-period="day">Day</button>
            <button class="period-tab" data-period="week">Week</button>
            <button class="period-tab" data-period="month">Month</button>
            <button class="period-tab" data-period="quarter">Quarter</button>
            <button class="period-tab" data-period="year">Year</button>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card purchases">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
                <div class="stat-value" id="purchases-value"><?php echo number_format($stats['today_purchases']); ?></div>
                <div class="stat-label" id="purchases-label">Today's Purchases</div>
            </div>
        </div>
        <div class="stat-card cost">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value" id="cost-value">K<?php echo number_format($stats['today_cost'], 2); ?></div>
                <div class="stat-label" id="cost-label">Today's Cost</div>
            </div>
        </div>
        <div class="stat-card quantity">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <div class="stat-value" id="quantity-value"><?php echo number_format($stats['today_quantity']); ?> items</div>
                <div class="stat-label" id="quantity-label">Today's Quantity</div>
            </div>
        </div>
    </div>
</div>

<!-- Purchases Table -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Cost</th>
                <th>Supplier</th>
                <th>Branch</th>
                <th>Damages</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td class="font-bold"><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td class="text-blue font-bold">K<?php echo number_format($row['unit_price'], 2); ?></td>
                    <td class="text-green font-bold">K<?php echo number_format($row['total_cost'], 2); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                    <td><?php echo htmlspecialchars($row['branch']); ?></td>
                    <td class="<?php echo $row['damages'] > 0 ? 'text-red' : 'text-gray'; ?>">
                        <?php echo $row['damages']; ?>
                    </td>
                    <td class="text-sm"><?php echo date('M j, Y g:i A', strtotime($row['purchase_date'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-outline btn-sm" onclick="showEditPurchaseModal(<?php echo $row['id']; ?>, <?php echo $row['product_id']; ?>, <?php echo $row['quantity']; ?>, <?php echo $row['unit_price']; ?>, '<?php echo htmlspecialchars($row['supplier']); ?>', '<?php echo htmlspecialchars($row['branch']); ?>', <?php echo $row['damages']; ?>)">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="showDeletePurchaseModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['product_name']).' - '.htmlspecialchars($row['supplier']).' ('.date('M j, Y', strtotime($row['purchase_date'])).')'; ?>')">Delete</button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>

<style>
/* Period Performance Card */
.period-performance-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin: 20px 0;
    padding: 24px;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.performance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.performance-title {
    font-size: 20px;
    font-weight: 600;
    color: #1a202c;
    margin: 0;
}

.period-tabs {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.period-tab {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    background: #f7fafc;
    color: #4a5568;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 14px;
}

.period-tab:hover {
    background: #e2e8f0;
    color: #2d3748;
}

.period-tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.stat-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 12px;
    padding: 20px;
    color: white;
    position: relative;
    overflow: hidden;
}

.stat-card.purchases {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.cost {
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

.stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    font-size: 28px;
    opacity: 0.9;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 4px;
    transition: opacity 0.3s ease;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
    transition: opacity 0.3s ease;
}

@media (max-width: 768px) {
    .performance-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .period-tabs {
        width: 100%;
        justify-content: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Period Performance Tab Functionality
document.addEventListener('DOMContentLoaded', function() {
    const periodData = {
        day: {
            purchases: '<?php echo number_format($stats['today_purchases']); ?>',
            cost: '<?php echo number_format($stats['today_cost'], 2); ?>',
            quantity: '<?php echo number_format($stats['today_quantity']); ?>',
            purchasesLabel: "Today's Purchases",
            costLabel: "Today's Cost",
            quantityLabel: "Today's Quantity"
        },
        week: {
            purchases: '<?php echo number_format($stats['week_purchases']); ?>',
            cost: '<?php echo number_format($stats['week_cost'], 2); ?>',
            quantity: '<?php echo number_format($stats['week_quantity']); ?>',
            purchasesLabel: "This Week's Purchases",
            costLabel: "This Week's Cost",
            quantityLabel: "This Week's Quantity"
        },
        month: {
            purchases: '<?php echo number_format($stats['month_purchases']); ?>',
            cost: '<?php echo number_format($stats['month_cost'], 2); ?>',
            quantity: '<?php echo number_format($stats['month_quantity']); ?>',
            purchasesLabel: "This Month's Purchases",
            costLabel: "This Month's Cost",
            quantityLabel: "This Month's Quantity"
        },
        quarter: {
            purchases: '<?php echo number_format($stats['quarter_purchases']); ?>',
            cost: '<?php echo number_format($stats['quarter_cost'], 2); ?>',
            quantity: '<?php echo number_format($stats['quarter_quantity']); ?>',
            purchasesLabel: "This Quarter's Purchases",
            costLabel: "This Quarter's Cost",
            quantityLabel: "This Quarter's Quantity"
        },
        year: {
            purchases: '<?php echo number_format($stats['year_purchases']); ?>',
            cost: '<?php echo number_format($stats['year_cost'], 2); ?>',
            quantity: '<?php echo number_format($stats['year_quantity']); ?>',
            purchasesLabel: "This Year's Purchases",
            costLabel: "This Year's Cost",
            quantityLabel: "This Year's Quantity"
        }
    };
    
    const tabs = document.querySelectorAll('.period-tab');
    const purchasesValue = document.getElementById('purchases-value');
    const costValue = document.getElementById('cost-value');
    const quantityValue = document.getElementById('quantity-value');
    const purchasesLabel = document.getElementById('purchases-label');
    const costLabel = document.getElementById('cost-label');
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
                purchasesValue, costValue, quantityValue,
                purchasesLabel, costLabel, quantityLabel
            ];
            
            elementsToUpdate.forEach(element => {
                if (element) element.style.opacity = '0.5';
            });
            
            setTimeout(() => {
                // Update values
                purchasesValue.textContent = data.purchases;
                costValue.textContent = 'K' + data.cost;
                quantityValue.textContent = data.quantity + ' items';
                
                // Update labels
                purchasesLabel.textContent = data.purchasesLabel;
                costLabel.textContent = data.costLabel;
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

// Purchase modal functions
function showAddPurchaseModal() {
    console.log('Add purchase modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name']]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Record New Purchase', [
        {name: 'product_id', label: 'Product', type: 'select', required: true, options: products},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, placeholder: 'Enter quantity purchased'},
        {name: 'unit_price', label: 'Unit Price', type: 'number', step: '0.01', required: true, placeholder: '0.00'},
        {name: 'supplier', label: 'Supplier', type: 'text', required: true, placeholder: 'Supplier name'},
        {name: 'branch', label: 'Branch', type: 'select', required: true, options: branches},
        {name: 'damages', label: 'Damages', type: 'number', placeholder: '0', value: '0'}
    ], {submitText: 'Record Purchase'}).then(data => {
        window.submitForm('add_handler.php', data, 'POST');
    });
}

function showEditPurchaseModal(id, productId, quantity, unitPrice, supplier, branch, damages) {
    console.log('Edit purchase modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    const products = <?php echo json_encode(array_map(function($product) { 
        return ['value' => $product['id'], 'text' => $product['name']]; 
    }, $products)); ?>;
    
    const branches = <?php echo json_encode(array_map(function($branch) { 
        return ['value' => $branch, 'text' => $branch]; 
    }, $branches)); ?>;
    
    window.modal.showForm('Edit Purchase', [
        {name: 'id', label: 'ID', type: 'hidden', value: id},
        {name: 'product_id', label: 'Product', type: 'select', required: true, value: productId, options: products},
        {name: 'quantity', label: 'Quantity', type: 'number', required: true, value: quantity},
        {name: 'unit_price', label: 'Unit Price', type: 'number', step: '0.01', required: true, value: unitPrice},
        {name: 'supplier', label: 'Supplier', type: 'text', required: true, value: supplier},
        {name: 'branch', label: 'Branch', type: 'select', required: true, value: branch, options: branches},
        {name: 'damages', label: 'Damages', type: 'number', value: damages}
    ], {submitText: 'Update Purchase'}).then(data => {
        window.submitForm('edit_handler.php', data, 'POST');
    });
}

function showDeletePurchaseModal(id, name) {
    console.log('Delete purchase modal clicked');
    if (typeof window.modal === 'undefined') {
        alert('Modal system not loaded!');
        return;
    }
    
    window.modal.showConfirmation(
        'Delete Purchase',
        `Are you sure you want to delete this purchase: ${name}?`,
        'This action cannot be undone.',
        'Delete Purchase'
    ).then(confirmed => {
        if (confirmed) {
            window.submitForm('delete_handler.php', {id: id}, 'POST');
        }
    });
}
</script>

<?php $conn->close(); ?>
