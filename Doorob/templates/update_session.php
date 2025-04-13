<?php
session_start();
$data = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false];

if (isset($data['status'])) {
    switch ($data['status']) {
        case 'enable_location':
            $_SESSION['location'] = true;
            $response['success'] = true;
            break;
        case 'disable_location':
            unset($_SESSION['location']);
            $response['success'] = true;
            break;
        case 'enable_camera':
            $_SESSION['camera'] = true;
            $response['success'] = true;
            break;
        case 'disable_camera':
            unset($_SESSION['camera']);
            $response['success'] = true;
            break;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
