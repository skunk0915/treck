<?php
$tests = [
    "Tags: A, B\nContent",
    "Tags: A, B\r\nContent",
    "Tags: A, BContent", // Missing newline (corruption source)
    "Tags: A, B\n\nContent"
];

$newTags = "Tags: New";

foreach ($tests as $content) {
    echo "Original: " . json_encode($content) . "\n";
    
    // New Regex: Match Tags line AND optional following newline
    // Note: . does not match newline. \R matches any newline sequence (PHP 5.10+ PCRE)
    // But let's use (\r?\n)?
    if (preg_match('/^Tags:\s*(.*)(\r?\n)?/m', $content, $matches)) {
        // Replace with NewTags + Newline
        $newContent = preg_replace('/^Tags:\s*(.*)(\r?\n)?/m', $newTags . "\n", $content, 1);
        echo "New:      " . json_encode($newContent) . "\n";
    } else {
        echo "No match\n";
    }
    echo "---\n";
}
?>
