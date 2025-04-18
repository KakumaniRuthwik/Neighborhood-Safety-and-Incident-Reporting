<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Base query
    $query = "
        SELECT 
            id,
            COALESCE(incident_type, 'other') AS type,
            COALESCE(title, 'Untitled') AS title,
            COALESCE(description, 'No description') AS description,
            COALESCE(area, 'Unknown') AS area,
            COALESCE(location, 'Unknown location') AS location,
            COALESCE(date, CURDATE()) AS date,
            COALESCE(time, '00:00:00') AS time,
            COALESCE(lat, 14.366700) AS lat,
            COALESCE(lng, 79.616700) AS lng,
            photo_path,
            created_at
        FROM incidents
        WHERE 1=1
    ";
    $params = [];

    // Apply type filter
    if (isset($_GET['type']) && $_GET['type'] !== 'all') {
        $query .= " AND incident_type = ?";
        $params[] = sanitizeInput($_GET['type']);
    }

    // Apply time filter
    if (isset($_GET['time'])) {
        $timeRange = sanitizeInput($_GET['time']);
        switch ($timeRange) {
            case '24h':
                $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }
    }

    // Count total rows for has_more
    $countQuery = "SELECT COUNT(*) as total FROM incidents WHERE 1=1";
    $countParams = [];
    if (isset($_GET['type']) && $_GET['type'] !== 'all') {
        $countQuery .= " AND incident_type = ?";
        $countParams[] = sanitizeInput($_GET['type']);
    }
    if (isset($_GET['time'])) {
        $countQuery .= str_replace('created_at', 'created_at', strstr($query, 'AND created_at'));
    }
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($countParams);
    $totalRows = $countStmt->fetchColumn();

    // Pagination
    $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 5;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $offset = ($page - 1) * $perPage;
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    // Execute main query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $incidents = $stmt->fetchAll();

    // Format incidents
    $formattedIncidents = array_map(function($incident) {
        return [
            'id' => (int)$incident['id'],
            'type' => sanitizeForJson($incident['type']),
            'title' => sanitizeForJson($incident['title']),
            'description' => sanitizeForJson($incident['description']),
            'location' => sanitizeForJson($incident['location']),
            'area' => sanitizeForJson($incident['area']),
            'date' => $incident['date'],
            'time' => substr($incident['time'], 0, 5),
            'lat' => (float)$incident['lat'],
            'lng' => (float)$incident['lng'],
            'photo_path' => $incident['photo_path'],
            'created_at' => $incident['created_at'],
            'time_ago' => getTimeAgo($incident['created_at'])
        ];
    }, $incidents);

    // Determine if more data exists
    $hasMore = ($offset + count($incidents)) < $totalRows;

    echo json_encode([
        'incidents' => $formattedIncidents,
        'has_more' => $hasMore,
        'page' => $page,
        'per_page' => $perPage,
        'total' => $totalRows
    ]);
} catch (Exception $e) {
    error_log("Error fetching incidents: " . $e->getMessage() . " | Query: $query | Params: " . json_encode($params));
    http_response_code(500);
    echo json_encode(['incidents' => [], 'has_more' => false, 'error' => APP_ENV === 'development' ? $e->getMessage() : 'Server error']);
}
?>