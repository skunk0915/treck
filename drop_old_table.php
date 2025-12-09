<?php
require_once __DIR__ . '/lib/DB.php';

try {
    $pdo = DB::getInstance()->getConnection();
    
    // Check if table exists before
    $stmt = $pdo->query("SHOW TABLES LIKE 'access_logs'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'access_logs' exists. Dropping...\n";
        $pdo->exec("DROP TABLE access_logs");
        echo "Table 'access_logs' dropped successfully.\n";
    } else {
        echo "Table 'access_logs' does not exist.\n";
    }

    // Verify
    $stmt = $pdo->query("SHOW TABLES LIKE 'access_logs'");
    if ($stmt->rowCount() === 0) {
        echo "VERIFICATION: Table 'access_logs' is GONE.\n";
    } else {
        echo "VERIFICATION: Table 'access_logs' STILL EXISTS.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
