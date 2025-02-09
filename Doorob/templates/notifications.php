<?php
session_start();
header('Content-Type: application/json');
$notifications = [];

// Check if there's a new user notification
if (isset($_SESSION['new_user'])) {
    $notifications[] = ["message" => "🎉 Welcome! Thank you for signing up, " . $_SESSION['new_user']];
    // Do not unset $_SESSION['new_user'] here
}


// Send back the notifications to the client
echo json_encode(["notifications" => $notifications]);
?>
