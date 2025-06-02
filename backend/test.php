<?php
require_once 'database/db_connect.php';
require_once 'config.php';

// Test database connection
try {
    $db = getDB();
    echo "Database connection successful!<br>";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test directory permissions
echo "Upload directory is " . (is_writable(UPLOAD_DIR) ? "writable" : "not writable") . "<br>";
echo "Samples directory is " . (is_writable(SAMPLES_DIR) ? "writable" : "not writable") . "<br>";

// Display PHP info for debugging
phpinfo();
?> 