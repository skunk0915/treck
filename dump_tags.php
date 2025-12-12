<?php
// dump_tags.php
require_once __DIR__ . '/lib/DBTagManager.php';

$outputFile = __DIR__ . '/data/tags.json';

try {
    $tagManager = new DBTagManager();
    
    // We need a method to get ALL article tags. 
    // DBTagManager doesn't have a simple "get all article tags mapping" method yet.
    // Let's add a custom query here or extend DBTagManager. 
    // For now, ad-hoc query here is fine or we can use getAllTags but that's just counts.
    // We need filename -> [tags] mapping.
    
    $pdo = \DB::getInstance()->getConnection();
    $stmt = $pdo->query("
        SELECT at.article_filename, t.name 
        FROM article_tags at
        JOIN tags t ON at.tag_id = t.id
    ");
    
    $mapping = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $filename = $row['article_filename'];
        $tag = $row['name'];
        if (!isset($mapping[$filename])) {
            $mapping[$filename] = [];
        }
        $mapping[$filename][] = $tag;
    }
    
    // Ensure data dir exists
    if (!is_dir(dirname($outputFile))) {
        mkdir(dirname($outputFile), 0755, true);
    }
    
    file_put_contents($outputFile, json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Tags dumped to data/tags.json', 'count' => count($mapping)]);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
