<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'user not logged in']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'match id not given']);
    exit;
}

$match_id = $_GET['id'];
$db = getDB();

try {

    $stmt = $db->prepare('
        SELECT
            m.id AS match_id,
            m.status,
            m.player1_id,
            p1.producername AS player1_producername,
            p1.elo AS player1_elo,
            m.player2_id,
            p2.producername AS player2_producername,
            p2.elo AS player2_elo,
            m.created_at
        FROM matches m
        JOIN users p1 ON m.player1_id = p1.id
        LEFT JOIN users p2 ON m.player2_id = p2.id
        WHERE m.id = :match_id
    ');
    
    
    $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
    $match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    
    if ($match) {


        //if the user isnt in the match we cant let them see the details
        if ($match['player1_id'] != $_SESSION['user_id'] && $match['player2_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'you are not a player in this match.']);
            exit;
        }

        echo json_encode(['success' => true, 'match' => $match]);
    
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'match not found']);
    }



} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'database error: ' . $e->getMessage()]);
} 