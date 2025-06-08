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
    $stmt = $db->prepare('SELECT id FROM matches WHERE (player1_id = :user_id OR player2_id = :user_id) AND completed_at IS NULL LIMIT 1');
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($match) {
        echo json_encode(['in_match' => true, 'match_id' => $match['id']]);
    } else {
        echo json_encode(['in_match' => false]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['in_match' => false, 'error' => 'Database error']);
}
?> 