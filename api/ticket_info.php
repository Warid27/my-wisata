<?php
// Enable error logging but disable display
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set up error handler for API
function apiErrorHandler($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $errstr,
        'file' => basename($errfile),
        'line' => $errline
    ]);
    exit();
}
set_error_handler('apiErrorHandler');

// Set up exception handler
function apiExceptionHandler($exception) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit();
}
set_exception_handler('apiExceptionHandler');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// Check if functions exist before using them
if (!function_exists('sanitize')) {
    echo json_encode([
        'success' => false,
        'message' => 'Required function sanitize not found'
    ]);
    exit;
}

if (!function_exists('format_date')) {
    echo json_encode([
        'success' => false,
        'message' => 'Required function format_date not found'
    ]);
    exit;
}

if (!function_exists('format_currency')) {
    echo json_encode([
        'success' => false,
        'message' => 'Required function format_currency not found'
    ]);
    exit;
}

// Require admin login for security
require_admin();

$kode_tiket = strtoupper(sanitize($_GET['code'] ?? ''));

if (empty($kode_tiket)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Kode tiket harus diisi'
    ]);
    exit;
}

try {
    // Get ticket information
    $query = "SELECT a.*, e.nama_event, e.tanggal, u.nama as nama_user, 
                    u.email as email_user, t.nama_tiket, t.harga, od.qty, od.subtotal
              FROM attendee a 
              JOIN order_detail od ON a.id_detail = od.id_detail 
              JOIN orders o ON od.id_order = o.id_order 
              JOIN users u ON o.id_user = u.id_user 
              JOIN tiket t ON od.id_tiket = t.id_tiket 
              JOIN event e ON t.id_event = e.id_event 
              WHERE a.kode_tiket = ? AND o.status = 'paid'";

    $stmt = $db->prepare($query);
    $stmt->execute([$kode_tiket]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Kode tiket tidak valid atau tidak ditemukan'
        ]);
        exit;
    }

    // Check if already checked in
    $already_checked = $ticket['status_checkin'] === 'sudah';

    echo json_encode([
        'success' => true,
        'data' => [
            'kode_tiket' => $ticket['kode_tiket'],
            'nama_event' => $ticket['nama_event'],
            'tanggal' => format_date($ticket['tanggal']),
            'nama_pengunjung' => $ticket['nama_user'],
            'email_pengunjung' => $ticket['email_user'],
            'nama_tiket' => $ticket['nama_tiket'],
            'harga' => format_currency($ticket['harga']),
            'qty' => $ticket['qty'],
            'subtotal' => format_currency($ticket['subtotal']),
            'status_checkin' => $ticket['status_checkin'],
            'already_checked' => $already_checked,
            'waktu_checkin' => $already_checked ? date('H:i', strtotime($ticket['waktu_checkin'])) : null
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
}
