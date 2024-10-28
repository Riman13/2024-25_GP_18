<?php
include 'config.php';
include 'session.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);
$newPassword = $data['newPassword'];
$userId = $_SESSION['userID'];

// Validate and hash the password
if (!empty($newPassword)) {
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    $query = "UPDATE users SET password = ? WHERE userId = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $hashedPassword, $userId);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update password."]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid password."]);
}

$conn->close();

