<?php
session_start();
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // ใช้ Prepared Statement ป้องกัน SQL Injection
    $stmt = $conn->prepare("SELECT id, username, fullname, password, role FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            // Login สำเร็จ
            $_SESSION['userid'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role']; // เก็บสิทธิ์การใช้งาน (Admin/Staff)

            echo "
            <!DOCTYPE html>
            <html lang='th'>
            <head>
                <meta charset='UTF-8'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <style>body { font-family: 'Prompt', sans-serif; }</style>
            </head>
            <body>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'ยินดีต้อนรับคุณ " . $row['fullname'] . "',
                        text: 'เข้าสู่ระบบสำเร็จ',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location = 'dashboard.php';
                    });
                </script>
            </body>
            </html>";
            exit();
        }
    }

    // Login ไม่สำเร็จ
    echo "
    <script>
        alert('ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง');
        window.location='index.php';
    </script>";
}
?>