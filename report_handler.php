<?php
ini_set('display_errors', 0); // Disable display errors in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

// Ensure APP_ENV is defined
if (!defined('APP_ENV')) {
    define('APP_ENV', 'production'); // Default to production if not set
}

// Validate request method
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// OpenCage API key
$opencage_api_key = 'd8522d3d3f704523b33dd8999b7d6a61'; // Replace with your actual key

try {
    // Log raw POST and FILES for debugging in development
    if (APP_ENV === 'development') {
        error_log("POST data: " . json_encode($_POST));
        error_log("FILES data: " . json_encode($_FILES));
    }

    // Required fields validation
    $required_fields = ['incident_type', 'title', 'description', 'location', 'area', 'date', 'time', 'consent'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Required field missing or empty: " . ucfirst($field));
        }
    }

    // Date validation
    if (strtotime($_POST['date']) > time()) {
        throw new Exception("Incident date cannot be in the future");
    }

    // Validate incident type
    $valid_types = ['theft', 'vandalism', 'suspicious', 'assault', 'hazard', 'noise', 'other'];
    if (!in_array($_POST['incident_type'], $valid_types)) {
        throw new Exception("Invalid incident type");
    }

    // Validate email if provided
    $reporter_email = isset($_POST['reporter_email']) && trim($_POST['reporter_email']) ? $_POST['reporter_email'] : null;
    if ($reporter_email && !filter_var($reporter_email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Sanitize inputs
    $incident_type = sanitizeInput($_POST['incident_type']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $location = sanitizeInput($_POST['location']);
    $area = sanitizeInput($_POST['area']);
    $date = sanitizeInput($_POST['date']);
    $time = sanitizeInput($_POST['time']);
    $reporter_name = isset($_POST['reporter_name']) && trim($_POST['reporter_name']) ? sanitizeInput($_POST['reporter_name']) : null;
    $reporter_email = $reporter_email ? sanitizeInput($reporter_email) : null;

    // Handle file upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photo = $_FILES['photo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($photo['type'], $allowed_types)) {
            throw new Exception("Photo must be JPG, PNG, or GIF");
        }
        if ($photo['size'] > $max_size) {
            throw new Exception("Photo size exceeds 5MB limit");
        }

        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = uniqid() . '_' . basename($photo['name']);
        $photo_path = $upload_dir . $filename;

        if (!move_uploaded_file($photo['tmp_name'], $photo_path)) {
            throw new Exception("Failed to upload photo");
        }
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        ];
        $error_code = $_FILES['photo']['error'];
        $error_msg = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : 'Unknown upload error';
        throw new Exception("File upload error: " . $error_msg);
    }

    // Geocoding with OpenCage
    $latitude = null;
    $longitude = null;
    $geocoding_successful = false;

    // Try geocoding with location and area
    $geocode_query = urlencode($location . ", " . $area);
    $opencage_url = "https://api.opencagedata.com/geocode/v1/json?q=" . $geocode_query . "&key=" . $opencage_api_key . "&limit=1&countrycode=in";
    $opencage_response = @file_get_contents($opencage_url);

    if ($opencage_response !== false) {
        $opencage_data = json_decode($opencage_response, true);
        if ($opencage_data && isset($opencage_data['results']) && !empty($opencage_data['results'])) {
            $latitude = (float)$opencage_data['results'][0]['geometry']['lat'];
            $longitude = (float)$opencage_data['results'][0]['geometry']['lng'];
            $geocoding_successful = true;
            error_log("Geocoding successful: Lat=$latitude, Lng=$longitude for query: $geocode_query");
        } else {
            error_log("OpenCage returned no results for query: $geocode_query");
        }
    } else {
        error_log("OpenCage API request failed for query: $geocode_query");
    }

    // Fallback: Try geocoding with just location
    if (!$geocoding_successful) {
        $geocode_query = urlencode($location);
        $opencage_url = "https://api.opencagedata.com/geocode/v1/json?q=" . $geocode_query . "&key=" . $opencage_api_key . "&limit=1&countrycode=in";
        $opencage_response = @file_get_contents($opencage_url);

        if ($opencage_response !== false) {
            $opencage_data = json_decode($opencage_response, true);
            if ($opencage_data && isset($opencage_data['results']) && !empty($opencage_data['results'])) {
                $latitude = (float)$opencage_data['results'][0]['geometry']['lat'];
                $longitude = (float)$opencage_data['results'][0]['geometry']['lng'];
                $geocoding_successful = true;
                error_log("Fallback geocoding successful: Lat=$latitude, Lng=$longitude for query: $geocode_query");
            } else {
                error_log("OpenCage fallback returned no results for query: $geocode_query");
            }
        } else {
            error_log("OpenCage API fallback request failed for query: $geocode_query");
        }
    }

    // Check geocoding result
    if (!$geocoding_successful) {
        throw new Exception("Could not determine the location. Please provide a more specific address or area.");
    }

    // Database insertion
    $stmt = $pdo->prepare("
        INSERT INTO incidents
        (incident_type, title, description, location, area, date, time, reporter_name, reporter_email, photo_path, lat, lng, created_at)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $success = $stmt->execute([
        $incident_type,
        $title,
        $description,
        $location,
        $area,
        $date,
        $time,
        $reporter_name,
        $reporter_email,
        $photo_path,
        $latitude,
        $longitude,
    ]);

    if ($success) {
        $incident_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Incident report submitted successfully', 'incident_id' => $incident_id]);
    } else {
        error_log("Database insert failed. PDO Error Info: " . print_r($stmt->errorInfo(), true));
        throw new Exception("Failed to save incident report");
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => APP_ENV === 'development' ? "Database error: " . $e->getMessage() : 'Database error. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>