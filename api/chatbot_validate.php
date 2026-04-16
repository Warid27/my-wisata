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
    // Check for mathematical expressions
    if (preg_match('/\d+\s*[\+\-\*\/]\s*\d+/', $message)) {
        return [
            'valid' => false,
            'reason' => 'mathematical_expression'
        ];
    }
    
    // Check for prompt injection patterns
    $blockedPatterns = [
        '/ignore\s+(all|previous)\s+instructions/i',
        '/system\s*:/i',
        '/act\s+as\s+/i',
        '/pretend\s+to\s+be/i',
        '/roleplay/i',
        '/simulate/i',
        '/you\s+are\s+now/i',
        '/forget\s+everything/i',
        '/new\s+role/i',
        '/change\s+personality/i',
        '/developer\s+mode/i',
        '/admin\s+mode/i',
        '/debug\s+mode/i',
        '/override/i',
        '/bypass/i',
        '/jailbreak/i',
        '/=[=]+/',
        '/\[\[.*?\]\]/',
        '/{{.*?}}/',
        '/```.*?```/s',
        '/<script.*?>.*?<\/script>/is'
    ];
    
    foreach ($blockedPatterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return [
                'valid' => false,
                'reason' => 'prompt_injection'
            ];
        }
    }
    
    // Check for non-MyWisata topics
    $blockedTopics = [
        '/calculate/i',
        '/solve\s+this/i',
        '/what\s+is\s+\d+/i',
        '/compute/i',
        '/program/i',
        '/code/i',
        '/hack/i',
        '/crack/i',
        '/exploit/i'
    ];
    
    foreach ($blockedTopics as $topic) {
        if (preg_match($topic, $message)) {
            return [
                'valid' => false,
                'reason' => 'off_topic'
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
    $sanitized = $message;
    
    // Remove potential prompt injection markers
    $sanitized = preg_replace('/\[\[.*?\]\]/', '', $sanitized);
    $sanitized = preg_replace('/{{.*?}}/', '', $sanitized);
    $sanitized = preg_replace('/```[\s\S]*?```/', '', $sanitized);
    $sanitized = preg_replace('/<script[\s\S]*?<\/script>/i', '', $sanitized);
    $sanitized = preg_replace('/\[SYSTEM\]/i', '', $sanitized);
    $sanitized = preg_replace('/\[INST\]/i', '', $sanitized);
    $sanitized = preg_replace('/\[USER\]/i', '', $sanitized);
    $sanitized = preg_replace('/\[ASSISTANT\]/i', '', $sanitized);
    $sanitized = preg_replace('/=[=]+/', '', $sanitized);
    
    return trim($sanitized);
}
?>
