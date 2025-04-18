<?php
// Environment-based Database Configuration
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'safecommunities';
$dbUser = getenv('DB_USER') ?: 'root'; // Change in production
$dbPass = getenv('DB_PASS') ?: '';     // Change in production

// Define environment
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Try to connect to database
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (APP_ENV === 'development') {
        die("Database connection failed: " . $e->getMessage());
    } else {
        http_response_code(500);
        die("Unable to connect to the database. Please try again later.");
    }
}

// Application Configuration
$config = [
    'app_name' => 'SafeCommunities',
    'upload_dir' => __DIR__ . '/uploads/',
    'max_file_size' => 5 * 1024 * 1024, // 5MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif'],
];

// Ensure upload directory exists and is writable
if (!is_dir($config['upload_dir'])) {
    // Attempt to create the directory
    if (!mkdir($config['upload_dir'], 0755, true)) {
        die("Error: Could not create upload directory.");
    }
}

if (!is_writable($config['upload_dir'])) {
    die("Error: Upload directory is not writable.");
}

// Helper Functions
function sanitizeInput($data) {
    if (is_null($data)) {
        return '';
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function sanitizeForJson($data) {
    if (is_array($data)) {
        return array_map('sanitizeForJson', $data);
    }
    return is_null($data) ? '' : sanitizeInput($data);
}

function generateUniqueFilename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . strtolower($extension);
}

function isAllowedFileType($file, $allowed_extensions) {
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($extension, $allowed_extensions);
}

function getTimeAgo($datetime) {
    if (empty($datetime)) {
        return 'Unknown time';
    }
    $time = strtotime($datetime);
    if ($time === false) {
        return 'Unknown time';
    }
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) {
        return "$diff seconds ago";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "$mins minute" . ($mins > 1 ? 's' : '') . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "$hours hour" . ($hours > 1 ? 's' : '') . " ago";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "$days day" . ($days > 1 ? 's' : '') . " ago";
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return "$months month" . ($months > 1 ? 's' : '') . " ago";
    } else {
        $years = floor($diff / 31536000);
        return "$years year" . ($years > 1 ? 's' : '') . " ago";
    }
}
?>
