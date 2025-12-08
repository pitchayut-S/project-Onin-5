<?php
$servername = "localhost";
$username = "root";
$password = ""; // ปกติ XAMPP จะไม่มีรหัสผ่าน (ปล่อยว่างไว้)
$dbname = "onin_shop";

// สร้างการเชื่อมต่อ
$conn = mysqli_connect($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
// ตั้งค่าภาษาไทยให้รองรับ UTF-8
mysqli_set_charset($conn, "utf8");
?>