<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Validate POST data

$client = $_POST['client'] ?? '';
$months = $_POST['months'] ?? [];
$status = $_POST['status'] ?? '';
$year = date('Y'); // Default to current year

if (!$client || !$months || !$status || !is_array($months)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit;
}

// Find shop_no for client
$conn = getDbConnection();
$stmt = $conn->prepare('SELECT shop_no FROM rentals WHERE client = ? LIMIT 1');
$stmt->bind_param('s', $client);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $shop_no = $row['shop_no'];
} else {
    echo json_encode(['success' => false, 'error' => 'Client not found.']);
    exit;
}
$stmt->close();

$who_paid = $_SESSION['username'] ?? 'admin';
$errors = [];
foreach ($months as $month) {
    // Check if payment already exists for this shop/month/year
    $stmt = $conn->prepare('SELECT id FROM payments WHERE shop_no = ? AND month = ? AND year = ?');
    $stmt->bind_param('ssi', $shop_no, $month, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->fetch_assoc()) {
        $errors[] = "Payment already exists for $client ($month $year)";
        $stmt->close();
        continue;
    }
    $stmt->close();

    // Insert payment
    $date_paid = ($status === 'PAID') ? date('Y-m-d') : null;
    $stmt = $conn->prepare('INSERT INTO payments (shop_no, client, month, year, date_paid, who_paid) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $shop_no, $client, $month, $year, $date_paid, $who_paid);
    if (!$stmt->execute()) {
        $errors[] = "Error for $client ($month $year): " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();

if (empty($errors)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $errors]);
}
?>
