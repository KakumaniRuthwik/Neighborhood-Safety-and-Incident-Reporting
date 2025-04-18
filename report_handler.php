<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json'); // Ensure response is JSON

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Replace with your actual OpenCage API key
$opencage_api_key = 'd8522d3d3f704523b33dd8999b7d6a61';

try {
    // Log raw POST and FILES for debugging (only in development environment)
    if (APP_ENV === 'development') {
        error_log("POST data: " . json_encode($_POST));
        error_log("FILES data: " . json_encode($_FILES));
    }

    // Required fields validation
    $required_fields = ['incident_type', 'title', 'description', 'location', 'area', 'date', 'time', 'consent'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Required field missing or empty: $field");
        }
    }

    // Date validation
    if (strtotime($_POST['date']) > time()) {
        throw new Exception("Incident date cannot be in the future");
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
    $reporter_email = isset($_POST['reporter_email']) && trim($_POST['reporter_email']) ? sanitizeInput($_POST['reporter_email']) : null;

    // Handle file upload
    $photo_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; // Ensure this directory exists and is writable
        $filename = uniqid() . '_' . basename($_FILES['photo']['name']);
        $photo_path = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            throw new Exception("Failed to upload photo");
        }
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception("File upload error: " . $_FILES['photo']['error']);
    }

    $latitude = null;
    $longitude = null;
    $geocoding_successful = false;

    // Attempt geocoding with browser-provided coordinates first
    if (isset($_POST['latitude']) && is_numeric($_POST['latitude']) && isset($_POST['longitude']) && is_numeric($_POST['longitude'])) {
        $latitude = floatval($_POST['latitude']);
        $longitude = floatval($_POST['longitude']);
        $geocoding_successful = true;
        error_log("Location obtained from browser geolocation: Lat=" . $latitude . ", Lng=" . $longitude);
    } else {
        // Geocoding logic using OpenCage if no browser coordinates
        $geocode_query = urlencode($location . ", " . $area);
        $opencage_url = "https://api.opencagedata.com/geocode/v1/json?q=" . $geocode_query . "&key=" . $opencage_api_key . "&limit=1&countrycode=in"; // Focusing on India

        $opencage_response_combined = @file_get_contents($opencage_url);
        error_log("OpenCage Response (Combined): " . $opencage_response_combined);
        $opencage_data_combined = json_decode($opencage_response_combined, true);

        if ($opencage_data_combined && isset($opencage_data_combined['results']) && !empty($opencage_data_combined['results'])) {
            $latitude = (float)$opencage_data_combined['results'][0]['geometry']['lat'];
            $longitude = (float)$opencage_data_combined['results'][0]['geometry']['lng'];
            $geocoding_successful = true;
        } else {
            error_log("OpenCage geocoding failed for location and area: " . $location . ", " . $area);
            // Fallback: Attempt geocoding with just the location
            $opencage_url_fallback = "https://api.opencagedata.com/geocode/v1/json?q=" . urlencode($location) . "&key=" . $opencage_api_key . "&limit=1&countrycode=in";
            $opencage_response_fallback = @file_get_contents($opencage_url_fallback);
            error_log("OpenCage Response (Fallback): " . $opencage_response_fallback);
            $opencage_data_fallback = json_decode($opencage_response_fallback, true);

            if ($opencage_data_fallback && isset($opencage_data_fallback['results']) && !empty($opencage_data_fallback['results'])) {
                $latitude = (float)$opencage_data_fallback['results'][0]['geometry']['lat'];
                $longitude = (float)$opencage_data_fallback['results'][0]['geometry']['lng'];
                $geocoding_successful = true;
            } else {
                error_log("OpenCage fallback geocoding failed for location: " . $location);
                $geocoding_successful = false;
            }
        }
    }

    // Database insertion (only if geocoding was successful)
    if ($geocoding_successful) {
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
            echo json_encode(['success' => false, 'message' => 'Database insert failed']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not determine the location of the incident. Please provide a more accurate location or area.']);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => APP_ENV === 'development' ? "Database error: " . $e->getMessage() : 'Database error. Please try again.']);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>