<?php
session_start();
require_once __DIR__ . '/lib/DBTagManager.php';
require_once __DIR__ . '/lib/TagManager.php';

// Security: Only allow if admin is logged in (same check as admin.php)
$adminEmail = 'skunk0915@gmail.com';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die('Access Denied. Please log in to admin.php first.');
}

$jsonFile = __DIR__ . '/data/tags.json';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Tag Migration</title>
    <style>
        body { font-family: monospace; padding: 2rem; }
        .log { background: #f0f0f0; padding: 1rem; border-radius: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Tag Migration Tool</h1>
    <div class="log">
<?php
echo "Migration started...\n";

if (!file_exists($jsonFile)) {
    echo "Error: tags.json not found at $jsonFile\n";
} else {
    try {
        // TagManager helper to read JSON is fine, or just raw decode
        // We use the DBTagManager to insert
        $dbTagManager = new DBTagManager();
        
        // Read JSON directly
        $jsonData = json_decode(file_get_contents($jsonFile), true);
        if (!$jsonData) {
            echo "Error: Invalid or empty JSON.\n";
        } else {
            $count = 0;
            foreach ($jsonData as $filename => $tags) {
                if (!empty($tags)) {
                    $dbTagManager->setTags($filename, $tags);
                    $count++;
                    echo "Migrated tags for: $filename (" . implode(', ', $tags) . ")\n";
                    flush(); // Try to push output
                }
            }
            echo "\nMigration completed. Processed $count files.\n";
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
    </div>
    <p><a href="admin.php">Back to Admin</a></p>
</body>
</html>
