<?php
$file = __DIR__ . '/article/test_corruption.md';
$content = "# Title\nTags: Guide, **先生**: 普通のブラは「形を整える」ためのもので、「運動」には向いていない。\n\nContent";
file_put_contents($file, $content);

function getArticleTags($filepath) {
    if (!file_exists($filepath)) return [];
    $content = file_get_contents($filepath);
    if (preg_match('/^Tags:\s*(.*)/m', $content, $matches)) {
        return array_filter(array_map('trim', explode(',', $matches[1])));
    }
    return [];
}

$tags = getArticleTags($file);
print_r($tags);

unlink($file);
?>
