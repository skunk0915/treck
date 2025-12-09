<?php
require_once __DIR__ . '/lib/DB.php';

echo "<h1>Database Cleanup</h1>";

try {
    $pdo = DB::getInstance()->getConnection();
    
    // Check if table exists before
    $stmt = $pdo->query("SHOW TABLES LIKE 'access_logs'");
    if ($stmt->rowCount() > 0) {
        $pdo->exec("DROP TABLE access_logs");
        echo "<p style='color: green;'>Success: Table 'access_logs' has been dropped.</p>";
    } else {
        echo "<p>Table 'access_logs' does not exist (already clean).</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><a href='index.php'>Return to Home</a></p>";
