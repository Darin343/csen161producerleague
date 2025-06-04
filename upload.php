<?php
header('Content-Type: application/json');

$response = [
    "success" => false,
    "message" => "Invalid request."
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file']) && isset($_POST['user_id'])) {
        $file = $_FILES['file'];
        $userId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['user_id']); // sanitize user id

        if ($file['error'] === UPLOAD_ERR_OK) {
            // Create /uploads directory if not exist
            $uploadDir = __DIR__ . '/../uploads/';
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
                $response["success"] = true;
                $response["message"] = "File uploaded successfully as $safeName!";
                $response["file"] = "uploads/" . $safeName;
            } else {
                $response["message"] = "Error moving uploaded file.";
            }
        } else {
            $response["message"] = "File upload error: " . $file['error'];
        }
    } else {
        $response["message"] = "No file or user ID provided.";
    }
}

echo json_encode($response);
