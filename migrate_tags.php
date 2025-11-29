<?php
require_once __DIR__ . '/lib/TagManager.php';

$articleDir = __DIR__ . '/article';
$dataFile = __DIR__ . '/data/tags.json';

$tagManager = new TagManager($dataFile);
$files = glob($articleDir . '/*.md');
$count = 0;

echo "Starting migration...\n";

foreach ($files as $file) {
    $filename = basename($file);
    $content = file_get_contents($file);
    $tags = [];

    // Extract tags from file content
    if (preg_match('/^Tags:\s*(.*)/m', $content, $matches)) {
        $tags = array_filter(array_map('trim', explode(',', $matches[1])));
    } else {
        // Fallback logic if needed (e.g. from filename), but for migration let's stick to what's in the file
        // Or we can use the logic from index.php if we want to preserve that behavior
        $filenameBase = str_replace('.md', '', $filename);
        $parts = explode('_', $filenameBase);
        $fallbackTags = array_filter($parts, function($t) {
            return !in_array($t, ['guide', 'article', 'review', 'comparison']);
        });
        if (empty($tags) && !empty($fallbackTags)) {
             // Only use fallback if no explicit tags found? 
             // Actually, let's just use what's explicitly there to avoid polluting with filename parts if they weren't intended as tags.
             // But the user might rely on filename parsing. Let's check index.php logic.
             // index.php uses fallback if regex fails.
             $tags = array_values($fallbackTags);
        }
    }

    // Clean tags
    $tags = array_filter($tags, function($t) {
        return strlen($t) < 50 && strpos($t, '**') === false && strpos($t, '。') === false;
    });

    if (!empty($tags)) {
        $tagManager->setTags($filename, $tags);
        echo "Migrated tags for $filename: " . implode(', ', $tags) . "\n";
        $count++;
    } else {
        echo "No tags found for $filename\n";
    }
}

echo "Migration complete. $count files processed.\n";
