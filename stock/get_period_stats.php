<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$conn = getDbConnection();
$period = $_GET['period'] ?? 'month';

// Calculate date ranges based on period
$dateConditions = [];
$currentDate = date('Y-m-d');

switch($period) {
    case 'today':
        $startDate = $currentDate;
        $endDate = $currentDate;
        break;
    case 'week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'quarter':
        $currentMonth = date('n');
        $quarterStartMonth = (ceil($currentMonth / 3) - 1) * 3 + 1;
        $startDate = date('Y-' . sprintf('%02d', $quarterStartMonth) . '-01');
        $endDate = date('Y-m-t', strtotime($startDate . ' +2 months'));
        break;
    case 'year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
    default:
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
}

// Fetch all products
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

// Calculate stock movements for the period
$totalProducts = 0;
$outOfStock = 0;
$lowStock = 0;
$totalValue = 0;
$periodPurchases = 0;
$periodSales = 0;
$periodTransfers = 0;

// Get period-specific data
$periodPurchasesQuery = $conn->query("
    SELECT SUM(quantity) as total, SUM(quantity * unit_price) as value 
    FROM purchases 
    WHERE date >= '$startDate' AND date <= '$endDate'
");
$periodPurchasesData = $periodPurchasesQuery->fetch_assoc();
$periodPurchases = intval($periodPurchasesData['total'] ?? 0);
$periodPurchaseValue = floatval($periodPurchasesData['value'] ?? 0);

$periodSalesQuery = $conn->query("
    SELECT SUM(quantity) as total, SUM(quantity * unit_price) as value 
    FROM sales 
    WHERE date >= '$startDate' AND date <= '$endDate'
");
$periodSalesData = $periodSalesQuery->fetch_assoc();
$periodSales = intval($periodSalesData['total'] ?? 0);
$periodSalesValue = floatval($periodSalesData['value'] ?? 0);

$periodTransfersQuery = $conn->query("
    SELECT COUNT(*) as total 
    FROM transfers 
    WHERE date >= '$startDate' AND date <= '$endDate'
");
$periodTransfersData = $periodTransfersQuery->fetch_assoc();
$periodTransfers = intval($periodTransfersData['total'] ?? 0);

// Calculate current stock levels (this remains the same as it's current state)
foreach ($products as $pid => $product) {
    $productTotalStock = 0;
    foreach ($branches as $branch) {
        // Purchases (all time for current stock)
        $purchases = $conn->query("SELECT SUM(quantity - damages) AS total FROM purchases WHERE product_id = $pid AND branch = '$branch'");
        $purchased = ($row = $purchases->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Sales (all time for current stock)
        $sales = $conn->query("SELECT SUM(quantity) AS total FROM sales WHERE product_id = $pid AND branch = '$branch'");
        $sold = ($row = $sales->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers out (all time for current stock)
        $transfersOut = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND source_branch = '$branch'");
        $out = ($row = $transfersOut->fetch_assoc()) ? intval($row['total']) : 0;
        
        // Transfers in (all time for current stock)
        $transfersIn = $conn->query("SELECT SUM(quantity) AS total FROM transfers WHERE product_id = $pid AND dest_branch = '$branch'");
        $in = ($row = $transfersIn->fetch_assoc()) ? intval($row['total']) : 0;
        
        $branchStock = $purchased - $sold - $out + $in;
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

// For period-specific insights, let's get products that had activity in this period
$activeProductsQuery = $conn->query("
    SELECT DISTINCT product_id 
    FROM (
        SELECT product_id FROM purchases WHERE date >= '$startDate' AND date <= '$endDate'
        UNION
        SELECT product_id FROM sales WHERE date >= '$startDate' AND date <= '$endDate'
        UNION
        SELECT product_id FROM transfers WHERE date >= '$startDate' AND date <= '$endDate'
    ) as active_products
");

$activeProductsCount = $activeProductsQuery->num_rows;

// Get products that went out of stock in this period
$newOutOfStockQuery = $conn->query("
    SELECT COUNT(DISTINCT p.product_id) as count
    FROM sales p
    WHERE p.date >= '$startDate' AND p.date <= '$endDate'
    AND (
        SELECT SUM(pur.quantity - pur.damages) - SUM(s.quantity) - 
               COALESCE(SUM(t_out.quantity), 0) + COALESCE(SUM(t_in.quantity), 0)
        FROM purchases pur
        LEFT JOIN sales s ON s.product_id = p.product_id AND s.branch = p.branch
        LEFT JOIN transfers t_out ON t_out.product_id = p.product_id AND t_out.source_branch = p.branch
        LEFT JOIN transfers t_in ON t_in.product_id = p.product_id AND t_in.dest_branch = p.branch
        WHERE pur.product_id = p.product_id AND pur.branch = p.branch
    ) <= 0
");
$newOutOfStockData = $newOutOfStockQuery->fetch_assoc();
$periodOutOfStock = intval($newOutOfStockData['count'] ?? 0);

// Calculate period-specific metrics
$response = [
    'period' => $period,
    'period_label' => ucfirst($period),
    'date_range' => [
        'start' => $startDate,
        'end' => $endDate
    ],
    'stats' => [
        'totalProducts' => $activeProductsCount > 0 ? $activeProductsCount : $totalProducts,
        'outOfStock' => $periodOutOfStock > 0 ? $periodOutOfStock : $outOfStock,
        'lowStock' => $lowStock, // Current low stock items
        'totalValue' => number_format($totalValue, 2)
    ],
    'period_activity' => [
        'purchases' => $periodPurchases,
        'sales' => $periodSales,
        'transfers' => $periodTransfers,
        'purchase_value' => $periodPurchaseValue,
        'sales_value' => $periodSalesValue
    ],
    'insights' => [
        'active_products' => $activeProductsCount,
        'new_out_of_stock' => $periodOutOfStock,
        'net_stock_change' => $periodPurchases - $periodSales
    ]
];

$conn->close();

echo json_encode($response);
?>
