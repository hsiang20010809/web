<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectToLogin() {
    header("Location: login.php");
    exit();
}

function logUserAction($userId, $action) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $userId, $action);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
?>
