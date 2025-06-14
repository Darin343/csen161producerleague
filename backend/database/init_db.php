<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db_path = __DIR__ . '/producerleague.db';
    echo "Current directory: " . __DIR__ . "\n";
    echo "Attempting to create/connect database at: $db_path\n";
    
    //check directory permissions
    echo "Directory permissions: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
    echo "Directory writable: " . (is_writable(__DIR__) ? 'Yes' : 'No') . "\n";
    
    //check if SQLite3 is available
    if (!class_exists('SQLite3')) {
        throw new Exception('SQLite3 class not found. Please enable the SQLite3 extension.');
    }
    
    //create/connect to SQLite db
    $db = new SQLite3($db_path);
    
    //had to set proper permissions here
    chmod($db_path, 0644);
    
    //create users table
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        producername TEXT NOT NULL,
        email TEXT UNIQUE,
        elo INTEGER DEFAULT 1500,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    
    //create matches table
    $db->exec('CREATE TABLE IF NOT EXISTS matches (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        player1_id INTEGER NOT NULL,
        player2_id INTEGER,
        status TEXT DEFAULT "pending",
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        winner_id INTEGER,
        FOREIGN KEY (player1_id) REFERENCES users(id),
        FOREIGN KEY (player2_id) REFERENCES users(id),
        FOREIGN KEY (winner_id) REFERENCES users(id)
    )');
    
    //create uploads table
    $db->exec('CREATE TABLE IF NOT EXISTS uploads (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        match_id INTEGER NOT NULL,
        file_path TEXT NOT NULL,
        upload_time DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (match_id) REFERENCES matches(id)
    )');
    
    //create voting table to prevent duplicate votes
    $db->exec('CREATE TABLE IF NOT EXISTS votes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        voter_id INTEGER NOT NULL,
        match_id INTEGER NOT NULL,
        voted_for INTEGER NOT NULL,
        voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (voter_id) REFERENCES users(id),
        FOREIGN KEY (match_id) REFERENCES matches(id),
        UNIQUE(voter_id, match_id)
    )');
    
    // Add vote count columns to matches table if they don't exist
    $db->exec('ALTER TABLE matches ADD COLUMN player1_votes INTEGER DEFAULT 0');
    $db->exec('ALTER TABLE matches ADD COLUMN player2_votes INTEGER DEFAULT 0');
    

    
    echo "successful db init!\n";
    
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo "error: " . $e->getMessage() . "\n";
    error_log("database initialization failed: " . $e->getMessage());
}
?> 