<?php
// Parse CLI arguments if running from command line
if (php_sapi_name() === 'cli') {
    $options = getopt('', ['key:', 'file:']);
    $key = $options['key'] ?? null;
    $file = $options['file'] ?? null;
} else {
    $key = $_GET['key'] ?? null;
    $file = $_GET['file'] ?? null;
}

// Prevent direct access if no key provided
if (!$key) {
    die('Access denied. Migration key required.');
}

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Verify migration key
if (!defined('MIGRATE_KEY') || $key !== MIGRATE_KEY) {
    die('Invalid migration key.');
}

// Database connection using existing Database class
require_once __DIR__ . '/../config/database.php';
$database = new Database();
$pdo = $database->getConnection();

if (!$pdo) {
    die("Database connection failed.");
}

// Set error mode
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Create migrations table if not exists
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Check if running specific migration
if ($file) {
    $specificFile = __DIR__ . '/' . basename($file);
    if (file_exists($specificFile) && preg_match('/^\d{3}_.*\.sql$/', basename($specificFile))) {
        $migrationFiles = [$specificFile];
        if (php_sapi_name() !== 'cli') {
            echo "<h2>Running specific migration: " . basename($specificFile) . "</h2>";
        } else {
            echo "Running specific migration: " . basename($specificFile) . "\n";
        }
    } else {
        die("Invalid migration file specified.\n");
    }
} else {
    // Get all SQL files in order
    $sqlFiles = glob(__DIR__ . '/*.sql');
    sort($sqlFiles);

    // Filter only migration files (001-999 pattern)
    $migrationFiles = array_filter($sqlFiles, function($file) {
        return preg_match('/\/(\d{3})_[^\/]+\.sql$/', $file);
    });

    // Sort migration files numerically
    usort($migrationFiles, function($a, $b) {
        $aNum = (int)basename($a, '.sql');
        $bNum = (int)basename($b, '.sql');
        return $aNum - $bNum;
    });
}

echo "<h1>Database Migration</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// Get already executed migrations
$executed = $pdo->query("SELECT filename FROM migrations")->fetchAll(PDO::FETCH_COLUMN);
$executed = array_map('basename', $executed);

$migrationsRun = 0;
$errors = [];

foreach ($migrationFiles as $file) {
    $filename = basename($file);
    
    // Skip if already executed
    if (in_array($filename, $executed)) {
        echo "<p class='info'>✓ Skipped: $filename (already executed)</p>";
        continue;
    }
    
    if (php_sapi_name() !== 'cli') {
        echo "<h3>Running: $filename</h3>";
    } else {
        echo "Running: $filename\n";
    }
    
    $transactionStarted = false;
    
    try {
        // Read SQL file
        $sql = file_get_contents($file);
        
        // Split SQL statements (basic split by semicolon)
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        // Execute statements without transaction (DDL can't be rolled back in MySQL)
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Record migration
        $stmt = $pdo->prepare("INSERT INTO migrations (filename) VALUES (?)");
        $stmt->execute([$filename]);
        
        if (php_sapi_name() !== 'cli') {
            echo "<p class='success'>✓ Success: $filename</p>";
        } else {
            echo "✓ Success: $filename\n";
        }
        $migrationsRun++;
        
    } catch (Exception $e) {
        $errorMsg = "✗ Error in $filename: " . $e->getMessage();
        if (php_sapi_name() !== 'cli') {
            echo "<p class='error'>$errorMsg</p>";
        } else {
            echo "$errorMsg\n";
        }
        $errors[] = $errorMsg;
        break; // Stop on first error
    }
}

echo "<hr>";
echo "<h2>Migration Summary</h2>";
echo "<p>Total migrations found: " . count($migrationFiles) . "</p>";
echo "<p>Migrations executed: $migrationsRun</p>";
echo "<p>Errors: " . count($errors) . "</p>";

if (!empty($errors)) {
    echo "<h3 class='error'>Errors:</h3>";
    foreach ($errors as $error) {
        echo "<p class='error'>$error</p>";
    }
}

echo "<hr>";
echo "<p><a href='?key=" . htmlspecialchars($_GET['key']) . "'>Run Again</a></p>";
echo "<p><small>Accessed at: " . date('Y-m-d H:i:s') . "</small></p>";
?>
