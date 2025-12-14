<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. เช็ครหัสผ่านตรงกันไหม
    if ($password !== $confirm_password) {
        echo "<script>alert('รหัสผ่านยืนยันไม่ตรงกัน'); window.location='index.php';</script>";
        exit();
    }

    // 2. เช็คว่ามี Username นี้หรือยัง
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('ชื่อผู้ใช้นี้มีคนใช้แล้ว กรุณาใช้ชื่ออื่น'); window.location='index.php';</script>";
        exit();
    }

    // 3. บันทึกข้อมูล (เข้ารหัสรหัสผ่าน + กำหนด role เริ่มต้นเป็น staff)
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);
    $default_role = 'staff'; // สมาชิกใหม่เป็นพนักงานธรรมดา

    $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, username, password, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $fullname, $phone, $email, $username, $password_hashed, $default_role);

    if ($stmt->execute()) {
        echo "<script>alert('สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาดในการสมัคร'); window.location='index.php';</script>";
    }
}
?>