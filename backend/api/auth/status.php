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
        
        echo json_encode([
            'logged_in' => false,
            'error' => 'A critical server error occurred. Please check server logs.',
        ]);
    }
});

error_reporting(E_ALL);
ini_set('display_errors', 1); 

if (!headers_sent()) {
    header('Content-Type: application/json');
}

session_start();

error_log('[status.php] Endpoint hit. Session ID: ' . session_id());
error_log('[status.php] Session data: ' . print_r($_SESSION, true));

try {
    $db_connect_path = __DIR__ . '/../../database/db_connect.php';
    if (!file_exists($db_connect_path)) {
        throw new Exception("Database configuration file not found at: " . $db_connect_path);
    }
    if (!is_readable($db_connect_path)) {
        throw new Exception("Database configuration file not readable at: " . $db_connect_path);
    }
    require_once $db_connect_path;
    
    $db = getDB(); 
    if (!$db) {
        throw new Exception("Failed to get database connection from getDB().");
    }
    
    if (isset($_SESSION['user_id'])) {
        if (!is_object($db) || !method_exists($db, 'prepare')) {
            error_log('[status.php] Database connection object is invalid or does not support prepare method.');
            throw new Exception("Invalid database connection object.");
        }

        $stmt = $db->prepare('SELECT username, producername, elo FROM users WHERE id = :id');
        if (!$stmt) {
            $errorMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : 'Failed to prepare SQL statement.';
            throw new Exception("SQL prepare error: " . $errorMsg);
        }
        
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();

        if (!$result) {
            $errorMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : 'Failed to execute SQL statement.';
            throw new Exception("SQL execute error: " . $errorMsg);
        }
        
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user) {
            echo json_encode([
                'logged_in' => true,
                'user' => [
                    'username' => $user['username'],
                    'producer_name' => $user['producername'],
                    'elo' => $user['elo'] ?? 1500
                ]
            ]);
        } else {
            error_log('[status.php] User ID ' . $_SESSION['user_id'] . ' found in session but not in database. Clearing session.');
            session_unset();    
            session_destroy();  
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            echo json_encode(['logged_in' => false, 'reason' => 'User session invalid, logged out.']);
        }
    } else {
        error_log('[status.php] No user_id found in session.');
        echo json_encode(['logged_in' => false]);
    }
} catch (Exception $e) {
    error_log('[status.php] Exception caught: ' . $e->getMessage() . ' Stack trace: ' . $e->getTraceAsString());
    
    if (!headers_sent()) {
        http_response_code(500); 
    }
    echo json_encode([
        'logged_in' => false,
        'error' => 'An error occurred while checking authentication status. Please try again later.',
    ]);
}
?> 