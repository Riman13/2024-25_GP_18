<?php
include 'config.php'; // Database connection

header('Content-Type: application/json');

// Get userId and placeId from GET request
$userId = $_GET['userId'];
$placeId = $_GET['placeId'];

// Prepare SQL to fetch rating
$stmt = $conn->prepare("SELECT Rating FROM ratings WHERE userID = ? AND placeID = ?");
$stmt->bind_param("ii", $userId, $placeId);
$stmt->execute();
$stmt->bind_result($rating);
$stmt->fetch();
$stmt->close();

// Return the rating, or 0 if no rating exists
echo json_encode(['rating' => $rating ? $rating : 0]);
?>