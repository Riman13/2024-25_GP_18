<?php
session_start();
$_SESSION['userID']=120;
if (!isset($_SESSION['userID'])) {
    header("Location: registration.php");
    exit();
}
?>