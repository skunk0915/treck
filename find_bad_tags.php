<?php
$articleDir = __DIR__ . '/article';

function getArticleTags($filepath) {
    if (!file_exists($filepath)) return [];
    $content = file_get_contents($filepath);
    if (preg_match('/^Tags:\s*(.*)/m', $content, $matches)) {
        return array_filter(array_map('trim', explode(',', $matches[1])));
    }
    return [];
}

$files = glob($articleDir . '/*.md');
$suspicious = "普通のブラは「形を整える」ためのもので、「運動」には向いていない。";

foreach ($files as $file) {
    $tags = getArticleTags($file);
    foreach ($tags as $tag) {
        if (strpos($tag, '普通のブラ') !== false) {
            echo "Found suspicious tag in: " . basename($file) . "\n";
            echo "Tag content: " . $tag . "\n";
            echo "Full Tags line: " . trim(shell_exec("grep '^Tags:' " . escapeshellarg($file))) . "\n";
        }
    }
}
?>
