<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$pageTitle = 'Rental Management';
$currentPage = 'rentals';
include '../includes/header.php';

// Fetch rental data from database
$conn = getDbConnection();
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month = isset($_GET['month']) ? $_GET['month'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$monthNames = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEPT","OCT","NOV","DEC"];

// Build query


// Get all rentals
$sql = "SELECT shop_no, client, contact FROM rentals ORDER BY shop_no ASC";
$stmt = $conn->prepare($sql);
$stmt->execute();
$rentalResult = $stmt->get_result();

// Build $rentals array with empty months
$rentals = [];
while ($row = $rentalResult->fetch_assoc()) {
    $rentals[$row['shop_no']] = [
        'shop_no' => $row['shop_no'],
        'client' => $row['client'],
        'contact' => $row['contact'],
        'months' => array_fill_keys($monthNames, 'UNPAID')
    ];
}
$stmt->close();

// Get all payments for selected year
$sql = "SELECT shop_no, month, date_paid FROM payments WHERE year = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $year);
$stmt->execute();
$paymentResult = $stmt->get_result();

while ($row = $paymentResult->fetch_assoc()) {
    $shop_no = $row['shop_no'];
    $month = $row['month'];
    if (isset($rentals[$shop_no]) && isset($rentals[$shop_no]['months'][$month])) {
        $rentals[$shop_no]['months'][$month] = ($row['date_paid'] && $row['date_paid'] !== '0000-00-00') ? 'PAID' : 'UNPAID';
    }
}
$rentals = array_values($rentals);
$stmt->close();
$conn->close();

// Filters
$filter_month = $_GET['month'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Helper: Get current year and month
$currentYear = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$currentMonth = isset($_GET['month']) && $_GET['month'] ? $_GET['month'] : $monthNames[intval(date('n'))-1];
$monthNames = ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEPT","OCT","NOV","DEC"];
$monthNumbers = array_flip($monthNames);

// Helper: Get last day and 5th of next month
function getDueWindow($year, $month) {
    $monthNum = array_search($month, ["JAN","FEB","MAR","APR","MAY","JUN","JUL","AUG","SEPT","OCT","NOV","DEC"]);
    $lastDay = date('Y-m-t', strtotime("$year-".($monthNum+1)."-01"));
    $fifthNext = date('Y-m-05', strtotime("$lastDay +1 month"));
    return [$lastDay, $fifthNext];
}

// Calculate metrics

// Rent amounts per client
$clientRent = [
    'ONE BABA SHOE SHOP' => 6000,
    'ONE LOVE COSMETICS 1' => 5000,
    'ONE LOVE COSMETICS 2' => 5000,
    'PHILIP COLOR CENTRE' => 5000,
    'BEST COSMETICS' => 5000,
    'MWAKAMO COSMETICS' => 5000,
    'MAT P GENERAL DEALERS' => 5000,
    'BACK ROOM' => 1000
];

$totalRevenue = 0;
$paidShops = 0;
$dueShops = 0;
foreach ($rentals as $r) {
    // Use selected year and month for metrics
    $status = getStatusForMonth($r['months'], $currentMonth);
    $amount = $clientRent[$r['client']] ?? 5000;
    if ($status === 'PAID') {
        $totalRevenue += $amount;
        $paidShops++;
    }
    // Due logic: only count as due if payment is not PAID and today is in due window
    list($lastDay, $fifthNext) = getDueWindow($currentYear, $currentMonth);
    $today = date('Y-m-d');
    if ($status !== 'PAID' && $today >= $lastDay && $today <= $fifthNext) {
        $dueShops++;
    }
}

function getStatusForMonth($months, $month) {
    return $months[$month] ?? '';
}

?>
<style>
/* Compact Table Styles */
.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    background: #fff;
}
.table th, .table td {
    padding: 0.25rem 0.3rem;
    border-bottom: 1px solid #e5e7eb;
    text-align: left;
    white-space: nowrap;
}
.table th {
    background: #f3f4f6;
    font-weight: 600;
    color: #374151;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}
.table tbody tr {
    transition: background 0.2s;
}
.table tbody tr:hover {
    background: #f9fafb;
}
.table td.text-green {
    color: #059669;
    font-weight: 500;
}
.table td.text-red {
    color: #dc2626;
    font-weight: 500;
}
.action-buttons {
    display: flex;
    gap: 0.25rem;
}
.btn.btn-sm {
    padding: 0.2rem 0.45rem;
    font-size: 0.75rem;
    border-radius: 4px;
}
.btn-success {
    background: #059669;
    color: #fff;
    border: none;
}
.btn-success:hover {
    background: #047857;
}
.btn-secondary {
    background: #6b7280;
    color: #fff;
    border: none;
}
.btn-secondary:hover {
    background: #4b5563;
}
.btn-danger {
    background: #dc2626;
    color: #fff;
    border: none;
}
.btn-danger:hover {
    background: #b91c1c;
}
.table th:last-child, .table td:last-child {
    text-align: right;
}
@media (max-width: 900px) {
    .table th, .table td {
        padding: 0.15rem 0.15rem;
        font-size: 0.75rem;
    }
}
.grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}
.card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 1rem;
}
.card-title {
    font-weight: 500;
    margin-bottom: 0.5rem;
}
.font-bold {
    font-weight: 700;
}
.text-sm {
    font-size: 0.875rem;
}
.text-gray {
    color: #6b7280;
}
.mb-4 {
    margin-bottom: 1.5rem;
}
/* Compact Filter Styles */
.card.mb-4 {
    padding: 0.75rem 1rem;
    background: #f9fafb;
    border-radius: 0.5rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    border: 1px solid #e5e7eb;
}
.form-inline {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
    margin: 0;
}
.form-inline label {
    font-size: 0.85rem;
    color: #374151;
    margin-right: 0.25rem;
    font-weight: 500;
}
.form-inline select {
    min-width: 70px;
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #374151;
    margin-right: 0.5rem;
}
.form-inline button {
    padding: 0.25rem 0.8rem;
    font-size: 0.8rem;
    border-radius: 4px;
    background: #2563eb;
    color: #fff;
    border: none;
    font-weight: 500;
    transition: background 0.2s;
}
.form-inline button:hover {
    background: #1e40af;
}
</style>
<div class="page-header">
    <h1>Rental Management</h1>
    <p>Manage rental payments, view status, and perform actions for SPF Shopping Complex.</p>
</div>
<div class="grid-3 mb-4">
    <div class="card">
        <div class="card-title">Total Rent Revenue</div>
        <div class="font-bold" style="font-size:1.5rem;">K <?= number_format($totalRevenue,2) ?></div>
        <div class="text-sm text-gray">for <?= $currentMonth ?> <?= $currentYear ?></div>
    </div>
    <div class="card">
        <div class="card-title">Number of Due Shops</div>
        <div class="font-bold" style="font-size:1.5rem;"><?= $dueShops ?></div>
        <div class="text-sm text-gray">Due: last day to 5th of next month</div>
    </div>
    <div class="card">
        <div class="card-title">Number of Paid Shops</div>
        <div class="font-bold" style="font-size:1.5rem;"><?= $paidShops ?></div>
        <div class="text-sm text-gray">for <?= $currentMonth ?> <?= $currentYear ?></div>
    </div>
</div>
<div class="card mb-4">
    <form method="get" class="form-inline" style="display:flex;gap:0.5rem;flex-wrap:nowrap;align-items:center;">
        <label for="year">Year:</label>
        <select name="year" id="year">
            <?php for($y=intval(date('Y'))-2;$y<=intval(date('Y'))+1;$y++): ?>
                <option value="<?= $y ?>" <?= $currentYear==$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
        <label for="month">Month:</label>
        <select name="month" id="month">
            <option value="">All</option>
            <?php foreach($monthNames as $m): ?>
                <option value="<?= $m ?>" <?= $currentMonth==$m?'selected':'' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>
        <label for="status">Status:</label>
        <select name="status" id="status">
            <option value="">All</option>
            <option value="PAID" <?= $filter_status=='PAID'?'selected':'' ?>>PAID</option>
            <option value="UNPAID" <?= $filter_status=='UNPAID'?'selected':'' ?>>UNPAID</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    </form>
</div>
<div class="card">
    <div class="card-header">
        <span class="card-title">Rental Payment Table</span>
        <div class="action-buttons">
            <button class="btn btn-success btn-sm" onclick="window.openAddModal('Rental Payment', [
                {name:'client',label:'Client',type:'select',required:true,options: rentalClients.map(c => ({value: c.client, text: c.client}))},
                {name:'month',label:'Month',type:'select',options:[{value:'JAN',text:'JAN'},{value:'FEB',text:'FEB'},{value:'MAR',text:'MAR'},{value:'APR',text:'APR'},{value:'MAY',text:'MAY'},{value:'JUN',text:'JUN'},{value:'JUL',text:'JUL'},{value:'AUG',text:'AUG'},{value:'SEPT',text:'SEPT'},{value:'OCT',text:'OCT'},{value:'NOV',text:'NOV'},{value:'DEC',text:'DEC'}],required:true},
                {name:'status',label:'Status',type:'select',options:[{value:'PAID',text:'PAID'},{value:'UNPAID',text:'UNPAID'}],required:true}
            ], 'add_handler.php')">Add Payment</button>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <?php foreach($monthNames as $m): ?>
                        <th><?= $m ?></th>
                    <?php endforeach; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($rentals as $r):
                    // Filter logic
                    $show = true;
                    if ($filter_month) {
                        $status = getStatusForMonth($r['months'], $filter_month);
                        if ($filter_status && (($filter_status=='PAID' && $status!=='PAID') || ($filter_status=='UNPAID' && $status==='PAID'))) {
                            $show = false;
                        }
                    }
                    // Show Not Due/Due/Paid for current month
                    $status = getStatusForMonth($r['months'], $currentMonth);
                    list($lastDay, $fifthNext) = getDueWindow($currentYear, $currentMonth);
                    $today = date('Y-m-d');
                    if ($status !== 'PAID' && $today < $lastDay) {
                        $dueLabel = 'Not Due';
                    } elseif ($status !== 'PAID' && $today >= $lastDay && $today <= $fifthNext) {
                        $dueLabel = 'Due';
                    } elseif ($status === 'PAID') {
                        $dueLabel = 'Paid';
                    } else {
                        $dueLabel = '';
                    }
                ?>
                <?php if($show): ?>
                <tr>
                    <td><?= htmlspecialchars($r['client']) ?></td>
                    <td><?= htmlspecialchars($r['contact']) ?></td>
                    <?php foreach($monthNames as $m): ?>
                        <td class="<?= getStatusForMonth($r['months'],$m)==='PAID'?'text-green':'text-red' ?>">
                            <?= htmlspecialchars(getStatusForMonth($r['months'],$m)) ?>
                            <?php if($m==$currentMonth): ?>
                                <?php if($dueLabel): ?>
                                    <div class="text-sm" style="font-weight:600; color:#374151;">
                                        <?= $dueLabel ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-secondary btn-sm" onclick="window.openEditModal('Rental Payment', [
                                {name:'client',label:'Client',type:'select',value:'<?= htmlspecialchars($r['client']) ?>',required:true,options: rentalClients.map(c => ({value: c.client, text: c.client}))},
                                {name:'contact',label:'Contact',type:'text',value:'<?= htmlspecialchars($r['contact']) ?>'},
                                {name:'month',label:'Month',type:'select',options:[{value:'JAN',text:'JAN'},{value:'FEB',text:'FEB'},{value:'MAR',text:'MAR'},{value:'APR',text:'APR'},{value:'MAY',text:'MAY'},{value:'JUN',text:'JUN'},{value:'JUL',text:'JUL'},{value:'AUG',text:'AUG'},{value:'SEPT',text:'SEPT'},{value:'OCT',text:'OCT'},{value:'NOV',text:'NOV'},{value:'DEC',text:'DEC'}],value:'',required:true},
                                {name:'status',label:'Status',type:'select',options:[{value:'PAID',text:'PAID'},{value:'UNPAID',text:'UNPAID'}],value:'',required:true}
                            ], 'edit_handler.php', <?= $r['shop_no'] ?>)">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="window.openDeleteModal('Rental Payment', '<?= htmlspecialchars($r['client']) ?>', 'delete_handler.php', <?= $r['shop_no'] ?>)">Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    const rentalClients = [
        {shop_no:10, client:"ONE BABA SHOE SHOP", contact:"979129597"},
        {shop_no:8, client:"ONE LOVE COSMETICS 1", contact:"969200544"},
        {shop_no:9, client:"ONE LOVE COSMETICS 2", contact:"969200544"},
        {shop_no:7, client:"PHILIP COLOR CENTRE", contact:"979784282"},
        {shop_no:4, client:"BEST COSMETICS", contact:"977332882"},
        {shop_no:3, client:"MWAKAMO COSMETICS", contact:""},
        {shop_no:1, client:"MAT P GENERAL DEALERS", contact:""},
        {shop_no:0, client:"BACK ROOM", contact:""}
    ];

    window.openAddModal = function(title, fields, handler) {
        fields = fields.map(f => {
            if (f.name === 'client') {
                f.type = 'select';
                f.options = rentalClients.map(c => ({value: c.client, text: c.client}));
            }
            return f;
        });
        window.modal.showForm(title, fields, {submitText: 'Add'}).then(data => {
            // Only submit client, month, status
            window.submitForm(handler, {
                client: data.client,
                month: data.month,
                status: data.status
            }, 'POST');
        });
    };
</script>
<?php include '../includes/footer.php'; ?>
