<?php
session_start();
require_once "db.php"; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์ม
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // เตรียม CSS สำหรับ SweetAlert (เพื่อให้ Font สวยเหมือนหน้าแรก)
    $swal_style = "
        <style>
            body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
            .swal2-popup, .swal2-title, .swal2-html-container, .swal2-confirm {
                font-family: 'Prompt', sans-serif !important;
            }
        </style>";

    // HTML Header สำหรับแจ้งเตือน
    $html_head = "
        <!DOCTYPE html>
        <html lang='th'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <link href='https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap' rel='stylesheet'>
            $swal_style
            <link rel='icon' type='image/png' href='favicon.png'>
        </head>
        <body>";
    
    $html_end = "</body></html>";

    // ----------------------------------------------------------------------
    // 2. ตรวจสอบเบื้องต้น (รหัสผ่านตรงกันไหม)
    // ----------------------------------------------------------------------
    if ($password !== $confirm_password) {
        echo $html_head . "
            <script>
                Swal.fire({
                    icon: 'warning',
                    title: 'รหัสผ่านไม่ตรงกัน',
                    text: 'กรุณากรอกรหัสผ่านและการยืนยันให้ตรงกัน',
                    confirmButtonColor: '#f39c12',
                    confirmButtonText: 'แก้ไขข้อมูล'
                }).then(() => {
                    window.history.back(); // กลับไปหน้าเดิม
                });
            </script>
        " . $html_end;
        exit();
    }

    // ----------------------------------------------------------------------
    // 3. ตรวจสอบ Username ซ้ำ 
    // ----------------------------------------------------------------------
    $checkUser = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkUser->bind_param("s", $username);
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        echo $html_head . "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'ชื่อผู้ใช้งานซ้ำ',
                    text: 'ชื่อ \"$username\" มีอยู่ในระบบแล้ว กรุณาใช้ชื่ออื่น',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'ลองใหม่อีกครั้ง'
                }).then(() => {
                    window.history.back();
                });
            </script>
        " . $html_end;
        exit();
    }

    // ----------------------------------------------------------------------
    // 4. ตรวจสอบ เบอร์โทรศัพท์ ซ้ำ
    // ----------------------------------------------------------------------
    $checkPhone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $checkPhone->bind_param("s", $phone);
    $checkPhone->execute();
    if ($checkPhone->get_result()->num_rows > 0) {
        echo $html_head . "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'เบอร์โทรศัพท์ซ้ำ',
                    text: 'เบอร์ \"$phone\" ถูกลงทะเบียนไปแล้ว',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'ลองใหม่อีกครั้ง'
                }).then(() => {
                    window.history.back();
                });
            </script>
        " . $html_end;
        exit();
    }

    // ----------------------------------------------------------------------
    // 5. บันทึกข้อมูลลงฐานข้อมูล (ถ้าผ่านทุกด่าน)
    // ----------------------------------------------------------------------
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'staff';    // ค่าเริ่มต้นเป็นพนักงาน
    $status = 'active'; // ค่าเริ่มต้นสถานะปกติ

    $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $fullname, $phone, $email, $username, $hashed_password, $role, $status);

    if ($stmt->execute()) {
        // สมัครสำเร็จ
        echo $html_head . "
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'สมัครสมาชิกสำเร็จ!',
                    text: 'คุณสามารถเข้าสู่ระบบได้ทันที',
                    confirmButtonColor: '#356CB5',
                    confirmButtonText: 'เข้าสู่ระบบ'
                }).then(() => {
                    window.location = 'index.php'; // กลับไปหน้า Login
                });
            </script>
        " . $html_end;
    } else {
        // Error DB
        echo $html_head . "
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: '" . $conn->error . "',
                    confirmButtonText: 'ตกลง'
                }).then(() => {
                    window.history.back();
                });
            </script>
        " . $html_end;
    }
}
?>