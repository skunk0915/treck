<?php
// check_tags_debug.php
require_once __DIR__ . '/lib/DB.php';

echo "<h2>Deep Debug: montbell_fleece_complete_guide.md</h2>";

// 1. Check DB
echo "<h3>1. Database Check</h3>";
try {
    $pdo = \DB::getInstance()->getConnection();
    $stmt = $pdo->prepare("SELECT * FROM article_tags WHERE article_filename LIKE ?");
    $stmt->execute(['%montbell_fleece_complete_guide%']);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($rows) {
        echo "<p style='color:green'>Found " . count($rows) . " rows in DB.</p>";
        foreach ($rows as $r) {
            echo "ID: {$r['id']}, Filename: [{$r['article_filename']}], TagID: {$r['tag_id']}<br>";
        }
    } else {
        echo "<p style='color:red'>NOT FOUND in DB.</p>";
    }
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage();
}

// 2. Check JSON
echo "<h3>2. JSON File Check</h3>";
$jsonFile = __DIR__ . '/data/tags.json';
if (file_exists($jsonFile)) {
    $json = file_get_contents($jsonFile);
    $data = json_decode($json, true);
    
    if ($data === null) {
        echo "<p style='color:red'>JSON Decode Error: " . json_last_error_msg() . "</p>";
    } else {
        echo "<p>JSON loaded. Total articles: " . count($data) . "</p>";
        
        $target = 'montbell_fleece_complete_guide.md';
        if (isset($data[$target])) {
            echo "<p style='color:green'>Found '$target' in JSON.</p>";
            echo "Tags: " . json_encode($data[$target]);
        } else {
             echo "<p style='color:red'>Key '$target' NOT FOUND in JSON.</p>";
             
             // Fuzzy search
             echo "<h4>Fuzzy Search in JSON keys (montbell_fleece):</h4>";
             $found = false;
             foreach ($data as $key => $val) {
                 if (strpos($key, 'montbell_fleece') !== false) {
                     echo "Match: [$key] => " . json_encode($val) . "<br>";
                     $found = true;
                 }
             }
             if (!$found) echo "No fuzzy matches.";
        }
    }
} else {
    echo "<p style='color:red'>JSON file does not exist.</p>";
}
