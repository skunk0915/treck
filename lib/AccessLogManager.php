<?php

require_once __DIR__ . '/DB.php';

class AccessLogManager {
    private $pdo;

    public function __construct() {
        $this->pdo = DB::getInstance()->getConnection();
        $this->ensureTable();
    }

    private function ensureTable() {
        // Optimized table: 1 record per user
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS user_access_counts (
            user_uuid VARCHAR(64) PRIMARY KEY,
            access_count INT DEFAULT 1,
            last_access_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Clean up old table if it exists (Optional, but good for cleanliness based on user feedback)
        // $this->pdo->exec("DROP TABLE IF EXISTS access_logs"); 
    }

    public function logAccess($uuid) {
        // UPSERT: Insert or Increment
        $stmt = $this->pdo->prepare("
            INSERT INTO user_access_counts (user_uuid, access_count, last_access_time)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                access_count = access_count + 1,
                last_access_time = NOW()
        ");
        $stmt->execute([$uuid]);
    }

    public function getAccessCount($uuid) {
        $stmt = $this->pdo->prepare("SELECT access_count FROM user_access_counts WHERE user_uuid = ?");
        $stmt->execute([$uuid]);
        $count = $stmt->fetchColumn();
        return $count ? (int)$count : 0;
    }
}
