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
    if (isset($data['event'])) {
        switch ($data['event']) {
            case 'invoice.paid':
                handleInvoicePaid($data);
                break;
            case 'invoice.expired':
                handleInvoiceExpired($data);
                break;
            case 'invoice.failed':
                handleInvoiceFailed($data);
                break;
            case 'payment_token.activation':
                // Payment token activation - just log it
                error_log('Payment token activated: ' . json_encode($data['data']));
                break;
            default:
                error_log('Unhandled webhook event: ' . $data['event']);
                break;
        }
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
    
    $externalId = $data['data']['external_id'] ?? '';
    $invoiceId = $data['data']['id'] ?? '';
    
    // Extract order ID from external_id
    if (strpos($externalId, 'ORDER-') === 0) {
        $id_order = (int)substr($externalId, 6);
        
        // Update order status
        $query = "UPDATE orders SET status = 'paid', updated_at = NOW() 
                  WHERE id_order = ? AND status = 'pending'";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_order]);
        
        // Log the payment
        error_log("Order #$id_order marked as paid via Xendit invoice $invoiceId");
    }
}

/**
 * Handle expired invoice
 */
function handleInvoiceExpired($data) {
    global $db;
    
    $externalId = $data['data']['external_id'] ?? '';
    
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
    
    $externalId = $data['data']['external_id'] ?? '';
    
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
