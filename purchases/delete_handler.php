<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid purchase ID.']);
    exit;
}

$conn = getDbConnection();
$stmt = $conn->prepare('DELETE FROM purchases WHERE id = ?');
$stmt->bind_param('i', $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error deleting purchase.']);
}
$stmt->close();
$conn->close();
?>
<?php
session_start();
require_once '../includes/auth.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $conn = getDbConnection();
    
    $id = $_POST['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Purchase ID is required']);
        exit;
    }
    
    $stmt = $conn->prepare("DELETE FROM purchases WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Purchase deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Purchase not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete purchase: ' . $stmt->error]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
