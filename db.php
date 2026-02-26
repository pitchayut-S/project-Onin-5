<?php
// ดึงค่าจาก Environment Variable (ถ้ามี) หรือใช้ค่าเริ่มต้นสำหรับรันบน XAMPP ปกติ
$servername = getenv('DB_HOST') ? getenv('DB_HOST') : "localhost";
$username   = getenv('DB_USER') ? getenv('DB_USER') : "root";
$password   = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : ""; 
$dbname     = getenv('DB_NAME') ? getenv('DB_NAME') : "onin_shop";

// สร้างการเชื่อมต่อ (แบบ mysqli object)
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าภาษาไทยและอีโมจิให้รองรับ utf8mb4
$conn->set_charset("utf8mb4");
?>
