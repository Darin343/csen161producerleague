<?php
session_start();

require_once '../../database/db_connect.php';

//helper to send json error
function send_error($code, $message) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error(405, 'method not allowed');
}

if (!isset($_SESSION['user_id'])) {
    send_error(401, 'user not logged in');
}

if (!isset($_FILES['file']) || !isset($_POST['match_id'])) {
    send_error(400, 'no file or match id provided');
}

//file validation
$file = $_FILES['file'];
$allowed_extensions = ['mp3', 'wav', 'flac', 'ogg'];
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if ($file['error'] !== UPLOAD_ERR_OK) {
    send_error(400, 'file upload error: ' . $file['error']);
}
if (!in_array($file_extension, $allowed_extensions)) {
    send_error(400, 'invalid file type. allowed types: ' . implode(', ', $allowed_extensions));
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $matchId = intval($_POST['match_id']);

    //verify user is a participant
    $stmt = $db->prepare('SELECT player1_id, player2_id FROM matches WHERE id = :match_id');
    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
    $match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$match) {
        send_error(404, 'match not found');
    }
    if ($match['player1_id'] != $userId && $match['player2_id'] != $userId) {
        send_error(403, 'you are not a participant in this match');
    }

    //check for existing upload
    $stmt = $db->prepare('SELECT id FROM uploads WHERE user_id = :user_id AND match_id = :match_id');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
    if ($stmt->execute()->fetchArray()) {
        send_error(409, 'you have already uploaded a track for this match');
    }

    //paths and filenames
    $uploadDir = dirname(__DIR__, 2) . '/uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('failed to create upload directory');
        }
    }
    $safeName = $userId . '_' . uniqid() . '.' . $file_extension;
    $destination = $uploadDir . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('failed to move uploaded file');
    }

    //record upload in db
    $filePath = 'backend/uploads/' . $safeName;
    $stmt = $db->prepare('INSERT INTO uploads (user_id, match_id, file_path) VALUES (:user_id, :match_id, :file_path)');
    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
    $stmt->bindValue(':file_path', $filePath, SQLITE3_TEXT);
    $stmt->execute();

    //check if match is ready for voting
    $stmt = $db->prepare('SELECT COUNT(id) as upload_count FROM uploads WHERE match_id = :match_id');
    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
    $uploadCount = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['upload_count'];
    $uploads_complete = ($uploadCount >= 2);

    if ($uploads_complete) {
        $stmt = $db->prepare('UPDATE matches SET status = "ready_for_voting" WHERE id = :match_id');
        $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
        $stmt->execute();
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "message" => "file uploaded successfully",
        "file" => $filePath,
        "uploads_complete" => $uploads_complete
    ]);

} catch (Exception $e) {
    error_log("exception in upload.php: " . $e->getMessage());
    send_error(500, 'a server error occurred during file upload');
}
