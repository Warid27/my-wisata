<?php
class XenditService {
    private $apiKey;
    private $apiUrl = 'https://api.xendit.co';
    
    public function __construct() {
        // Get secret API key from config or environment
        $this->apiKey = defined('XENDIT_SECRET_KEY') ? XENDIT_SECRET_KEY : 
                       (getenv('XENDIT_SECRET_KEY') ?: 'xnd_development_...');
        
        if (empty($this->apiKey) || $this->apiKey === 'xnd_development_...' || 
            strpos($this->apiKey, 'YOUR_SECRET_KEY_HERE') !== false) {
            throw new Exception('Xendit secret API key is not configured. Please update config/config.php with your Xendit secret key.');
        }
    }
    
    /**
     * Create invoice for payment
     */
    public function createInvoice($data) {
        $endpoint = $this->apiUrl . '/v2/invoices';
        
        $payload = [
            'external_id' => $data['external_id'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'invoice_duration' => 86400, // 24 hours
            'customer' => [
                'given_names' => $data['customer_name'],
                'email' => $data['customer_email']
            ],
            'success_redirect_url' => get_full_base_url() . 'user/payment_success.php',
            'failure_redirect_url' => get_full_base_url() . 'user/payment.php?order=' . $data['external_id'],
            'currency' => 'IDR',
            'items' => $data['items'] ?? []
        ];
        
        $response = $this->makeRequest('POST', $endpoint, $payload);
        
        if (isset($response['id'])) {
            return [
                'success' => true,
                'invoice_id' => $response['id'],
                'invoice_url' => $response['invoice_url']
            ];
        }
        
        return ['success' => false, 'error' => $response];
    }
    
    /**
     * Get invoice details
     */
    public function getInvoice($invoiceId) {
        $endpoint = $this->apiUrl . '/v2/invoices/' . $invoiceId;
        
        $response = $this->makeRequest('GET', $endpoint);
        
        return $response;
    }
    
    /**
     * Verify webhook callback
     */
    public function verifyWebhook($headers, $body) {
        // Get the x-callback-token header - try different cases
        $callbackToken = $headers['x-callback-token'] ?? 
                        $headers['X-Callback-Token'] ?? 
                        $headers['X-CALLBACK-TOKEN'] ?? 
                        '';
        
        // Verify with your Xendit webhook verification token
        $webhookToken = defined('XENDIT_WEBHOOK_TOKEN') ? XENDIT_WEBHOOK_TOKEN : 
                        (getenv('XENDIT_WEBHOOK_TOKEN') ?: 'your_webhook_token');
        
        // Skip verification if webhook token is not configured (for development)
        if ($webhookToken === 'your_webhook_token' || empty($webhookToken)) {
            error_log('WARNING: Xendit webhook token not configured. Skipping verification.');
            return true;
        }
        
        // Log for debugging
        error_log('Webhook Verification - Received: ' . substr($callbackToken, 0, 10) . '..., Expected: ' . substr($webhookToken, 0, 10) . '...');
        
        return hash_equals($callbackToken, $webhookToken);
    }
    
    /**
     * Make HTTP request to Xendit API
     */
    private function makeRequest($method, $url, $data = null) {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->apiKey . ':')
        ];
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception('Curl error: ' . $error);
        }
        
        $decoded = json_decode($response, true);
        
        if ($httpCode !== 200) {
            $errorMsg = $decoded['message'] ?? 'API request failed';
            throw new Exception('Xendit API Error: ' . $errorMsg . ' (HTTP ' . $httpCode . ')');
        }
        
        return $decoded;
    }
    
    /**
     * Map Xendit status to order status
     */
    public static function mapStatus($xenditStatus) {
        $mapping = [
            'PENDING' => 'pending',
            'PAID' => 'paid',
            'EXPIRED' => 'cancelled',
            'FAILED' => 'cancelled'
        ];
        
        return $mapping[$xenditStatus] ?? 'pending';
    }
}
