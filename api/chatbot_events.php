<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            handleEventList();
            break;
        case 'search':
            handleEventSearch();
            break;
        case 'detail':
            handleEventDetail();
            break;
        case 'venues':
            handleVenues();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function handleEventList() {
    global $db;
    
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($page - 1) * $limit;
    
    // Filter parameters
    $dateFilter = $_GET['date'] ?? null;
    $categoryFilter = $_GET['category'] ?? null;
    $venueFilter = $_GET['venue'] ?? null;
    
    $whereConditions = ['e.tanggal >= CURDATE()'];
    $params = [];
    
    if ($dateFilter) {
        $whereConditions[] = 'DATE(e.tanggal) = ?';
        $params[] = $dateFilter;
    }
    
    if ($categoryFilter) {
        $whereConditions[] = 'e.kategori = ?';
        $params[] = $categoryFilter;
    }
    
    if ($venueFilter) {
        $whereConditions[] = 'e.id_venue = ?';
        $params[] = $venueFilter;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Get events
    $query = "SELECT e.*, v.nama_venue, v.alamat,
                     MIN(t.harga) as min_harga, MAX(t.harga) as max_harga,
                     COUNT(t.id_tiket) as total_tiket_types
              FROM event e 
              JOIN venue v ON e.id_venue = v.id_venue 
              LEFT JOIN tiket t ON e.id_event = t.id_event 
              WHERE $whereClause
              GROUP BY e.id_event 
              ORDER BY e.tanggal ASC, e.nama_event ASC
              LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events for chatbot
    $formattedEvents = array_map(function($event) {
        return [
            'id' => (int)$event['id_event'],
            'name' => htmlspecialchars($event['nama_event']),
            'description' => htmlspecialchars(substr($event['deskripsi'], 0, 200) . '...'),
            'date' => date('d M Y', strtotime($event['tanggal'])),
            'time' => '19:00',
            'venue' => [
                'name' => htmlspecialchars($event['nama_venue']),
                'address' => htmlspecialchars($event['alamat'])
            ],
            'price' => [
                'min' => $event['min_harga'] ? format_currency($event['min_harga']) : null,
                'max' => $event['max_harga'] ? format_currency($event['max_harga']) : null,
                'range' => $event['min_harga'] && $event['max_harga'] && $event['min_harga'] != $event['max_harga']
                    ? format_currency($event['min_harga']) . ' - ' . format_currency($event['max_harga'])
                    : ($event['min_harga'] ? format_currency($event['min_harga']) : 'Gratis')
            ],
            'category' => htmlspecialchars($event['kategori'] ?? 'Umum'),
            'image' => $event['gambar'] ? assets_url('images/' . $event['gambar']) : null,
            'url' => base_url('user/event_detail.php?id=' . $event['id_event'])
        ];
    }, $events);
    
    // Get total count
    $countQuery = "SELECT COUNT(DISTINCT e.id_event) as total
                   FROM event e 
                   WHERE $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countParams = array_slice($params, 0, -2); // Remove limit and offset
    $countStmt->execute($countParams);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedEvents,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function handleEventSearch() {
    global $db;
    
    $query = $_GET['q'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    if (empty($query)) {
        throw new Exception('Query parameter is required');
    }
    
    $searchQuery = "%$query%";
    
    $sql = "SELECT e.*, v.nama_venue, v.alamat, v.kota,
                   MIN(t.harga) as min_harga, MAX(t.harga) as max_harga
            FROM event e 
            JOIN venue v ON e.id_venue = v.id_venue 
            LEFT JOIN tiket t ON e.id_event = t.id_event 
            WHERE (e.nama_event LIKE ? OR e.deskripsi LIKE ? OR v.nama_venue LIKE ?)
            AND e.tanggal >= CURDATE()
            GROUP BY e.id_event 
            ORDER BY e.tanggal ASC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$searchQuery, $searchQuery, $searchQuery, $limit]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedEvents = array_map(function($event) {
        return [
            'id' => (int)$event['id_event'],
            'name' => htmlspecialchars($event['nama_event']),
            'date' => date('d M Y', strtotime($event['tanggal'])),
            'venue' => htmlspecialchars($event['nama_venue']),
            'price_range' => $event['min_harga'] && $event['max_harga'] && $event['min_harga'] != $event['max_harga']
                ? format_currency($event['min_harga']) . ' - ' . format_currency($event['max_harga'])
                : ($event['min_harga'] ? format_currency($event['min_harga']) : 'Gratis'),
            'url' => base_url('user/event_detail.php?id=' . $event['id_event'])
        ];
    }, $events);
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'data' => $formattedEvents,
        'count' => count($formattedEvents)
    ]);
}

function handleEventDetail() {
    global $db;
    
    $eventId = $_GET['id'] ?? null;
    
    if (!$eventId) {
        throw new Exception('Event ID is required');
    }
    
    // Get event details
    $query = "SELECT e.*, v.nama_venue, v.alamat, v.kota, v.telepon, v.email
              FROM event e 
              JOIN venue v ON e.id_venue = v.id_venue 
              WHERE e.id_event = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        throw new Exception('Event not found');
    }
    
    // Get ticket types
    $ticketQuery = "SELECT * FROM tiket WHERE id_event = ? ORDER BY harga ASC";
    $ticketStmt = $db->prepare($ticketQuery);
    $ticketStmt->execute([$eventId]);
    $tickets = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedEvent = [
        'id' => (int)$event['id_event'],
        'name' => htmlspecialchars($event['nama_event']),
        'description' => htmlspecialchars($event['deskripsi']),
        'date' => date('d M Y', strtotime($event['tanggal'])),
        'time' => '19:00 - 22:00',
        'venue' => [
            'name' => htmlspecialchars($event['nama_venue']),
            'address' => htmlspecialchars($event['alamat'])
        ],
        'category' => htmlspecialchars($event['kategori']),
        'tickets' => array_map(function($ticket) {
            return [
                'type' => htmlspecialchars($ticket['nama_tiket']),
                'price' => format_currency($ticket['harga']),
                'available' => (int)$ticket['kuota'],
                'description' => ''
            ];
        }, $tickets),
        'image' => $event['gambar'] ? assets_url('images/' . $event['gambar']) : null,
        'url' => base_url('user/event_detail.php?id=' . $event['id_event'])
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $formattedEvent
    ]);
}

function handleVenues() {
    global $db;
    
    $query = "SELECT v.*, COUNT(e.id_event) as upcoming_events
              FROM venue v
              LEFT JOIN event e ON v.id_venue = e.id_venue AND e.tanggal >= CURDATE()
              GROUP BY v.id_venue
              ORDER BY v.nama_venue ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formattedVenues = array_map(function($venue) {
        return [
            'id' => (int)$venue['id_venue'],
            'name' => htmlspecialchars($venue['nama_venue']),
            'address' => htmlspecialchars($venue['alamat']),
            'capacity' => (int)$venue['kapasitas'],
            'upcoming_events' => (int)$venue['upcoming_events']
        ];
    }, $venues);
    
    echo json_encode([
        'success' => true,
        'data' => $formattedVenues,
        'count' => count($formattedVenues)
    ]);
}
?>
