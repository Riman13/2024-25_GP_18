<?php
session_start();
include 'config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['eml'];
    $password = $_POST['pass'];

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$checkEmailQuery = "SELECT * FROM users WHERE email = ?";
$stmt = $conn->prepare($checkEmailQuery);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already exists. Please try logging in.";
    header("Location: registration.php");
    exit();
} else {
    $insertQuery = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("sss", $name, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['userID'] = $conn->insert_id;
        $_SESSION['user_type'] = 'user';  
        header("Location: homepage.php");
        exit();
    } else {

        $_SESSION['error'] = "There was an error signing up. Please try again.";
        header("Location: signup_page.php");
        exit();
    } }}