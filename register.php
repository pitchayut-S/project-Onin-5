<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    // ถ้ารหัสไม่ตรงกัน
    if ($password !== $confirm) {
        echo "<script>alert('รหัสผ่านไม่ตรงกัน'); window.location='index.php';</script>";
        exit();
    }

    // เข้ารหัสรหัสผ่าน
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // ตรวจสอบชื่อผู้ใช้ซ้ำ
    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        echo "<script>alert('ชื่อผู้ใช้งานนี้ถูกใช้แล้ว'); window.location='index.php';</script>";
        exit();
    }

    // บันทึกข้อมูล
    $sql = "INSERT INTO users (fullname, phone, username, email, password)
            VALUES ('$fullname', '$phone', '$username', '$email', '$hash')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('สมัครสมาชิกสำเร็จ!'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด'); window.location='index.php';</script>";
    }
}
?>
