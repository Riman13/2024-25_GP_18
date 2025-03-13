<?php
include 'session.php';
include 'config.php';

// Retrieve the user ID from the session
$user_id = $_SESSION['userID'];

// Query to fetch bookmarked places with place names and granular category
$sql = "SELECT p.place_name, p.granular_category, b.place_id 
        FROM bookmarks b
        JOIN riyadhplaces_doroob p ON b.place_id = p.id
        WHERE b.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookmarks = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $bookmarks[] = $row;
    }
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookmarked Places</title>
    <link rel="stylesheet" href="styles/favorite-list.css">
</head>
<body>
    <div class="bookmarks">
        <h2>Bookmarked Places</h2>
        <div class="bookmark-list">
            <?php if (!empty($bookmarks)): ?>
                <ul>
                    <?php foreach ($bookmarks as $bookmark): ?>
                        <li>
                            <strong>Place:</strong> <?php echo htmlspecialchars($bookmark['place_name']); ?><br>
                            <strong>Category:</strong> <?php echo htmlspecialchars($bookmark['granular_category']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-message">You haven't bookmarked any places yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>