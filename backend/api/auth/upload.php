<?php
session_start();
header('Content-Type: application/json');

require_once '../../database/db_connect.php';

$response = [
    "success" => false,
    "message" => "Invalid request."
];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response["message"] = "User not logged in.";
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && isset($_POST['match_id'])) {
        $file = $_FILES['file'];
        $userId = $_SESSION['user_id']; // Get user ID from session
        $matchId = intval($_POST['match_id']);

        try {
            $db = getDB();
            
            // Verify the user is part of this match
            $stmt = $db->prepare('SELECT player1_id, player2_id, status FROM matches WHERE id = :match_id');
            $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
            $match = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if (!$match) {
                $response["message"] = "Match not found.";
                echo json_encode($response);
                exit;
            }
            
            if ($match['player1_id'] != $userId && $match['player2_id'] != $userId) {
                $response["message"] = "You are not a participant in this match.";
                echo json_encode($response);
                exit;
            }
            
            // Check if user has already uploaded for this match
            $stmt = $db->prepare('SELECT id FROM uploads WHERE user_id = :user_id AND match_id = :match_id');
            $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
            $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
            $existingUpload = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            
            if ($existingUpload) {
                $response["message"] = "You have already uploaded a track for this match.";
                echo json_encode($response);
                exit;
            }

            if ($file['error'] === UPLOAD_ERR_OK) {
                // Create backend/uploads directory if it doesn't exist
                $uploadDir = dirname(__DIR__, 1) . '/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate sample ID and final filename
                $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
                $sampleId = uniqid('sample_', true);
                $safeName = "{$userId}_{$sampleId}." . strtolower($fileExt);
                $destination = $uploadDir . $safeName;

                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Record the upload in the database
                    $filePath = "backend/uploads/" . $safeName;
                    $stmt = $db->prepare('INSERT INTO uploads (user_id, match_id, file_path) VALUES (:user_id, :match_id, :file_path)');
                    $stmt->bindValue(':user_id', $userId, SQLITE3_INTEGER);
                    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
                    $stmt->bindValue(':file_path', $filePath, SQLITE3_TEXT);
                    $stmt->execute();
                    
                    // Check if both players have uploaded (match completion check)
                    $stmt = $db->prepare('SELECT COUNT(*) as upload_count FROM uploads WHERE match_id = :match_id');
                    $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
                    $uploadCount = $stmt->execute()->fetchArray(SQLITE3_ASSOC)['upload_count'];
                    
                    // Update match status if both players have uploaded
                    if ($uploadCount >= 2) {
                        $stmt = $db->prepare('UPDATE matches SET status = :status WHERE id = :match_id');
                        $stmt->bindValue(':status', 'ready_for_voting', SQLITE3_TEXT);
                        $stmt->bindValue(':match_id', $matchId, SQLITE3_INTEGER);
                        $stmt->execute();
                    }
                    
                    $playerNumber = ($match['player1_id'] == $userId) ? 1 : 2;
                    $response["success"] = true;
                    $response["message"] = "File uploaded successfully as Player {$playerNumber}!";
                    $response["file"] = $filePath;
                    $response["uploads_complete"] = ($uploadCount >= 2);
                } else {
                    $response["message"] = "Error moving uploaded file.";
                }
            } else {
                $response["message"] = "File upload error: " . $file['error'];
            }
        } catch (Exception $e) {
            $response["message"] = "Database error: " . $e->getMessage();
        }
    } else {
        $response["message"] = "No file or match ID provided.";
    }
}

echo json_encode($response);
