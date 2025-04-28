<?php
session_start();
header('Content-Type: application/json');

$notifications = [];
$showAlert = false;
$alertMessage = "";



// Debug: Log session data
error_log(print_r($_SESSION, true));

if (isset($_SESSION['new_user'])) {
    $notifications[] = ["message" => "🎉 Welcome! Thank you for signing up, " . $_SESSION['new_user']];

    // If user has no ratings, set alert
    if (isset($_SESSION['userID']) && $_SESSION['userID'] > 8000) {
        $showAlert = true;
        $alertMessage = "👋 Hi " . $_SESSION['new_user'] . "! No recommendations are available yet—start rating to get personalized suggestions!";
    }
}

if (isset($_SESSION['best_place_notification'])) {
    $notifications[] = ["message" => $_SESSION['best_place_notification']];
}


echo json_encode([
    "notifications" => $notifications,
    "showAlert" => $showAlert,
    "alertMessage" => $alertMessage
]);
?>
