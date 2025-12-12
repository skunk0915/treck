<?php
// read_log.php
$logFile = __DIR__ . '/build_debug.log';
echo "<h2>Build Debug Log</h2>";
if (file_exists($logFile)) {
    // Try file_get_contents. If it's UTF-16LE, we might need conversion.
    $content = file_get_contents($logFile);
    
    // Check for BOM (EF BB BF for UTF8, FF FE for UTF-16LE)
    if (substr($content, 0, 2) === "\xFF\xFE") {
        echo "<p>Detected UTF-16LE</p>";
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');
    }
    
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "Log file not found.";
}
