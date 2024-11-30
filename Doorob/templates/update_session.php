<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $_SESSION['Location_Status'] = $data['status'];
    $_SESSION['Latitude'] = $data['lat'];
    $_SESSION['Longitude'] = $data['lng'];

    echo json_encode(['success' => true, 'message' => 'Session updated']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
