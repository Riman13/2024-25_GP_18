<?php
include 'config.php';
include 'session.php';

$placeId = $_POST['placeId'];
$userId = $_POST['userId'];
$action = $_POST['action'];

$response = ['success' => false, 'error' => ''];

if ($action === 'add') {
    // Check if the table is empty before adding a new bookmark
    $checkEmptyStmt = $conn->prepare("SELECT COUNT(*) FROM bookmarks");
    $checkEmptyStmt->execute();
    $count = $checkEmptyStmt->get_result()->fetch_row()[0];

    if ($count == 0) {
        // Table is empty, reset auto-increment
        $resetAutoIncrementStmt = $conn->prepare("ALTER TABLE bookmarks AUTO_INCREMENT = 1");
        if (!$resetAutoIncrementStmt->execute()) {
            $response['error'] = "Failed to reset auto-increment: " . $resetAutoIncrementStmt->error;
            echo json_encode($response);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, place_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $userId, $placeId);
} elseif ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND place_id = ?");
    $stmt->bind_param("ii", $userId, $placeId);

    // Check if the table is empty after deleting a bookmark
    $checkEmptyStmt = $conn->prepare("SELECT COUNT(*) FROM bookmarks");
    $checkEmptyStmt->execute();
    $count = $checkEmptyStmt->get_result()->fetch_row()[0];

    if ($count == 0) {
        $resetAutoIncrementStmt = $conn->prepare("ALTER TABLE bookmarks AUTO_INCREMENT = 1");
        if (!$resetAutoIncrementStmt->execute()) {
            $response['error'] = "Failed to reset auto-increment: " . $resetAutoIncrementStmt->error;
            echo json_encode($response);
            exit;
        }
    }
}

if ($stmt && $stmt->execute()) {
    $response['success'] = true;
} else {
    $response['error'] = $stmt ? "Database error: " . $stmt->error : 'Database error.';
}

echo json_encode($response);
$conn->close();
?>