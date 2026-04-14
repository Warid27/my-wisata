<?php
// Additional helper functions

// Get event details with venue
function get_event_with_venue($id_event) {
    global $db;
    $query = "SELECT e.*, v.nama_venue, v.alamat, v.kapasitas 
              FROM event e 
              JOIN venue v ON e.id_venue = v.id_venue 
              WHERE e.id_event = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_event]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get tickets for event
function get_tickets_by_event($id_event) {
    global $db;
    $query = "SELECT * FROM tiket WHERE id_event = ? ORDER BY nama_tiket";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_event]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check ticket availability
function check_ticket_availability($id_tiket, $qty) {
    global $db;
    
    try {
        // Lock the ticket row for update
        $query = "SELECT kuota FROM tiket WHERE id_tiket = ? FOR UPDATE";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_tiket]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            return false;
        }
        
        // Calculate sold tickets
        $query = "SELECT COALESCE(SUM(od.qty), 0) as sold 
                  FROM order_detail od 
                  JOIN orders o ON od.id_order = o.id_order 
                  WHERE od.id_tiket = ? AND o.status != 'cancelled'";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_tiket]);
        $sold = $stmt->fetch(PDO::FETCH_ASSOC)['sold'];
        
        $available = $ticket['kuota'] - $sold;
        
        return $available >= $qty;
    } catch (Exception $e) {
        return false;
    }
}

// Validate voucher
function validate_voucher($kode_voucher) {
    global $db;
    $query = "SELECT * FROM voucher 
              WHERE kode_voucher = ? AND status = 'aktif' AND kuota > 0";
    $stmt = $db->prepare($query);
    $stmt->execute([$kode_voucher]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Use voucher (decrement quota)
function use_voucher($id_voucher) {
    global $db;
    $query = "UPDATE voucher SET kuota = kuota - 1 WHERE id_voucher = ? AND kuota > 0";
    $stmt = $db->prepare($query);
    return $stmt->execute([$id_voucher]);
}

// Create order with details
function create_order($id_user, $cart_items, $id_voucher = null) {
    global $db;
    
    // Check if transaction is already active
    $inTransaction = $db->inTransaction();
    if (!$inTransaction) {
        $db->beginTransaction();
    }
    
    try {
        // Calculate total
        $total = 0;
        foreach ($cart_items as $item) {
            $total += $item['subtotal'];
        }
        
        // Apply voucher discount
        if ($id_voucher) {
            $voucher = validate_voucher_by_id($id_voucher);
            if ($voucher && $voucher['kuota'] > 0) {
                $total -= $voucher['potongan'];
                if ($total < 0) $total = 0;
                // Update voucher quota within the same transaction
                $query = "UPDATE voucher SET kuota = kuota - 1 WHERE id_voucher = ? AND kuota > 0";
                $stmt = $db->prepare($query);
                $stmt->execute([$id_voucher]);
            }
        }
        
        // Create order
        $query = "INSERT INTO orders (id_user, total, status, id_voucher) VALUES (?, ?, 'pending', ?)";
        $stmt = $db->prepare($query);
        $stmt->execute([$id_user, $total, $id_voucher]);
        $id_order = $db->lastInsertId();
        
        // Create order details
        foreach ($cart_items as $item) {
            $query = "INSERT INTO order_detail (id_order, id_tiket, qty, subtotal) VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$id_order, $item['id_tiket'], $item['qty'], $item['subtotal']]);
            $id_detail = $db->lastInsertId();
            
            // Generate tickets
            for ($i = 0; $i < $item['qty']; $i++) {
                $kode_tiket = generate_ticket_code();
                $query = "INSERT INTO attendee (id_detail, kode_tiket) VALUES (?, ?)";
                $stmt = $db->prepare($query);
                $stmt->execute([$id_detail, $kode_tiket]);
            }
        }
        
        // Only commit if we started the transaction
        if (!$inTransaction) {
            $db->commit();
        }
        return $id_order;
    } catch (Exception $e) {
        // Only rollback if we started the transaction
        if (!$inTransaction) {
            $db->rollBack();
        }
        throw $e;
    }
}

// Validate voucher by ID
function validate_voucher_by_id($id_voucher) {
    global $db;
    $query = "SELECT * FROM voucher WHERE id_voucher = ? AND status = 'aktif'";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_voucher]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get user's orders
function get_user_orders($id_user, $limit = 10, $offset = 0) {
    global $db;
    // Ensure limit and offset are integers
    $limit = (int)$limit;
    $offset = (int)$offset;
    
    $query = "SELECT o.*, COUNT(a.id_attendee) as total_tiket 
              FROM orders o 
              LEFT JOIN order_detail od ON o.id_order = od.id_order 
              LEFT JOIN attendee a ON od.id_detail = a.id_detail 
              WHERE o.id_user = ? 
              GROUP BY o.id_order 
              ORDER BY o.tanggal_order DESC 
              LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_user]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get order details
function get_order_details($id_order) {
    global $db;
    $query = "SELECT od.*, t.nama_tiket, e.nama_event 
              FROM order_detail od 
              JOIN tiket t ON od.id_tiket = t.id_tiket 
              JOIN event e ON t.id_event = e.id_event 
              WHERE od.id_order = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_order]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get tickets for order
function get_order_tickets($id_order) {
    global $db;
    $query = "SELECT a.*, od.id_tiket, t.nama_tiket, e.nama_event 
              FROM attendee a 
              JOIN order_detail od ON a.id_detail = od.id_detail 
              JOIN tiket t ON od.id_tiket = t.id_tiket 
              JOIN event e ON t.id_event = e.id_event 
              WHERE od.id_order = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id_order]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check in ticket
function checkin_ticket($kode_tiket) {
    global $db;
    $db->beginTransaction();
    
    try {
        $query = "SELECT a.*, e.nama_event, e.tanggal 
                  FROM attendee a 
                  JOIN order_detail od ON a.id_detail = od.id_detail 
                  JOIN tiket t ON od.id_tiket = t.id_tiket 
                  JOIN event e ON t.id_event = e.id_event 
                  WHERE a.kode_tiket = ? AND a.status_checkin = 'belum'";
        $stmt = $db->prepare($query);
        $stmt->execute([$kode_tiket]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket) {
            $query = "UPDATE attendee SET status_checkin = 'sudah', waktu_checkin = NOW() WHERE kode_tiket = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$kode_tiket]);
            $db->commit();
            return $ticket;
        } else {
            $db->rollBack();
            return false;
        }
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}

// Get dashboard statistics
function get_dashboard_stats() {
    global $db;
    
    $stats = [];
    
    // Total users
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total orders
    $query = "SELECT COUNT(*) as total FROM orders WHERE status = 'paid'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total revenue
    $query = "SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE status = 'paid'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total events
    $query = "SELECT COUNT(*) as total FROM event";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return $stats;
}

// Get sales report per event
function get_sales_report($date_from = null, $date_to = null) {
    global $db;
    
    $query = "SELECT e.id_event, e.nama_event, e.tanggal, 
                     COUNT(DISTINCT o.id_order) as total_orders,
                     SUM(od.qty) as total_tiket,
                     SUM(od.subtotal) as total_penjualan
              FROM event e
              LEFT JOIN tiket t ON e.id_event = t.id_event
              LEFT JOIN order_detail od ON t.id_tiket = od.id_tiket
              LEFT JOIN orders o ON od.id_order = o.id_order AND o.status = 'paid'
              WHERE 1=1";
    
    $params = [];
    
    if ($date_from) {
        $query .= " AND e.tanggal >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND e.tanggal <= ?";
        $params[] = $date_to;
    }
    
    $query .= " GROUP BY e.id_event ORDER BY e.tanggal DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Pagination helper
function paginate($total_items, $items_per_page = 10, $current_page = 1) {
    $total_pages = ceil($total_items / $items_per_page);
    $offset = ($current_page - 1) * $items_per_page;
    
    return [
        'total_items' => $total_items,
        'items_per_page' => $items_per_page,
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}
?>
