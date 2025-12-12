<?php
// check_db_filenames.php
require_once __DIR__ . '/lib/DB.php';

echo "<h2>DB Filename Format Check</h2>";

try {
    $pdo = \DB::getInstance()->getConnection();
    
    // Check specific montbell fleece entry
    echo "<h3>Looking for 'montbell_fleece' like entries</h3>";
    $stmt = $pdo->query("SELECT * FROM article_tags WHERE article_filename LIKE '%montbell_fleece%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($rows) {
        echo "<table border='1'><tr><th>ID</th><th>Filename</th><th>TagID</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>" . htmlspecialchars($r['article_filename']) . "</td>";
            echo "<td>{$r['tag_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No matches found.";
    }
    
    // Check general format (sample 5)
    echo "<h3>Random 5 entries</h3>";
    $stmt = $pdo->query("SELECT article_filename FROM article_tags LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_Column);
    foreach ($rows as $r) {
        echo "Filename: " . htmlspecialchars($r) . "<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
