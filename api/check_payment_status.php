<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$invoice_id = $_GET['invoice_id'] ?? '';

if (empty($invoice_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invoice ID required']);
    exit;
}

// Get order by Xendit invoice ID
$query = "SELECT status FROM orders WHERE xendit_invoice_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$invoice_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if ($order) {
    echo json_encode(['status' => $order['status']]);
} else {
    echo json_encode(['status' => 'not_found']);
}
?>
