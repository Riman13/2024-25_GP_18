<?php
include 'config.php';
include 'session.php';

$placeId = $_POST['placeId'];
$userId = $_POST['userId'];

$stmt = $conn->prepare("SELECT 1 FROM bookmarks WHERE user_id = ? AND place_id = ?");
$stmt->bind_param("ii", $userId, $placeId);
$stmt->execute();
$stmt->store_result();

$response = ['bookmarked' => $stmt->num_rows > 0];

echo json_encode($response);
$conn->close();
?>