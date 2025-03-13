<?php
session_start();
header('Content-Type: application/json');

$notifications = [];

// Debug: Log session data
error_log(print_r($_SESSION, true));

if (isset($_SESSION['new_user'])) {
    $notifications[] = ["message" => "🎉 Welcome! Thank you for signing up, " . $_SESSION['new_user']];
}

if (isset($_SESSION['best_place_notification'])) {
    $notifications[] = ["message" => $_SESSION['best_place_notification']];
}

echo json_encode(["notifications" => $notifications]);
?>
