<?php
session_start();
// ถ้าล็อกอินอยู่แล้ว ให้เด้งไป Dashboard เลย ไม่ต้องล็อกอินซ้ำ
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onin Shop Stock - เข้าสู่ระบบ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* (ใช้ CSS เดิมของคุณได้เลยครับ ผมละไว้เพื่อความกระชับ) */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body {
            height: 100vh; width: 100%;
            background: linear-gradient(180deg, #FFFFFF 40%, #8ab4e8 100%);
            display: flex; justify-content: center; align-items: center; flex-direction: column;
            overflow: hidden;
        }
        /* ... CSS เดิม ... */
        /* เพิ่มเติม CSS ให้ Popup */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .login-box { background-color: white; width: 500px; padding: 40px; border-radius: 15px; position: relative; }
        .register-box { background-color: white; width: 800px; padding: 40px; border-radius: 15px; position: relative; }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-submit { background-color: #356CB5; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .link-switch { color: #356CB5; cursor: pointer; display:block; text-align:right; margin-bottom:10px; }
        .btn-start { padding: 15px 35px; font-size: 26px; border-radius: 10px; border: 1px solid #ffffff; cursor: pointer; background: white; color: #356CB5; font-weight: bold;}

        /*  ส่วนที่เพิ่ม: ปรับฟอนต์ SweetAlert2 ให้เป็น Prompt  */
        div:where(.swal2-container) .swal2-popup {
            font-family: 'Prompt', sans-serif !important;
        }
        div:where(.swal2-container) .swal2-title {
            font-weight: 600 !important;
            color: #333 !important;
        }
        div:where(.swal2-container) .swal2-html-container {
            font-weight: 400 !important;
            color: #666 !important;
        }
    </style>
</head>
<body>

    <div class="main-content" style="text-align: center;">
        <h1 style="color: #356CB5; font-size: 65px;">Onin Shop Stock</h1>
        <p style="font-size: 30px; margin-bottom: 20px;">ระบบบริหารจัดการสต็อกร้านของชำ</p>
        <button class="btn-start" onclick="openModal('login')">เริ่มต้นใช้งาน</button>
    </div>

    <div class="modal-overlay" id="loginModal">
        <div class="login-box">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>
            
            <div style="text-align: center; margin-bottom: 30px;">
                <h3 style="margin: 0; color: #7f8c8d; font-weight: 400; font-size: 20px;">ยินดีต้อนรับสู่</h3>
                <h1 style="margin: 5px 0 0 0; color: #356CB5; font-size: 32px; font-weight: 600;">Onin Shop Stock</h1>
            </div>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <input type="text" name="username" placeholder="ชื่อผู้ใช้งาน" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="รหัสผ่าน" required>
                </div>
                <span class="link-switch" onclick="switchModal('register')">ยังไม่มีบัญชี? สมัครสมาชิก</span>
                <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="registerModal">
        <div class="register-box">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>
            <h2 style="text-align:center; color:#356CB5; margin-bottom:20px;">สมัครสมาชิก</h2>

            <form action="register.php" method="POST">
                <div class="form-grid">
                    <div class="form-group"><input type="text" name="fullname" placeholder="ชื่อ-นามสกุล" required></div>
                    <div class="form-group"><input type="tel" name="phone" placeholder="เบอร์โทรศัพท์" required></div>
                    <div class="form-group"><input type="email" name="email" placeholder="อีเมล" required></div>
                    <div class="form-group"><input type="text" name="username" placeholder="ตั้งชื่อผู้ใช้งาน" required></div>
                    <div class="form-group"><input type="password" name="password" placeholder="รหัสผ่าน" required></div>
                    <div class="form-group"><input type="password" name="confirm_password" placeholder="ยืนยันรหัสผ่าน" required></div>
                </div>
                <span class="link-switch" onclick="switchModal('login')">มีบัญชีแล้ว? เข้าสู่ระบบ</span>
                <button type="submit" class="btn-submit">ยืนยันการสมัคร</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(type) {
            closeAllModals();
            document.getElementById(type + 'Modal').style.display = 'flex';
        }
        function switchModal(to) { openModal(to); }
        function closeAllModals() {
            document.getElementById('loginModal').style.display = 'none';
            document.getElementById('registerModal').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.className === 'modal-overlay') { closeAllModals(); }
        }
    </script>
</body>
</html>