<?php
require_once __DIR__ . '/config/config.php';

// Check if xendit_invoice_id column exists
try {
    $query = "DESCRIBE orders";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasXenditColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'xendit_invoice_id') {
            $hasXenditColumn = true;
            break;
        }
    }
    
    if ($hasXenditColumn) {
        echo "✓ xendit_invoice_id column exists in orders table\n";
    } else {
        echo "✗ xendit_invoice_id column does NOT exist in orders table\n";
        echo "Please run the SQL migration: sql/add_xendit_column.sql\n";
    }
} catch (Exception $e) {
    echo "Error checking database: " . $e->getMessage() . "\n";
}
?>
