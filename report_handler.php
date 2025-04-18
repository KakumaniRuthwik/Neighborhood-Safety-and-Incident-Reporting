<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json'); // Make sure response is JSON

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    // Log raw POST and FILES for debugging (only in development environment)
    if (APP_ENV === 'development') {
        error_log("POST data: " . json_encode($_POST));
        error_log("FILES data: " . json_encode($_FILES));
    }

    // Required fields
    $required_fields = ['incident_type', 'title', 'description', 'location', 'area', 'date', 'time', 'consent'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Required field missing or empty: $field");
        }
    }

    // Validate date (can't be in the future)
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
        if ($_FILES['photo']['size'] > $config['max_file_size']) {
            throw new Exception("File too large. Max size: " . ($config['max_file_size'] / (1024 * 1024)) . "MB");
        }
        if (!isAllowedFileType($_FILES['photo'], $config['allowed_extensions'])) {
            throw new Exception("File type not allowed: " . implode(', ', $config['allowed_extensions']));
        }
        $filename = generateUniqueFilename($_FILES['photo']['name']);
        $upload_path = $config['upload_dir'] . $filename;
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            throw new Exception("Failed to upload file");
        }
        $photo_path = $upload_path;
    } elseif (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        throw new Exception("File upload error: " . $_FILES['photo']['error']);
    }

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO incidents 
        (incident_type, title, description, location, area, date, time, reporter_name, reporter_email, photo_path, lat, lng, created_at) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
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
        14.366700, // Placeholder latitude
        79.616700, // Placeholder longitude
    ]);

    if (!$success) {
        throw new Exception("Database insert failed");
    }

    $incident_id = $pdo->lastInsertId();
    if (!$incident_id) {
        throw new Exception("Failed to retrieve inserted incident ID");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Incident report submitted successfully',
        'incident_id' => $incident_id
    ]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => APP_ENV === 'development' ? "Database error: " . $e->getMessage() : 'Database error. Please try again.'
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
