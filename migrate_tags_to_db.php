<?php
require_once __DIR__ . '/lib/DBTagManager.php';
require_once __DIR__ . '/lib/TagManager.php';

// Check if run from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

$jsonFile = __DIR__ . '/data/tags.json';

echo "Migration started...\n";

if (!file_exists($jsonFile)) {
    die("tags.json not found at $jsonFile\n");
}

try {
    $jsonTagManager = new TagManager($jsonFile);
    $dbTagManager = new DBTagManager();

    // Access the raw data from JSON TagManager (we might need to add a getter or just use existing methods)
    // Actually, TagManager doesn't expose all raw data easily unless we iterate all files or modify it.
    // But wait, the TagManager loads everything into $tagsData. Let's rely on reading the JSON directly for migration to be safe/complete.
    
    $jsonData = json_decode(file_get_contents($jsonFile), true);
    if (!$jsonData) {
        die("Invalid or empty JSON.\n");
    }

    $count = 0;
    foreach ($jsonData as $filename => $tags) {
        if (!empty($tags)) {
            $dbTagManager->setTags($filename, $tags);
            $count++;
            echo "Migrated tags for: $filename\n";
        }
    }

    echo "Migration completed. processed $count files.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
