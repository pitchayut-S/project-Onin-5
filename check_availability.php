<?php
require_once "db.php"; 

if (isset($_POST['username'])) {
    $username = trim($_POST['username']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    echo ($stmt->get_result()->num_rows > 0) ? "taken" : "available";
    exit();
}

if (isset($_POST['phone'])) {
    $phone = trim($_POST['phone']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    echo ($stmt->get_result()->num_rows > 0) ? "taken" : "available";
    exit();
}
?>