<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'user not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['match_id']) || !isset($data['voted_for'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'match id and voted_for are required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$match_id = $data['match_id'];
$voted_for = $data['voted_for'];

if ($voted_for !== 1 && $voted_for !== 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'voted_for must be 1 or 2']);
    exit;
}

try {
    $db = getDB();
    //check if user already voted
    $stmt = $db->prepare('SELECT id FROM votes WHERE voter_id = :voter_id AND match_id = :match_id');
    $stmt->bindValue(':voter_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
    if ($stmt->execute()->fetchArray()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'you have already voted for this match']);
        exit;
    }
    
    //check if match is ready for voting
    $stmt = $db->prepare('SELECT id FROM matches WHERE id = :match_id AND status = "ready_for_voting"');
    $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
    if (!$stmt->execute()->fetchArray()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'match not found or not ready for voting']);
        exit;
    }
    
    //transaction
    $db->exec('BEGIN');
    
    //insert vote
    $stmt = $db->prepare('INSERT INTO votes (voter_id, match_id, voted_for) VALUES (:voter_id, :match_id, :voted_for)');
    $stmt->bindValue(':voter_id', $user_id, SQLITE3_INTEGER);
    $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
    $stmt->bindValue(':voted_for', $voted_for, SQLITE3_INTEGER);
    $stmt->execute();
    
    //update vote count
    $columnToUpdate = ($voted_for === 1) ? 'player1_votes' : 'player2_votes';
    $stmt = $db->prepare("UPDATE matches SET $columnToUpdate = $columnToUpdate + 1 WHERE id = :match_id");
    $stmt->bindValue(':match_id', $match_id, SQLITE3_INTEGER);
    $stmt->execute();
    
    $db->exec('COMMIT');
    
    echo json_encode(['success' => true, 'message' => 'vote submitted successfully']);
    
} catch (Exception $e) {
    if ($db) {
        $db->exec('ROLLBACK');
    }
    error_log("exception in submit_vote.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'a server error occurred while submitting vote']);
}
?> 