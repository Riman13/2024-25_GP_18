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
    echo "<script>
        alert('Token not found!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

if (strtotime($user["reset_token_expires_at"]) <= time()) {
    echo "<script>
        alert('Token has expired!');
        window.location.href = 'index.php';
    </script>";
    exit;
}

if ($_POST["password"] !== $_POST["password_confirmation"]) {
    echo "<script>
        alert('Passwords must match!');
        window.history.back(); // Go back to the form
    </script>";
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
    echo "<script>
        alert('Password updated successfully. You can now login.');
        window.location.href = 'registration.php';
    </script>";
} else {
    echo "<script>
        alert('An error occurred. Please try again.');
        window.history.back(); // Go back to the form
    </script>";
}
?>
