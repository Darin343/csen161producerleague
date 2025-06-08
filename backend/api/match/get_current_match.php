<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db_connect.php';


if (!isset($_SESSION['user_id'])) {
    echo json_encode(['in_match' => false]);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = getDB();

try {
    //find matches where user hasn't uploaded yet AND match is not completed
    $stmt = $db->prepare('
        SELECT m.id 
        FROM matches m 
        WHERE (m.player1_id = :user_id OR m.player2_id = :user_id) 
        AND m.completed_at IS NULL 
        AND NOT EXISTS (
            SELECT 1 FROM uploads u 
            WHERE u.match_id = m.id AND u.user_id = :user_id
        )
        LIMIT 1
    ');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($match) {
        echo json_encode(['in_match' => true, 'match_id' => $match['id']]);
    } else {
        echo json_encode(['in_match' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['in_match' => false, 'error' => 'database error']);
}
?> 