<?php

require_once '../../database/db_connect.php';
session_start();


if (!headers_sent()) {
    header('Content-Type: application/json');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'username and password required']);
    exit;
}

try {
    $db = getDB();
    
    $checkStmt = $db->prepare('SELECT id FROM users WHERE username = :username');
    if ($checkStmt === false) {
        throw new Exception("failed to prepare username check statement: " . $db->lastErrorMsg());
    }
    $checkStmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $result = $checkStmt->execute();
    if ($result === false) {
        throw new Exception("failed to execute username check statement: " . $db->lastErrorMsg());
    }
    
    if ($result->fetchArray(SQLITE3_ASSOC)) { 
        http_response_code(409); 
        echo json_encode(['success' => false, 'error' => 'username already exists']);
        exit;
    }
    
    //hash password and create user
    $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $producerName = isset($data['producerName']) && trim($data['producerName']) !== ''
        ? 'Prod. ' . trim($data['producerName'])
        : $data['username'];
    
    $stmt = $db->prepare('
        INSERT INTO users (username, password_hash, producername, email) 
        VALUES (:username, :password_hash, :producername, :email)
    ');
    if ($stmt === false) {
        throw new Exception("failed to prepare insert user statement: " . $db->lastErrorMsg());
    }


    //bind values to the statement
    $stmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', $passwordHash, SQLITE3_TEXT);
    $stmt->bindValue(':producername', $producerName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $data['email'], SQLITE3_TEXT);
    


    if ($stmt->execute() === false) {
        throw new Exception("failed to execute insert user statement: " . $db->lastErrorMsg());
    }
    
    $newUserId = $db->lastInsertRowID();
    //set session data
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['username'] = $data['username'];
    $_SESSION['producer_name'] = $producerName;
    


    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $newUserId,
            'username' => $data['username'],
            'producer_name' => $producerName,
            'elo' => 1500 
        ]
    ]);



} catch (Exception $e) {
    http_response_code(500); 
    error_log("Exception in signup.php: " . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'server error during signup. ' . strtolower($e->getMessage())]); 
} 