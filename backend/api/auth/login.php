<?php

require_once '../../database/db_connect.php';
session_start();

if (!headers_sent()) {
    header('Content-Type: application/json');
}


// get data from request
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'username and password required']);
    exit;
}

try {
    $db = getDB();
    
    $stmt = $db->prepare('SELECT id, username, password_hash, producername, elo FROM users WHERE username = :username');
    if ($stmt === false) {
        throw new Exception("failed to prepare select user statement: " . $db->lastErrorMsg());
    }
    $stmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result === false) {
        throw new Exception("failed to execute select user statement: " . $db->lastErrorMsg());
    }
    
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        http_response_code(401); //unauthorized
        echo json_encode(['success' => false, 'error' => 'invalid username or password']);
        exit;
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['producer_name'] = $user['producername'];
    $_SESSION['elo'] = $user['elo']; //ensure elo is set in the session

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'producer_name' => $user['producername'],
            'elo' => $user['elo'] ?? 1500 //ensure elo is present
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("err in login.php: " . $e->getMessage());
}
?> 