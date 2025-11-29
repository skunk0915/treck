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
$allTags = [];

foreach ($files as $file) {
    $tags = getArticleTags($file);
    foreach ($tags as $tag) {
        $allTags[$tag] = ($allTags[$tag] ?? 0) + 1;
    }
}

ksort($allTags);
print_r($allTags);
?>
