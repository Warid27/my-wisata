<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/XenditService.php';

// Get raw POST data
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Get headers - try multiple methods
$headers = [];
if (function_exists('getallheaders')) {
    $headers = getallheaders();
} else {
    // Fallback for nginx/fastcgi
    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) == 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        }
    }
}

// Try different ways to get the callback token
$callbackToken = $headers['x-callback-token'] ?? 
                $headers['X-Callback-Token'] ?? 
                $_SERVER['HTTP_X_CALLBACK_TOKEN'] ?? 
                '';

// Log for debugging
error_log('Xendit Callback: ' . $rawData);
error_log('All Headers: ' . print_r($headers, true));
error_log('SERVER HTTP Headers: ' . print_r(array_filter($_SERVER, function($k) {
    return strpos($k, 'HTTP_') === 0;
}, ARRAY_FILTER_USE_KEY), true));
error_log('Callback Token: ' . $callbackToken);

try {
    // Debug mode - allow testing without proper webhook token
    if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
        error_log('DEBUG MODE: Skipping webhook verification');
    } else {
        // Verify webhook
        $xendit = new XenditService();
        
        if (!$xendit->verifyWebhook($headers, $rawData)) {
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => 'Invalid webhook token',
                'debug_info' => [
                    'received_token' => $callbackToken ? substr($callbackToken, 0, 10) . '...' : 'none',
                    'method' => $_SERVER['REQUEST_METHOD'],
                    'has_data' => !empty($rawData)
                ]
            ]);
            exit;
        }
    }
    
    // Process different callback types
    // Xendit sends invoice data directly, not wrapped in event/data structure
    if (isset($data['status'])) {
        switch ($data['status']) {
            case 'PAID':
                handleInvoicePaid($data);
                break;
            case 'EXPIRED':
                handleInvoiceExpired($data);
                break;
            case 'FAILED':
                handleInvoiceFailed($data);
                break;
            default:
                error_log('Unhandled webhook status: ' . $data['status']);
                break;
        }
    } else {
        error_log('No status found in webhook data');
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);
    
} catch (Exception $e) {
    error_log('Xendit Callback Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle successful payment
 */
function handleInvoicePaid($data) {
    global $db;
    
    $externalId = $data['external_id'] ?? '';
    $invoiceId = $data['id'] ?? '';
    
    error_log("Processing invoice paid: external_id=$externalId, invoice_id=$invoiceId");
    
    // Extract order ID from external_id
    if (strpos($externalId, 'ORDER-') === 0) {
        $id_order = (int)substr($externalId, 6);
        
        error_log("Extracted order ID: $id_order");
        
        // Update order status
        $query = "UPDATE orders SET status = 'paid', updated_at = NOW() 
                  WHERE id_order = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $result = $stmt->execute([$id_order]);
        
        $rowCount = $stmt->rowCount();
        error_log("Update result: success=$result, rows_affected=$rowCount");
        
        // Log the payment
        error_log("Order #$id_order marked as paid via Xendit invoice $invoiceId");
        
        // Verify the update
        $query = "SELECT status FROM orders WHERE id_order = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_order]);
        $status = $stmt->fetchColumn();
        error_log("Order #$id_order current status: $status");
    } else {
        error_log("Invalid external_id format: $externalId");
    }
}

/**
 * Handle expired invoice
 */
function handleInvoiceExpired($data) {
    global $db;
    
    $externalId = $data['external_id'] ?? '';
    
    if (strpos($externalId, 'ORDER-') === 0) {
        $id_order = (int)substr($externalId, 6);
        
        // Update order status to cancelled
        $query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() 
                  WHERE id_order = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_order]);
        
        error_log("Order #$id_order marked as cancelled due to expired invoice");
    }
}

/**
 * Handle failed payment
 */
function handleInvoiceFailed($data) {
    global $db;
    
    $externalId = $data['external_id'] ?? '';
    
    if (strpos($externalId, 'ORDER-') === 0) {
        $id_order = (int)substr($externalId, 6);
        
        // Update order status to cancelled
        $query = "UPDATE orders SET status = 'cancelled', updated_at = NOW() 
                  WHERE id_order = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_order]);
        
        error_log("Order #$id_order marked as cancelled due to failed payment");
    }
}
?>
