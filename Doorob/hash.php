<?php
include 'config.php'; 
$result = $conn->query("SELECT userID, password FROM users");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $userID = $row['userID'];
        $plainPassword = $row['password']; 
        if (strlen($plainPassword) < 60) {
            $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE userID = ?");
            $updateStmt->bind_param("si", $hashedPassword, $userID);

            if ($updateStmt->execute()) {
                echo "Updated user ID $userID with hashed password.<br>";
            } else {
                echo "Error updating user ID $userID: " . $conn->error . "<br>";
            }
        } else {
            echo "User ID $userID already has a hashed password.<br>";
        }
    }
} else {
    echo "No users found.";
}

$conn->close();
?>