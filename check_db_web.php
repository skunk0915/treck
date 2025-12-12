<?php
// check_db_web.php

echo "<h2>Check DB & Sync Status (Diagnostics Only)</h2>";

// 1. Check .env
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "<p>.env found at " . $envPath . "</p>";
}

// 2. JSON Stats
$jsonFile = __DIR__ . '/data/tags.json';
echo "<h3>JSON File Status</h3>";
if (file_exists($jsonFile)) {
    $content = file_get_contents($jsonFile);
    $jsonData = json_decode($content, true);
    $jsonMtime = date("Y-m-d H:i:s", filemtime($jsonFile));
    
    echo "<p>Path: " . realpath($jsonFile) . "</p>";
    echo "<p>Size: " . strlen($content) . "</p>";
    echo "<p>MD5: <strong>" . md5($content) . "</strong></p>";
    echo "<p>Last Modified: <strong>$jsonMtime</strong></p>";
    echo "<p>Count: " . count($jsonData) . "</p>";
    
    // Check specific target
    $target = 'montbell_fleece_complete_guide.md';
    if (isset($jsonData[$target])) {
        echo "<p style='color:green'>Target '$target' FOUND in JSON.</p>";
    } else {
        echo "<p style='color:red'>Target '$target' NOT FOUND in JSON.</p>";
    }
} else {
    echo "<p style='color:red'>JSON File NOT Found!</p>";
}
