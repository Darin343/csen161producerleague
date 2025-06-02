<?php
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('SAMPLES_DIR', __DIR__ . '/samples/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB max file size

// Allowed file types
define('ALLOWED_TYPES', [
    'audio/mpeg',
    'audio/mp3'
]);
?> 