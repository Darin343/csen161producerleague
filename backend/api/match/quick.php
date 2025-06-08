<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user_id'])) {

    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'user not logged in']);
    exit;
}



$user_id = $_SESSION['user_id'];
$db = getDB();


try {


    //check if user is already in an active match where they havent uploaded yet
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
    $existing_match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);


    if ($existing_match) {
        echo json_encode(['success' => true, 'match_id' => $existing_match['id'], 'message' => 'User is already in a match.']);
        exit;
    }


    //matchmaking logic:




    //must be: pending status, one player, not the current user
    $stmt = $db->prepare("SELECT id FROM matches WHERE player2_id IS NULL AND status = 'pending' AND player1_id != :user_id ORDER BY created_at ASC LIMIT 1");
    $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
    $open_match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($open_match) {


        //join the match
        $match_id = $open_match['id'];
        $stmt = $db->prepare('UPDATE matches SET player2_id = :user_id, status = :status WHERE id = :match_id');
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':status', 'active', SQLITE3_TEXT);
        $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
        $stmt->execute();

        echo json_encode(['success' => true, 'match_id' => $match_id]);
    } else {


        //no open matches, create a new one
        $stmt = $db->prepare('INSERT INTO matches (player1_id, status) VALUES (:user_id, :status)');
        $stmt->bindValue(':user_id', $user_id, SQLITE3_INTEGER);
        $stmt->bindValue(':status', 'pending', SQLITE3_TEXT);
        $stmt->execute();
        
        $match_id = $db->lastInsertRowID();
        echo json_encode(['success' => true, 'match_id' => $match_id]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'database error: ' . $e->getMessage()]);
} 