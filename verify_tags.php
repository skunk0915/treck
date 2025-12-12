<?php
$json = file_get_contents('js/articles.json');
$data = json_decode($json, true);
$target = 'montbell_fleece_complete_guide';
$found = false;
foreach ($data as $article) {
    if ($article['filename'] === $target) {
        print_r($article['tags']);
        $found = true;
        break;
    }
}
if (!$found) echo "NOT FOUND\n";
