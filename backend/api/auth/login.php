<?php
// THIS SHOULD BE AT THE VERY TOP
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null &&
        in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_PARSE]) &&
        !headers_sent()) {
        
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }
        error_log("Shutdown error in login.php: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
        echo json_encode([
            'success' => false,
            'error' => 'A critical server error occurred during login.'
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
    
    $stmt = $db->prepare('SELECT id, username, password_hash, producername, elo FROM users WHERE username = :username');
    if ($stmt === false) {
        throw new Exception("Failed to prepare select user statement: " . $db->lastErrorMsg());
    }
    $stmt->bindValue(':username', $data['username'], SQLITE3_TEXT);
    $result = $stmt->execute();
    if ($result === false) {
        throw new Exception("Failed to execute select user statement: " . $db->lastErrorMsg());
    }
    
    $user = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$user || !password_verify($data['password'], $user['password_hash'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
        exit;
    }
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['producer_name'] = $user['producername'];
    $_SESSION['elo'] = $user['elo']; // Ensure ELO is set in the session

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'producer_name' => $user['producername'],
            'elo' => $user['elo'] ?? 1500 // Ensure elo is present
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Exception in login.php: " . $e->getMessage() . "\nStack trace:\n" . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Server error during login. ' . $e->getMessage()]);
}
?> 