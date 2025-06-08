<?php

error_reporting(E_ALL);
ini_set('display_errors', 1); 

if (!headers_sent()) {
    header('Content-Type: application/json');
}

session_start();


//check if user is logged in
try {
    $db_connect_path = __DIR__ . '/../../database/db_connect.php';
    if (!file_exists($db_connect_path)) {
        
        throw new Exception("database configuration file not found at: " . $db_connect_path);
    }
    if (!is_readable($db_connect_path)) {
        throw new Exception("database configuration file not readable at: " . $db_connect_path);
    }
    require_once $db_connect_path;
    
    $db = getDB(); 
    if (!$db) {
        throw new Exception("failed to get db from getDB().");
    }
    
    if (isset($_SESSION['user_id'])) {
        if (!is_object($db) || !method_exists($db, 'prepare')) {
            throw new Exception("invalid database connection object.");
        }

        $stmt = $db->prepare('SELECT username, producername, elo FROM users WHERE id = :id');
        if (!$stmt) {
            $errorMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : 'failed to prepare SQL statement.';
            throw new Exception("SQL prepare error: " . $errorMsg);
        }
        
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);



        $result = $stmt->execute();

        if (!$result) {
            $errorMsg = method_exists($db, 'lastErrorMsg') ? $db->lastErrorMsg() : 'failed to run SQL statement.';
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
            
            //clear session
            session_unset();    
            session_destroy();  

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, //delete cookie
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }


            echo json_encode(['logged_in' => false, 'reason' => 'User session invalid, logged out.']);
        }
    } else {
        echo json_encode(['logged_in' => false]);
    }


} catch (Exception $e) {
    // if headers are not sent, set the response code to 500
    if (!headers_sent()) {
        http_response_code(500); 
    }
    echo json_encode([
        'logged_in' => false,
        'error' => 'an error occurred while checking authentication status. please try again later.',
    ]);
}
?> 