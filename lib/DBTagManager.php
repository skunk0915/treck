<?php

require_once __DIR__ . '/DB.php';

class DBTagManager {
    private $pdo;

    public function __construct() {
        $this->pdo = DB::getInstance()->getConnection();
        $this->ensureTables();
    }

    private function ensureTables() {
        // Ensure tables exist
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) UNIQUE NOT NULL
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS article_tags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            article_filename VARCHAR(255) NOT NULL,
            tag_id INT NOT NULL,
            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
            UNIQUE KEY unique_article_tag (article_filename, tag_id)
        )");
    }

    public function getTags($filename) {
        $stmt = $this->pdo->prepare("
            SELECT t.name 
            FROM tags t
            JOIN article_tags at ON t.id = at.tag_id
            WHERE at.article_filename = ?
        ");
        $stmt->execute([$filename]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function setTags($filename, $tags) {
        $tags = array_unique(array_filter(array_map('trim', $tags)));
        
        $this->pdo->beginTransaction();
        
        try {
            // Get current tag IDs for the article to potentially remove them
            // Or simpler: Remove all current associations and re-add
            // BUT we want to keep tag IDs stable if possible
            
            // 1. Ensure all tags exist in `tags` table and get their IDs
            $tagIds = [];
            $insertTagStmt = $this->pdo->prepare("INSERT IGNORE INTO tags (name) VALUES (?)");
            $getTagIdStmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");

            foreach ($tags as $tagName) {
                if (empty($tagName)) continue;
                $insertTagStmt->execute([$tagName]);
                
                // If inserted, ID is lastInsertId. If ignored, we need to select it.
                $getTagIdStmt->execute([$tagName]);
                $tagId = $getTagIdStmt->fetchColumn();
                $tagIds[] = $tagId;
            }

            // 2. Remove old associations for this article
            $deleteStmt = $this->pdo->prepare("DELETE FROM article_tags WHERE article_filename = ?");
            $deleteStmt->execute([$filename]);

            // 3. Add new associations
            $insertAssocStmt = $this->pdo->prepare("INSERT INTO article_tags (article_filename, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tid) {
                $insertAssocStmt->execute([$filename, $tid]);
            }

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getAllTags() {
        // Return [tag_name => count]
        $stmt = $this->pdo->query("
            SELECT t.name, COUNT(at.article_filename) as count
            FROM tags t
            LEFT JOIN article_tags at ON t.id = at.tag_id
            GROUP BY t.id, t.name
            HAVING count > 0
            ORDER BY t.name ASC
        ");
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return $results;
    }

    public function renameTag($oldTag, $newTag) {
        // 1. Check if new tag exists
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$newTag]);
        $newTagId = $stmt->fetchColumn();

        $stmt->execute([$oldTag]);
        $oldTagId = $stmt->fetchColumn();

        if (!$oldTagId) return 0; // Old tag doesn't exist

        $this->pdo->beginTransaction();
        try {
            if ($newTagId) {
                // Merge old to new
                // Move associations from old to new, treating duplicates with IGNORE
                $updateStmt = $this->pdo->prepare("UPDATE IGNORE article_tags SET tag_id = ? WHERE tag_id = ?");
                $updateStmt->execute([$newTagId, $oldTagId]);
                
                // Delete any remaining associations for old tag (duplicates that were ignored)
                $this->pdo->prepare("DELETE FROM article_tags WHERE tag_id = ?")->execute([$oldTagId]);
                
                // Delete old tag
                $this->pdo->prepare("DELETE FROM tags WHERE id = ?")->execute([$oldTagId]);
            } else {
                // Just rename
                $updateStmt = $this->pdo->prepare("UPDATE tags SET name = ? WHERE id = ?");
                $updateStmt->execute([$newTag, $oldTagId]);
            }
            
            // Count affected articles is hard to get exactly in the merge case without more queries, 
            // but we can return total occurrences. For simplicity, return 1 as success indicator or todo implement better counting.
            // Let's count articles with the new tag now.
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM article_tags WHERE tag_id = ?");
            $countStmt->execute([$newTagId ?: $oldTagId]);
            $count = $countStmt->fetchColumn();

            $this->pdo->commit();
            return $count; 
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function mergeTags($sourceTag, $targetTag, $isDelete = false) {
        $stmt = $this->pdo->prepare("SELECT id FROM tags WHERE name = ?");
        $stmt->execute([$sourceTag]);
        $sourceId = $stmt->fetchColumn();

        if (!$sourceId) return 0;

        $this->pdo->beginTransaction();
        try {
            if ($isDelete) {
                // Just delete associations and key
                $this->pdo->prepare("DELETE FROM tags WHERE id = ?")->execute([$sourceId]); 
                // Creating tables with ON DELETE CASCADE handles association cleanup
                $count = 0; // Or count deleted rows before deleting
            } else {
                // Find or create target
                $stmt->execute([$targetTag]);
                $targetId = $stmt->fetchColumn();
                
                if (!$targetId) {
                    $this->pdo->prepare("INSERT INTO tags (name) VALUES (?)")->execute([$targetTag]);
                    $targetId = $this->pdo->lastInsertId();
                }

                // Update associations
                $updateStmt = $this->pdo->prepare("UPDATE IGNORE article_tags SET tag_id = ? WHERE tag_id = ?");
                $updateStmt->execute([$targetId, $sourceId]);
                
                // Cleanup old
                $this->pdo->prepare("DELETE FROM tags WHERE id = ?")->execute([$sourceId]);
            }

            $this->pdo->commit();
            return 1; // Simplify return count
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    public function getArticlesByTag($tagName) {
        $stmt = $this->pdo->prepare("
            SELECT at.article_filename 
            FROM article_tags at
            JOIN tags t ON at.tag_id = t.id
            WHERE t.name = ?
        ");
        $stmt->execute([$tagName]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
