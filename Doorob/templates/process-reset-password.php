<?php
include 'config.php'; 

$token = $_POST["token"];

$token_hash = hash("sha256", $token);

$sql = "SELECT * FROM users
        WHERE reset_token_hash = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $token_hash);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user === null) {
    header("Location: reset-password.php?token=$token&error=Token+not+found");
    exit;
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    header("Location: reset-password.php?token=$token&error=Token+has+expired");
    exit;
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    header("Location: reset-password.php?token=$token&error=Passwords+must+match");
    exit;
}

$password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

$sql = "UPDATE users
        SET Password = ?,
            reset_token_hash = NULL,
            reset_token_expires_at = NULL
        WHERE UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $password_hash, $user["UserID"]);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    header("Location: registration.php?success=Password updated successfully. You can now login.");
    exit;
    } else {
    header("Location: reset-password.php?token=$token&error=Something+went+wrong.+Please+try+again");
}
?>
