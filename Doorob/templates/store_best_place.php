<?php
session_start();
header('Content-Type: application/json');

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['place_name'])) {


    $_SESSION['best_place_notification'] = "🌟 Check out this amazing spot: " . $data['place_name'] ;

    // Debugging: Print session data
    echo json_encode([
        "status" => "success",
        "message" => $_SESSION['best_place_notification'],
        "session_data" => $_SESSION
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No valid place data received",
        "session_data" => $_SESSION
    ]);
}
?>

