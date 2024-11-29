<?php
session_start();
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decode the incoming JSON data
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate and sanitize the name field
    if (isset($data['name']) && isset($_SESSION['userID'])) {
        $name = trim($data['name']);
        $userId = $_SESSION['userID'];

        // Update only the name in the database
        $updateQuery = "UPDATE users SET name = ? WHERE userId = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $name, $userId);

        // Execute the query and return the response
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update name.']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}

$conn->close();
?>
