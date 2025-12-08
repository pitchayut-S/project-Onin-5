<?php
session_start();
session_destroy(); // ล้างข้อมูล Session ทั้งหมด
header("Location: index.php"); // ส่งกลับไปหน้า Login
exit();
?>