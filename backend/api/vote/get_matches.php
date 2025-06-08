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
    // Get matches that are ready for voting (have 2 uploads) ordered by creation date
    $stmt = $db->prepare('
        SELECT 
            m.id as match_id,
            m.created_at,
            m.status,
            m.player1_votes,
            m.player2_votes,
            p1.producername as player1_name,
            p2.producername as player2_name,
            u1.file_path as player1_track,
            u2.file_path as player2_track,
            CASE WHEN v.voter_id IS NOT NULL THEN 1 ELSE 0 END as user_voted
        FROM matches m
        JOIN users p1 ON m.player1_id = p1.id
        JOIN users p2 ON m.player2_id = p2.id
        JOIN uploads u1 ON m.id = u1.match_id AND m.player1_id = u1.user_id
        JOIN uploads u2 ON m.id = u2.match_id AND m.player2_id = u2.user_id
        LEFT JOIN votes v ON m.id = v.match_id AND v.voter_id = :user_id
        WHERE m.status = "ready_for_voting"
        ORDER BY m.created_at ASC
    ');
    
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $matches = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $matches[] = $row;
    }
    
    echo json_encode(['success' => true, 'matches' => $matches]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 