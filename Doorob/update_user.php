<?php
session_start();
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input from the request
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim($data['name']);
    $email = trim($data['email']);
    $userId = $_SESSION['userID'];

    // Prepare an update statement
    $updateQuery = "UPDATE users SET name = ?, email = ? WHERE userId = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssi", $name, $email, $userId);

    if ($stmt->execute()) {
        // Respond with success
        echo json_encode(['success' => true]);
    } else {
        // Respond with an error message
        echo json_encode(['success' => false, 'error' => 'Failed to update user information.']);
    }

    $stmt->close();
}

$conn->close();
?>