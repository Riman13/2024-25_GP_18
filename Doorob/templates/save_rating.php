<?php
include 'config.php';
include 'session.php';

header('Content-Type: application/json');

// Read and decode JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Check if JSON parsing was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input: ' . json_last_error_msg()]);
    exit;
}

// Validate input fields
if (!isset($input['userId'], $input['placeId'], $input['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$userId = $input['userId'];
$placeId = $input['placeId'];
$rating = $input['rating'];

// Debug: Log the values being used for the SQL statement
error_log("User ID: $userId, Place ID: $placeId, Rating: $rating");

// Prepare and execute the SQL statement
$stmt = $conn->prepare("INSERT INTO ratings (userID, placeID, Rating) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare statement failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iii", $userId, $placeId, $rating);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>