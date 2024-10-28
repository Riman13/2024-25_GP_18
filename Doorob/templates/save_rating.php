<?php
include 'config.php';
include 'session.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['userId'], $input['placeId'], $input['rating'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$userId = $input['userId'];
$placeId = $input['placeId'];
$rating = $input['rating'];

// Check if a rating already exists for this user and place
$stmt = $conn->prepare("SELECT ID FROM ratings WHERE userID = ? AND placeID = ?");
$stmt->bind_param("ii", $userId, $placeId);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Update existing rating
    $stmt->close();
    $updateStmt = $conn->prepare("UPDATE ratings SET Rating = ? WHERE userID = ? AND placeID = ?");
    $updateStmt->bind_param("iii", $rating, $userId, $placeId);
    $success = $updateStmt->execute();
    $updateStmt->close();
} else {
    // Insert new rating
    $stmt->close();
    $insertStmt = $conn->prepare("INSERT INTO ratings (userID, placeID, Rating) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iii", $userId, $placeId, $rating);
    $success = $insertStmt->execute();
    $insertStmt->close();
}

$conn->close();

echo json_encode(['success' => $success]);
?>