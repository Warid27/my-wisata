<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/config.php';

// Get JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['message'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

$message = $data['message'];

// Validate message
$validation = validateChatMessage($message);

echo json_encode([
    'success' => true,
    'valid' => $validation['valid'],
    'reason' => $validation['reason'] ?? null,
    'sanitized' => $validation['sanitized'] ?? $message
]);

/**
 * Validate chat message for prompt injection and security threats
 */
function validateChatMessage($message) {
    // Check for obvious mathematical expressions with equals
    if (preg_match('/\d+\s*[+\-*/]\s*\d+\s*=/', $message)) {
        return [
            'valid' => false,
            'reason' => 'mathematical_expression'
        ];
    }
    
    // Check for serious prompt injection patterns only
    $blockedPatterns = [
        '/ignore\s+(all|previous)\s+instructions/i',
        '/system\s*:/i',
        '/you\s+are\s+now\s+(a|an)\s+/i',
        '/forget\s+everything/i',
        '/jailbreak/i',
        '/<script[\s\S]*?<\/script>/is'
    ];
    
    foreach ($blockedPatterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return [
                'valid' => false,
                'reason' => 'prompt_injection'
            ];
        }
    }
    
    // Sanitize message
    $sanitized = sanitizeMessage($message);
    
    return [
        'valid' => true,
        'sanitized' => $sanitized
    ];
}

/**
 * Sanitize message by removing potential injection markers
 */
function sanitizeMessage($message) {
    // Only remove dangerous script tags and system markers
    $sanitized = $message;
    
    // Remove only dangerous elements
    $sanitized = preg_replace('/<script[\s\S]*?<\/script>/i', '', $sanitized);
    $sanitized = preg_replace('/\[SYSTEM\]/i', '', $sanitized);
    
    return trim($sanitized);
}
?>
