<?php
header('Content-Type: application/json');

// Database Configuration
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'safecommunities';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

// Establish Database Connection
$conn = new mysqli($host, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
}

$stats = [];

// Get total number of incidents
$result = $conn->query("SELECT COUNT(*) AS total FROM incidents");
$row = $result->fetch_assoc();
$stats['total'] = (int) $row['total'];
$result->free_result();

// Get counts of incidents by type
$result = $conn->query("SELECT type, COUNT(*) AS count FROM incidents GROUP BY type");
while ($row = $result->fetch_assoc()) {
    $stats[$row['type']] = (int) $row['count'];
}
$result->free_result();

$conn->close();

echo json_encode($stats);
?>