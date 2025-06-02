<?php

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null &&
        in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE]) &&
        !headers_sent()) {
        
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        error_log("Shutdown error in signup.php: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        echo json_encode([
            'success' => false, 
            'error' => 'A critical server error occurred during signup.'
        ]);
    }
});

require_once '../../database/db_connect.php';
session_start();


if (!headers_sent()) {
    header('Content-Type: application/json');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username and password required']);
    exit;
}

try {
    $db = getDB();
    
    $checkStmt = $db->prepare('SELECT id FROM users WHERE username = :username');
    if ($checkStmt === false) {
        throw new Exception("Failed to prepare username check statement: " . $db->lastErrorMsg());
    }
    $checkStmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $result = $checkStmt->execute();
    if ($result === false) {
        throw new Exception("Failed to execute username check statement: " . $db->lastErrorMsg());
    }
    
    if ($result->fetchArray(SQLITE3_ASSOC)) { 
        http_response_code(409); 
        echo json_encode(['success' => false, 'error' => 'Username already exists']);
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
        throw new Exception("Failed to prepare insert user statement: " . $db->lastErrorMsg());
    }
    
    $stmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', $passwordHash, SQLITE3_TEXT);
    $stmt->bindValue(':producername', $producerName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $data['email'], SQLITE3_TEXT);
    
    if ($stmt->execute() === false) {
        throw new Exception("Failed to execute insert user statement: " . $db->lastErrorMsg());
    }
    
    $newUserId = $db->lastInsertRowID();
    // Set session data
    $_SESSION['user_id'] = $newUserId;
    $_SESSION['username'] = $data['username'];
    $_SESSION['producer_name'] = $producerName;
    
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $newUserId, //good to return the new ID
            'username' => $data['username'],
            'producer_name' => $producerName
            // 'elo' => 1500 // Default elo
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500); 
    error_log("Exception in signup.php: " . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Server error during signup. ' . $e->getMessage()]); 
} 