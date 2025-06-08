<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();

try {
    // Check if user is already in an active match (not completed)
    $stmt = $db->prepare('SELECT id FROM matches WHERE (player1_id = :user_id OR player2_id = :user_id) AND completed_at IS NULL');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $existing_match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($existing_match) {
        echo json_encode(['success' => true, 'match_id' => $existing_match['id'], 'message' => 'User is already in a match.']);
        exit;
    }

    // Find an open match to join (pending status, one player, not the current user)
    $stmt = $db->prepare("SELECT id FROM matches WHERE player2_id IS NULL AND status = 'pending' AND player1_id != :user_id ORDER BY created_at ASC LIMIT 1");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $open_match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($open_match) {
        // Join the found match
        $match_id = $open_match['id'];
        $stmt = $db->prepare('UPDATE matches SET player2_id = :user_id, status = :status WHERE id = :match_id');
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':status', 'active', SQLITE3_TEXT);
        $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['success' => true, 'match_id' => $match_id]);
    } else {
        // No open matches, create a new one
        $stmt = $db->prepare('INSERT INTO matches (player1_id, status) VALUES (:user_id, :status)');
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
        $stmt->execute();
        
        $match_id = $db->lastInsertRowID();
        echo json_encode(['success' => true, 'match_id' => $match_id]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} 