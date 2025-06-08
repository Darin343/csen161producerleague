<?php

require_once '../../database/db_connect.php';
session_start();

if (!headers_sent()) {
    header('Content-Type: application/json');
}

try {
    if (isset($_SESSION['user_id'])) {
        $db = getDB(); 
        
        $stmt = $db->prepare('SELECT username, producername, elo FROM users WHERE id = :id');
        if (!$stmt) {
            throw new Exception("failed to prepare user statement: " . $db->lastErrorMsg());
        }
        
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("failed to execute user statement: " . $db->lastErrorMsg());
        }
        
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'username' => $user['username'],
                    'producer_name' => $user['producername'],
                    'elo' => $user['elo']
                ]
            ]);
        } else {
            //user not found in db, clear session
            $_SESSION = array();
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();  

            echo json_encode(['logged_in' => false, 'reason' => 'user session invalid, logged out.']);
        }
    } else {
        echo json_encode(['logged_in' => false]);
    }
} catch (Exception $e) {
    error_log("exception in status.php: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500); 
    }
    echo json_encode([
        'logged_in' => false,
        'error' => 'an error occurred while checking authentication status',
    ]);
}
?> 