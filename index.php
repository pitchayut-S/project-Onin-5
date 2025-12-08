<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onin Shop Stock - ระบบบริหารจัดการสต็อก</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        /* --- CSS พื้นฐาน --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body {
            height: 100vh; width: 100%;
            background: linear-gradient(180deg, #FFFFFF 40%, #8ab4e8 100%);
            display: flex; justify-content: center; align-items: center; flex-direction: column;
            overflow: hidden;
        }

        /* --- เนื้อหาหน้าหลัก --- */
        .main-content { text-align: center; padding: 20px; }
        h1 { color: #356CB5; font-size: 48px; font-weight: 600; margin-bottom: 10px; }
        p { color: #333; font-size: 24px; font-weight: 400; margin-bottom: 40px; }
        .btn-start {
            display: inline-block; background-color: #FFFFFF; color: #356CB5;
            font-size: 20px; font-weight: 600; padding: 12px 40px; border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;
            border: 1px solid rgba(53, 108, 181, 0.1); cursor: pointer;
        }
        .btn-start:hover { transform: translateY(-2px); box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15); }

        /* --- CSS ของ POPUP --- */
        .modal-overlay {
            display: none;
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5); z-index: 1000;
            justify-content: center; align-items: center;
            backdrop-filter: blur(4px);
        }

        .login-box {
            background-color: white; width: 500px; padding: 40px;
            border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: popupFadeIn 0.3s ease-out;
            position: relative;
        }

        .register-box {
            background-color: white; width: 800px; padding: 40px 60px;
            border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: popupFadeIn 0.3s ease-out;
            position: relative;
        }

        @keyframes popupFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .close-btn {
            position: absolute; top: 15px; right: 20px;
            font-size: 28px; font-weight: bold; color: #aaa;
            cursor: pointer; transition: 0.2s;
        }
        .close-btn:hover { color: #ff4d4d; }

        .header-text h3 { font-size: 16px; color: #555; text-align: center; margin-bottom: 5px; }
        .header-text h1 { font-size: 24px; color: #356CB5; text-align: center; margin-bottom: 30px; }
        .header-text h2 { font-size: 20px; color: #356CB5; text-align: center; margin-bottom: 20px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 14px; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .btn-submit {
            background-color: #356CB5; color: white;
            padding: 10px 25px; border: none; border-radius: 8px;
            cursor: pointer; transition: 0.3s;
        }
        .btn-submit:hover { background-color: #285291; }

        .link-switch { color: #666; cursor: pointer; }
        .link-switch:hover { color: #356CB5; }

        @media (max-width: 820px) {
            .register-box { width: 95%; padding: 20px; }
            .login-box { width: 90%; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="main-content">
        <h1>Onin Shop Stock</h1>
        <p>ระบบบริหารจัดการสต็อกร้านของชำ</p>
        <button class="btn-start" onclick="openModal('login')">เริ่มต้นใช้งาน</button>
    </div>

    <!-- ------------------ LOGIN MODAL ------------------ -->
    <div class="modal-overlay" id="loginModal">
        <div class="login-box">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>
            <div class="header-text">
                <h3>WELCOME TO</h3>
                <h1>Onin Shop Stock</h1>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>ชื่อผู้ใช้งาน</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน</label>
                    <input type="password" name="password" required>
                </div>

                <div style="display: flex; justify-content: space-between;">
                    <span class="link-switch" onclick="switchModal('register')">สมัครสมาชิกใหม่?</span>
                    <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ------------------ REGISTER MODAL ------------------ -->
    <div class="modal-overlay" id="registerModal">
        <div class="register-box">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>

            <div class="header-text">
                <h1>สมัครสมาชิก</h1>
                <h2>Onin Shop Stock</h2>
            </div>

            <form action="register.php" method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" name="fullname" required>
                    </div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="tel" name="phone" required>
                    </div>
                    <div class="form-group">
                        <label>ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่าน</label>
                        <input type="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label>อีเมล</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่าน</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>

                <div style="display:flex; justify-content: flex-end; gap: 20px; margin-top: 20px;">
                    <span class="link-switch" onclick="switchModal('login')">กลับไปเข้าสู่ระบบ</span>
                    <button type="submit" class="btn-submit">สมัครสมาชิก</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(type) {
            closeAllModals();
            document.getElementById(type + 'Modal').style.display = 'flex';
        }
        function switchModal(to) {
            openModal(to);
        }
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
