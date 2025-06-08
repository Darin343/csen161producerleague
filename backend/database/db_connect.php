<?php
function getDB() {
    try {
        // using absolute path to ensure database is always found
        $db_path = __DIR__ . '/producerleague.db';
        
        if (!file_exists($db_path)) {
            throw new Exception("database file not found at: $db_path");
        }
        
        $db = new SQLite3($db_path);
        $db->enableExceptions(true);
        
        if (!$db) {
            throw new Exception("failed to create SQLite3 db");
        }
        
        return $db;
    } catch (Exception $e) {
        error_log("db_connect.php: " . $e->getMessage());
        //JSON error response
        throw new Exception("database connection failed");
    }
}
?> 