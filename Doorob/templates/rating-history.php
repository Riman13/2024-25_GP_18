<?php
include 'session.php';
include 'config.php';

// Retrieve the user ID from the session
$user_id = $_SESSION['userID'];

// Query to fetch only the latest rating per place for the logged-in user
$sql = "SELECT r.rating, p.place_name, r.placeID 
        FROM ratings r
        JOIN riyadhplaces_doroob p ON r.placeID = p.ID
        WHERE r.userID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$ratings = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $ratings[] = $row;
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
    <title>Favorite List</title>
    <link rel="stylesheet" href="styles/rating-history.css">
</head>
<body>
    <div class="rating">
        <h2>Rating History</h2>
        <div class="rating-list">
            <?php if (!empty($ratings)): ?>
                <ul>
                    <?php foreach ($ratings as $rating): ?>
                        <li>
                            <strong>Place:</strong> <?php echo htmlspecialchars($rating['place_name']); ?><br>
                            <strong>Rating:</strong> <?php echo htmlspecialchars($rating['rating']); ?>/5
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="empty-message">You haven't rated any place yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>