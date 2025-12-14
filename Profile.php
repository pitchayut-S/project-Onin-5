<?php
session_start();
require_once "db.php";

// 1. ตรวจสอบ Login
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); 
    exit(); 
}

// -----------------------------------------------------------
// 2. ส่วนจัดการข้อมูล (Add / Edit / Delete)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'save_user') {
        $id = isset($_POST['user_id']) && $_POST['user_id'] != '' ? intval($_POST['user_id']) : 0;
        $username = trim($_POST['username']);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']); 
        $phone = trim($_POST['phone']); // ใช้ชื่อ phone ตามฐานข้อมูล
        
        $role = $_POST['role'];
        $status = $_POST['status'];
        
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // เช็คยืนยันรหัสผ่าน
        if (!empty($password) && $password !== $confirm_password) {
            $_SESSION['msg_error'] = "รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน";
            header("Location: Profile.php"); exit();
        }

        // Security: ถ้าไม่ใช่ Admin บังคับ Role/Status เดิม
        if ($_SESSION['role'] !== 'admin') {
            $role = 'staff'; 
            $status = 'active'; 
        }

        if ($id == 0) {
            // INSERT
            if ($_SESSION['role'] !== 'admin') {
                $_SESSION['msg_error'] = "คุณไม่มีสิทธิ์เพิ่มผู้ใช้งานใหม่";
            } else {
                $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
                if ($check->num_rows > 0) {
                    $_SESSION['msg_error'] = "ชื่อผู้ใช้นี้มีอยู่ในระบบแล้ว";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    // ใช้ชื่อคอลัมน์ตาม onin_shop.sql: phone, email, status (ที่เพิ่งเพิ่ม)
                    $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssss", $username, $hashed_password, $fullname, $email, $phone, $role, $status);
                    
                    if ($stmt->execute()) {
                        $_SESSION['msg_success'] = "เพิ่มผู้ใช้งานเรียบร้อย";
                    } else {
                        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $conn->error;
                    }
                }
            }
        } else {
            // UPDATE
            // อนุญาตให้แก้ไขถ้าเป็น Admin หรือ แก้ไขข้อมูลตัวเอง
            if ($_SESSION['role'] !== 'admin' && $username !== $_SESSION['username']) {
                 $_SESSION['msg_error'] = "คุณไม่มีสิทธิ์แก้ไขข้อมูลผู้อื่น";
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=?, status=?, password=? WHERE id=?");
                    $stmt->bind_param("ssssssi", $fullname, $email, $phone, $role, $status, $hashed_password, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, phone=?, role=?, status=? WHERE id=?");
                    $stmt->bind_param("sssssi", $fullname, $email, $phone, $role, $status, $id);
                }

                if ($stmt->execute()) {
                    $_SESSION['msg_success'] = "แก้ไขข้อมูลเรียบร้อย";
                    // ถ้าแก้ไขตัวเอง ให้อัปเดต Session ด้วย
                    if ($id == $_SESSION['user_id']) {
                        $_SESSION['fullname'] = $fullname;
                    }
                } else {
                    $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการแก้ไข";
                }
            }
        }
    }

    // Delete Logic
    if ($_POST['action'] == 'delete_user') {
        if ($_SESSION['role'] !== 'admin') {
             $_SESSION['msg_error'] = "คุณไม่มีสิทธิ์ลบผู้ใช้งาน";
        } else {
            $del_id = intval($_POST['del_id']);
            $check_self = $conn->query("SELECT username FROM users WHERE id = $del_id");
            $target_user = $check_self->fetch_assoc();

            if ($target_user && $target_user['username'] == $_SESSION['username']) {
                $_SESSION['msg_error'] = "ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้";
            } else {
                $conn->query("DELETE FROM users WHERE id = $del_id");
                $_SESSION['msg_success'] = "ลบผู้ใช้งานเรียบร้อย";
            }
        }
    }

    header("Location: Profile.php");
    exit();
}

// -----------------------------------------------------------
// 3. แสดงผล
// -----------------------------------------------------------
$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$sql = "SELECT * FROM users WHERE 1";

if ($_SESSION['role'] !== 'admin') {
    $my_username = $conn->real_escape_string($_SESSION['username']);
    $sql .= " AND username = '$my_username' ";
}

if ($search_text !== "") {
    $like = "%" . $conn->real_escape_string($search_text) . "%";
    $sql .= " AND (username LIKE '$like' OR fullname LIKE '$like')";
}
$sql .= " ORDER BY id ASC";
$query_users = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการผู้ใช้งาน</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="style.css">

<style>
/* CSS styles */
.content-container { padding: 30px; }
.page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }
.btn-add-new { background: #27ae60; color: white; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
.btn-add-new:hover { background: #219150; }
.search-box { background:#fff; padding:18px 20px; border-radius:14px; display:flex; gap:10px; align-items:center; margin-bottom:20px; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
.search-box input { flex:1; border:none; background:#eef2f6; padding:12px 14px; border-radius:10px; }
.btn-search { background:#356CB5; padding:10px 18px; border-radius:10px; color:white; border:none; cursor: pointer; }
table { width:100%; border-collapse:separate; border-spacing:0; background:white; border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
th, td { padding:14px 12px; border-bottom:1px solid #eee; text-align: left; }
th { background:#f3f6fb; font-weight:600; }
.badge { padding:6px 12px; border-radius:20px; font-size:12px; font-weight:600; }
.status-active { background:#e6f6ed; color:#1b9c5a; }
.status-inactive { background:#fdecea; color:#c0392b; }
.role-admin { background:#e8f6f3; color:#16a085; border: 1px solid #16a085; }
.role-staff { background:#f4f6f7; color:#7f8c8d; border: 1px solid #bdc3c7; }
.action-btn { padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; margin-right: 5px; }
.btn-edit { background: #f39c12; color: white; }
.btn-delete { background: #e74c3c; color: white; }
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }

/* Modal Layout 2 Columns */
.modal-content { 
    background-color: #fff; margin: 2% auto; padding: 30px; border-radius: 16px; 
    width: 90%; max-width: 800px; 
    box-shadow: 0 10px 30px rgba(0,0,0,0.2); position: relative; font-family: 'Prompt', sans-serif;
}
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; font-size: 14px; }
.form-control { width: 100%; padding: 12px 15px; border: 1px solid #ccc; border-radius: 8px; font-size: 16px; background: #fff; color: #333; box-sizing: border-box; }
.form-control:focus { outline: none; border-color: #333; }
.btn-submit { width: 100%; background: #356CB5; color: white; padding: 15px; border: none; border-radius: 8px; font-size: 16px; font-weight: 500; cursor: pointer; margin-top: 20px; }
.btn-submit:hover { background: #285291; }
.close { position: absolute; right: 20px; top: 20px; font-size: 24px; cursor: pointer; color: #aaa; }
.close:hover { color: #000; }

@media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
</style>
</head>

<body>
<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <div class="page-title">จัดการผู้ใช้งาน (Profile)</div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
            <button onclick="openUserModal()" class="btn-add-new"><i class="fa-solid fa-user-plus"></i> เพิ่มผู้ใช้ใหม่</button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['msg_success'])) { echo "<script>Swal.fire({icon:'success', title:'สำเร็จ', text:'".$_SESSION['msg_success']."', timer:1500, showConfirmButton:false});</script>"; unset($_SESSION['msg_success']); } ?>
        <?php if (isset($_SESSION['msg_error'])) { echo "<script>Swal.fire({icon:'error', title:'ผิดพลาด', text:'".$_SESSION['msg_error']."'});</script>"; unset($_SESSION['msg_error']); } ?>

        <form class="search-box" method="get">
            <input type="text" name="search" placeholder="ค้นหา..." value="<?= htmlspecialchars($search_text) ?>">
            <button type="submit" class="btn-search">ค้นหา</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Username</th>
                    <th>ชื่อ-นามสกุล</th>
                    <th>Email</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th>ตำแหน่ง</th>
                    <th>สถานะ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $query_users->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight:600"><?= htmlspecialchars($u['username']) ?></td>
                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= isset($u['email']) ? htmlspecialchars($u['email']) : '-' ?></td>
                    <td><?= isset($u['phone']) ? htmlspecialchars($u['phone']) : '-' ?></td>
                    <td>
                        <?php 
                            $role_display = ($u['role'] == 'admin') ? 'เจ้าของร้าน (Admin)' : 'พนักงาน (Staff)';
                            $role_class = ($u['role'] == 'admin') ? 'role-admin' : 'role-staff';
                        ?>
                        <span class="badge <?= $role_class ?>"><?= $role_display ?></span>
                    </td>
                    <td>
                        <?php 
                           // ถ้ายังไม่มี status ใน DB ให้กันไว้ก่อน
                           $st = isset($u['status']) ? $u['status'] : 'active';
                           $st_display = ($st == 'active') ? 'เปิดใช้งาน' : 'ระงับ';
                           $st_class = ($st == 'active') ? 'status-active' : 'status-inactive';
                        ?>
                        <span class="badge <?= $st_class ?>"><?= $st_display ?></span>
                    </td>
                    <td>
                        <?php if ($_SESSION['role'] === 'admin' || $_SESSION['username'] == $u['username']): ?>
                        <button type="button" class="action-btn btn-edit" onclick='openUserModal(<?= json_encode($u) ?>)'><i class="fa-solid fa-pen"></i></button>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                        <button type="button" class="action-btn btn-delete" onclick="deleteUser(<?= $u['id'] ?>, '<?= $u['username'] ?>')"><i class="fa-solid fa-trash"></i></button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUserModal()">&times;</span>
        <h3 id="modal_title" style="margin-top:0; margin-bottom:20px;">เพิ่มผู้ใช้ใหม่</h3>
        
        <form method="post" action="Profile.php">
            <input type="hidden" name="action" value="save_user">
            <input type="hidden" id="user_id" name="user_id" value="">

            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label>ชื่อผู้ใช้งาน</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>ชื่อ-นามสกุล</label>
                        <input type="text" id="fullname" name="fullname" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>อีเมล</label>
                        <input type="email" id="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>สถานะบัญชี</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active">เปิดใช้งาน</option>
                            <option value="inactive">ระงับการใช้งาน</option>
                        </select>
                    </div>
                </div>

                <div>
                    <div class="form-group">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="text" id="phone" name="phone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>รหัสผ่านใหม่</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="เปลี่ยนรหัสผ่าน">
                    </div>
                    <div class="form-group">
                        <label>ยืนยันรหัสผ่าน</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="ยืนยันรหัสผ่าน">
                    </div>
                    <div class="form-group">
                        <label>ตำแหน่ง</label>
                        <select name="role" id="role" class="form-control">
                            <option value="staff">พนักงาน</option>
                            <option value="admin">เจ้าของร้าน</option>
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

<form id="deleteForm" method="post" style="display:none;"><input type="hidden" name="action" value="delete_user"><input type="hidden" name="del_id" id="del_id_input"></form>

<script>
const currentUserRole = '<?= isset($_SESSION['role']) ? $_SESSION['role'] : '' ?>';

function openUserModal(user = null) {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('modal_title');
    
    // Reset Form
    document.getElementById('user_id').value = '';
    document.getElementById('username').value = '';
    document.getElementById('fullname').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = ''; 
    document.getElementById('password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('role').value = 'staff';
    document.getElementById('status').value = 'active';

    const roleSelect = document.getElementById('role');
    const statusSelect = document.getElementById('status');

    // ถ้าไม่ใช่ Admin ให้ปิดการแก้ไข Role/Status
    if (currentUserRole !== 'admin') {
        roleSelect.style.pointerEvents = 'none';
        roleSelect.style.backgroundColor = '#f2f2f2';
        statusSelect.style.pointerEvents = 'none';
        statusSelect.style.backgroundColor = '#f2f2f2';
    } else {
        roleSelect.style.pointerEvents = 'auto';
        roleSelect.style.backgroundColor = '#fff';
        statusSelect.style.pointerEvents = 'auto';
        statusSelect.style.backgroundColor = '#fff';
    }

    if (user) {
        title.innerText = 'แก้ไขข้อมูลผู้ใช้';
        document.getElementById('user_id').value = user.id;
        document.getElementById('username').value = user.username;
        document.getElementById('username').readOnly = true; 
        document.getElementById('username').style.backgroundColor = '#f2f2f2';
        
        document.getElementById('fullname').value = user.fullname;
        document.getElementById('email').value = user.email || ''; 
        document.getElementById('phone').value = user.phone || ''; 
        document.getElementById('role').value = user.role;
        // ถ้าฐานข้อมูลยังไม่มี status ให้ใช้ค่า default active
        document.getElementById('status').value = user.status || 'active';
    } else {
        title.innerText = 'เพิ่มผู้ใช้ใหม่';
        document.getElementById('username').readOnly = false;
        document.getElementById('username').style.backgroundColor = '#fff';
    }
    modal.style.display = "block";
}

function closeUserModal() { document.getElementById('userModal').style.display = "none"; }
function deleteUser(id, name) {
    Swal.fire({ title:'ยืนยันลบ?', text:"ลบผู้ใช้ "+name+" ?", icon:'warning', showCancelButton:true, confirmButtonColor:'#d33', confirmButtonText:'ลบเลย' }).then((r)=>{ if(r.isConfirmed){ document.getElementById('del_id_input').value=id; document.getElementById('deleteForm').submit(); } });
}
window.onclick = function(e) { if(e.target == document.getElementById('userModal')) closeUserModal(); }
</script>
</body>
</html>