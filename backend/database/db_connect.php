<?php
function getDB() {
    try {
        // Using absolute path to ensure database is always found
        $db_path = __DIR__ . '/producerleague.db';
        $db = new SQLite3($db_path);
        
        // Set proper permissions
        chmod($db_path, 0644);
        
        return $db;
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?> 