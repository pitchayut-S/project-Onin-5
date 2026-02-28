<?php
ob_start();
session_start();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="favicon.png">
    
    <style>
        /* --- CSS เดิมทั้งหมด คงไว้ 100% --- */
        :root { --primary: #356CB5; --primary-dark: #2a5298; --input-bg: #f0f2f5; --text-dark: #333; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body { height: 100vh; width: 100%; background: linear-gradient(180deg, #FFFFFF 30%, #8ab4e8 100%); display: flex; justify-content: center; align-items: center; flex-direction: column; overflow: hidden; }
        .main-content { text-align: center; }
        .main-title { color: #356CB5; font-size: 65px; margin-bottom: 0; font-weight: 700; }
        .sub-title { font-size: 30px; margin-bottom: 20px; color: #555; }
        .btn-start { padding: 10px 25px; font-size: 24px; border-radius: 10px; border: 1px solid #ffffff; cursor: pointer; background: white; color: #356CB5; font-weight: 700; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease; }
        .btn-start:hover { transform: none; background-color: #356CB5; color: white; border-color: #356CB5; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s ease; }
        .form-box { background: #fff; padding: 40px 50px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); width: 100%; position: relative; animation: slideUp 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28); }
        .login-size { max-width: 420px; }
        .register-size { max-width: 750px; }
        .close-btn { position: absolute; top: 5px; right: 25px; font-size: 30px; color: #ccc; cursor: pointer; transition: 0.2s; }
        .close-btn:hover { color: var(--text-dark); }
        .form-header { text-align: center; margin-bottom: 25px; }
        .form-header h2 { color: var(--primary); font-size: 28px; font-weight: 600; }
        .form-header p { color: #888; font-size: 14px; margin-top: 5px; }
        
        /* ปรับ margin-bottom เพิ่มนิดเดียวเพื่อเผื่อที่ให้ข้อความลอย */
        .input-group { position: relative; margin-bottom: 20px; } 
        
        .input-icon { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; font-size: 16px; transition: 0.3s; }
        .custom-input { width: 100%; padding: 12px 15px 12px 45px; border: 2px solid transparent; background-color: var(--input-bg); border-radius: 10px; font-size: 16px; color: #333; transition: all 0.3s; }
        .custom-input::placeholder { color: #bbb; }
        .custom-input:focus { background-color: #fff; border-color: var(--primary); box-shadow: 0 4px 10px rgba(53, 108, 181, 0.1); outline: none; }
        .custom-input:focus + .input-icon { color: var(--primary); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .btn-submit { width: 100%; padding: 14px; background: #356cb5 !important; color: white; border: none; border-radius: 10px; font-size: 18px; font-weight: 500; cursor: pointer; margin-top: 10px; box-shadow: 0 4px 15px rgba(53, 108, 181, 0.3); transition: all 0.3s ease; }
        .btn-submit:hover { transform: none; background-color: #0b4993; color: white; border-color: #0b4993; }
        .switch-link { display: block; text-align: center; margin-top: 20px; font-size: 14px; color: #666; cursor: pointer; }
        .switch-link b { color: var(--primary); font-weight: 600; }
        .switch-link:hover b { text-decoration: underline; }
        
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
        @keyframes slideUp { from {opacity:0; transform:translateY(50px);} to {opacity:1; transform:translateY(0);} }
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; gap: 0; } .form-box { padding: 30px 20px; width: 90%; } }
        
        div:where(.swal2-container) .swal2-popup, .swal2-title, .swal2-html-container { font-family: 'Prompt', sans-serif !important; }

        /* --- จุดสำคัญ: ทำให้ข้อความลอย แล้วไม่ดัน Layout --- */
        .msg-alert { 
            position: absolute; /* ลอยอิสระ */
            bottom: -18px;      /* อยู่ใต้กล่องพอดี */
            left: 5px;
            font-size: 11px; 
            white-space: nowrap; /* ห้ามขึ้นบรรทัดใหม่ */
        }
        
        .text-red { color: #e74c3c; font-weight: 500; }
        .text-green { color: #27ae60; font-weight: 500; }
        .input-error { border-color: #e74c3c !important; background-color: #fff !important; }
        .input-success { border-color: #27ae60 !important; background-color: #fff !important; }
        .btn-submit:disabled { background-color: #ccc; cursor: not-allowed; box-shadow: none; }
    </style>
</head>
<body>

    <div class="main-content">
        <h1 class="main-title">Onin Shop Stock</h1>
        <p class="sub-title">ระบบบริหารจัดการสต็อกร้านของชำ</p>
        <button class="btn-start" onclick="openModal('login')">เริ่มต้นใช้งาน</button>
    </div>

    <div class="modal-overlay" id="loginModal">
        <div class="form-box login-size">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>
            <div class="form-header">
                <h2>เข้าสู่ระบบ</h2>
                <p>กรุณาเข้าสู่ระบบ</p>
            </div>
            <form action="login.php" method="POST">
                <div class="input-group">
                    <input type="text" name="username" class="custom-input" placeholder="ชื่อผู้ใช้งาน" required>
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
                <div class="input-group">
                    <input type="password" name="password" class="custom-input" placeholder="รหัสผ่าน" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                </div>
                <button type="submit" class="btn-submit">เข้าสู่ระบบ</button>
                <div class="switch-link" onclick="switchModal('register')">ยังไม่มีบัญชี? <b>สมัครสมาชิก</b></div>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="registerModal">
        <div class="form-box register-size">
            <span class="close-btn" onclick="closeAllModals()">&times;</span>
            <div class="form-header">
                <h2>สมัครสมาชิกใหม่</h2>
                <p>กรอกข้อมูลเพื่อสร้างบัญชี</p>
            </div>

            <form action="register.php" method="POST" id="regForm">
                <div class="form-grid">
                    <div class="input-group">
                        <input type="text" name="fullname" class="custom-input" placeholder="ชื่อ-นามสกุล" required>
                        <i class="fa-solid fa-id-card input-icon"></i>
                    </div>
                    
                    <div class="input-group">
                        <input type="tel" name="phone" id="phone" class="custom-input" placeholder="เบอร์โทรศัพท์" required minlength="10" maxlength="10" pattern="[0-9]{10}" oninput="this.value = this.value.replace(/[^0-9]/g, ''); checkPhone();">
                        <i class="fa-solid fa-phone input-icon"></i>
                        <span id="phone_msg" class="msg-alert"></span>
                    </div>

                    <div class="input-group">
                        <input type="email" name="email" class="custom-input" placeholder="อีเมล" required>
                        <i class="fa-solid fa-envelope input-icon"></i>
                    </div>
                    
                    <div class="input-group">
                        <input type="text" name="username" id="username" class="custom-input" placeholder="ตั้งชื่อผู้ใช้งาน" required>
                        <i class="fa-solid fa-user-plus input-icon"></i>
                        <span id="username_msg" class="msg-alert"></span>
                    </div>

                    <div class="input-group">
                        <input type="password" name="password" id="password" class="custom-input" placeholder="รหัสผ่าน" required>
                        <i class="fa-solid fa-lock input-icon"></i>
                    </div>
                    <div class="input-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="custom-input" placeholder="ยืนยันรหัสผ่าน" required>
                        <i class="fa-solid fa-check-circle input-icon"></i>
                        <span id="pass_msg" class="msg-alert"></span>
                    </div>
                </div>

                <button type="submit" id="btn_submit" class="btn-submit">ยืนยันการสมัคร</button>
                <div class="switch-link" onclick="switchModal('login')">มีบัญชีอยู่แล้ว? <b>เข้าสู่ระบบ</b></div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openModal(type) {
            closeAllModals();
            document.getElementById(type + 'Modal').classList.add('active');
        }
        function closeAllModals() {
            document.querySelectorAll('.modal-overlay').forEach(el => el.classList.remove('active'));
        }
        function switchModal(to) {
            closeAllModals();
            setTimeout(() => { openModal(to); }, 150);
        }
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) closeAllModals();
        }

        $(document).ready(function() {
            function checkFormValidity() {
                if ($('.input-error').length > 0) {
                    $('#btn_submit').prop('disabled', true);
                } else {
                    $('#btn_submit').prop('disabled', false);
                }
            }

            // เช็ค Username
            $('#username').on('blur keyup', function() {
                let val = $(this).val();
                if(val.length >= 3) {
                    $.post('check_availability.php', { username: val }, function(res) {
                        if (res == 'taken') {
                            $('#username_msg').html('<i class="fa-solid fa-circle-xmark"></i> มีผู้ใช้งานแล้ว').removeClass('text-green').addClass('text-red');
                            $('#username').removeClass('input-success').addClass('input-error');
                        } else {
                            $('#username_msg').html('<i class="fa-solid fa-circle-check"></i> ใช้งานได้').removeClass('text-red').addClass('text-green');
                            $('#username').removeClass('input-error').addClass('input-success');
                        }
                        checkFormValidity();
                    });
                } else {
                    $('#username_msg').text('');
                    $('#username').removeClass('input-error input-success');
                }
            });

            // เช็ค Phone
            $('#phone').on('blur keyup', function() {
                let val = $(this).val();
                if(val.length >= 9) {
                    $.post('check_availability.php', { phone: val }, function(res) {
                        if (res == 'taken') {
                            $('#phone_msg').html('<i class="fa-solid fa-circle-xmark"></i> เบอร์นี้ใช้แล้ว').removeClass('text-green').addClass('text-red');
                            $('#phone').removeClass('input-success').addClass('input-error');
                        } else {
                            $('#phone_msg').html('<i class="fa-solid fa-circle-check"></i> ใช้งานได้').removeClass('text-red').addClass('text-green');
                            $('#phone').removeClass('input-error').addClass('input-success');
                        }
                        checkFormValidity();
                    });
                } else {
                    $('#phone_msg').text('');
                    $('#phone').removeClass('input-error input-success');
                }
            });

            // เช็ค Password
            $('#confirm_password, #password').on('keyup', function() {
                let pass = $('#password').val();
                let confirm = $('#confirm_password').val();
                if (pass && confirm) {
                    if (pass !== confirm) {
                        $('#pass_msg').html('<i class="fa-solid fa-circle-xmark"></i> ไม่ตรงกัน').removeClass('text-green').addClass('text-red');
                        $('#confirm_password').removeClass('input-success').addClass('input-error');
                    } else {
                        $('#pass_msg').html('<i class="fa-solid fa-circle-check"></i> ตรงกัน').removeClass('text-red').addClass('text-green');
                        $('#confirm_password').removeClass('input-error').addClass('input-success');
                    }
                    checkFormValidity();
                }
            });

            function checkPhone() {
                let phoneInput = document.getElementById('phone');
                let phoneMsg = document.getElementById('phone_msg');
                let phone = phoneInput.value;

                if (phone.length === 0) {
                    phoneMsg.innerText = ""; 
                } else if (phone.length > 0 && phone.length < 10) {
                    // กรณีพิมพ์ไม่ครบ 10 ตัว
                    phoneMsg.innerText = "กรุณากรอกเบอร์โทรศัพท์ให้ครบ 10 หลัก";
                    phoneMsg.style.color = "#dc2626"; // สีแดง
                } else if (phone.length === 10) {
                    // กรณีครบ 10 ตัว -> ส่ง AJAX ไปเช็คในฐานข้อมูล
                    fetch('check_phone.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'phone=' + encodeURIComponent(phone)
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.trim() === 'exists') {
                            phoneMsg.innerText = "เบอร์โทรศัพท์นี้ถูกลงทะเบียนแล้ว!";
                            phoneMsg.style.color = "#dc2626"; // สีแดง
                            // สามารถใช้คำสั่งปิดปุ่ม Submit ตรงนี้ได้ถ้าต้องการ
                        } else {
                            phoneMsg.innerText = "สามารถใช้เบอร์โทรศัพท์นี้ได้";
                            phoneMsg.style.color = "#16a34a"; // สีเขียว
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>