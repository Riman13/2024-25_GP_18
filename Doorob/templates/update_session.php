<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['status'])) {
    if ($data['status'] === 'location') {
        $_SESSION['location'] = true;
        echo json_encode(['success' => true]);
    } elseif ($data['status'] === 'camera') {
        $_SESSION['camera'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing status']);
}
