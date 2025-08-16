<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

// Set content type to JSON
header('Content-Type: application/json');

$conn = getDbConnection();

// Get parameters
$period = $_GET['period'] ?? 'week';
$branch_filter = $_GET['branch'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build date filter based on period
$date_condition = '';
switch($period) {
    case 'day':
        $date_condition = "DATE(s.sale_date) = CURDATE()";
        break;
    case 'week':
        $date_condition = "YEARWEEK(s.sale_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $date_condition = "YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE())";
        break;
    case 'quarter':
        $date_condition = "YEAR(s.sale_date) = YEAR(CURDATE()) AND QUARTER(s.sale_date) = QUARTER(CURDATE())";
        break;
    case 'year':
        $date_condition = "YEAR(s.sale_date) = YEAR(CURDATE())";
        break;
    default:
        $date_condition = "YEARWEEK(s.sale_date, 1) = YEARWEEK(CURDATE(), 1)";
}

// Build additional filters
$where_conditions = [$date_condition];

if ($branch_filter) {
    $where_conditions[] = "s.branch = '" . $conn->real_escape_string($branch_filter) . "'";
}
if ($category_filter) {
    $where_conditions[] = "p.category = '" . $conn->real_escape_string($category_filter) . "'";
}

// Override date conditions if custom date range is provided
if ($date_from && $date_to) {
    // Remove the period-based date condition and use custom range
    $where_conditions = array_filter($where_conditions, function($condition) {
        return !preg_match('/DATE\(|YEARWEEK\(|YEAR\(|QUARTER\(/', $condition);
    });
    $where_conditions[] = "DATE(s.sale_date) >= '" . $conn->real_escape_string($date_from) . "'";
    $where_conditions[] = "DATE(s.sale_date) <= '" . $conn->real_escape_string($date_to) . "'";
} elseif ($date_from) {
    $where_conditions = array_filter($where_conditions, function($condition) {
        return !preg_match('/DATE\(|YEARWEEK\(|YEAR\(|QUARTER\(/', $condition);
    });
    $where_conditions[] = "DATE(s.sale_date) >= '" . $conn->real_escape_string($date_from) . "'";
} elseif ($date_to) {
    $where_conditions = array_filter($where_conditions, function($condition) {
        return !preg_match('/DATE\(|YEARWEEK\(|YEAR\(|QUARTER\(/', $condition);
    });
    $where_conditions[] = "DATE(s.sale_date) <= '" . $conn->real_escape_string($date_to) . "'";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Query to get performance metrics
$query = "SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(s.total), 0) as total_revenue,
            COALESCE(SUM(s.quantity), 0) as total_quantity
          FROM sales s 
          JOIN products p ON s.product_id = p.id 
          $where_clause";

$result = $conn->query($query);

if ($result) {
    $data = $result->fetch_assoc();
    
    // Ensure numeric values
    $response = [
        'total_transactions' => (int)$data['total_transactions'],
        'total_revenue' => (float)$data['total_revenue'],
        'total_quantity' => (int)$data['total_quantity'],
        'period' => $period
    ];
    
    echo json_encode($response);
} else {
    // Error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Database query failed',
        'message' => $conn->error
    ]);
}

$conn->close();
?>
