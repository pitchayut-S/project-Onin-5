<?php
$servername = "localhost";
$username   = "root";
$password   = ""; 
$dbname     = "onin_shop";

// สร้างการเชื่อมต่อ (แบบ mysqli object)
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าภาษาไทยและอีโมจิให้รองรับ utf8mb4
$conn->set_charset("utf8mb4");
?>
