<?php
ob_start();
session_start();
require_once "db.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. ดึงข้อมูล
    $stmt = $conn->prepare("SELECT id, username, fullname, password, role, status FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // เตรียม CSS สำหรับ SweetAlert เพื่อให้ปุ่มสวยด้วย
    $swal_style = "
        <style>
            body { 
                font-family: 'Prompt', sans-serif; 
                background-color: #f8f9fa; 
            }
            /* บังคับให้ปุ่ม SweetAlert ใช้ฟอนต์ Prompt */
            .swal2-popup, .swal2-title, .swal2-html-container, .swal2-confirm, .swal2-cancel {
                font-family: 'Prompt', sans-serif !important;
            }
        </style>";

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            
            // -----------------------------------------------------------
            // 2. กรณีโดนระงับ (Inactive)
            // -----------------------------------------------------------
            if ($row['status'] == 'inactive') {
                echo "
                <!DOCTYPE html>
                <html lang='th'>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                    <link href='https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap' rel='stylesheet'>
                    $swal_style
                </head>
                <body>
                    <script>
                        Swal.fire({
                            icon: 'error',
                            title: 'เข้าสู่ระบบไม่สำเร็จ',
                            text: 'บัญชีของคุณถูกระงับการใช้งาน กรุณาติดต่อผู้ดูแลระบบ',
                            confirmButtonColor: '#d33',
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            window.location = 'index.php';
                        });
                    </script>
                </body>
                </html>";
                exit();
            }

            // -----------------------------------------------------------
            // 3. Login สำเร็จ (Success)
            // -----------------------------------------------------------
            $_SESSION['user_id'] = $row['id']; 
            $_SESSION['username'] = $row['username'];
            $_SESSION['fullname'] = $row['fullname'];
            $_SESSION['role'] = $row['role']; 

            echo "
            <!DOCTYPE html>
            <html lang='th'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <link rel='icon' type='image/png' href='favicon.png'>
                <link href='https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap' rel='stylesheet'>
                $swal_style
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

    // -----------------------------------------------------------
    // 4. Login ไม่สำเร็จ (รหัสผิด/ไม่มี user)
    // -----------------------------------------------------------
    echo "
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
    <body>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'ผิดพลาด',
                text: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'ลองใหม่อีกครั้ง'
            }).then(() => {
                window.location = 'index.php';
            });
        </script>
    </body>
    </html>";
}
?>