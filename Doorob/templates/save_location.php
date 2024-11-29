<?php
session_start();

// Get the location data from the request (sent via the JavaScript Fetch API)
$data = json_decode(file_get_contents('php://input'), true);

// Check if the necessary data is available
if (isset($data['user_id']) && isset($data['lat']) && isset($data['lng'])) {
    $_SESSION['Location Status'] = 'Allow';
    $_SESSION['Latitude'] = $data['lat'];
    $_SESSION['Longitude'] = $data['lng'];

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}
?>
