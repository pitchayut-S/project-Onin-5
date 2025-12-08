<?php
session_start();
require_once "db.php";

// เช็ค Login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ตัวแปรสำหรับเช็คว่าบันทึกสำเร็จไหม (เพื่อเปิด Modal Success)
$save_success = false;

// --- ส่วน PHP บันทึกข้อมูล ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_product') {
    
    // รับค่าจากฟอร์ม
    $code = $_POST['product_code'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $cost = $_POST['cost_price'];
    $price = $_POST['selling_price'];
    $mfg = $_POST['mfg_date'];
    $exp = $_POST['expiry_date'];
    $qty = $_POST['quantity'];

    // จัดการรูปภาพ
    $image_name = "";
    if (isset($_FILES['product_image']['name']) && $_FILES['product_image']['name'] != "") {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $image_name = "prod_" . time() . "." . $ext; // ตั้งชื่อไฟล์ใหม่กันซ้ำ
        move_uploaded_file($_FILES['product_image']['tmp_name'], "uploads/" . $image_name);
    }

    // สร้างโฟลเดอร์ uploads ถ้ายังไม่มี
    if (!file_exists('uploads')) { mkdir('uploads', 0777, true); }

    // SQL Insert
    $sql = "INSERT INTO products (product_code, name, category, unit, cost_price, selling_price, mfg_date, expiry_date, quantity, image) 
            VALUES ('$code', '$name', '$category', '$unit', '$cost', '$price', '$mfg', '$exp', '$qty', '$image_name')";

    if (mysqli_query($conn, $sql)) {
        $save_success = true; // ตั้งค่าเป็น true เพื่อให้ JS ทำงาน
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}

// --- ส่วน PHP ลบข้อมูล (คงเดิม) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    echo "<script>window.location='product_list.php';</script>";
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลสินค้า - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* --- ใช้ CSS ชุดเดิมจาก Dashboard --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #E5E5E5; }
        
        /* Sidebar */
        .sidebar { width: 250px; background-color: #356CB5; color: white; display: flex; flex-direction: column; position: fixed; height: 100%; left: 0; top: 0; z-index: 100; }
        .sidebar-header { padding: 20px; font-size: 20px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu-list { list-style: none; flex-grow: 1; padding-top: 10px; }
        .menu-list li a { display: flex; align-items: center; padding: 15px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 16px; transition: 0.3s; }
        .menu-list li a:hover, .menu-list li a.active { background-color: rgba(255,255,255,0.2); color: white; border-left: 4px solid white; }
        .menu-list li a i { width: 30px; font-size: 18px; }
        .sidebar-footer { margin-top: auto; width: 100%; flex-grow: 0 !important;}
        .sidebar-footer li { list-style: none; }
        .sidebar-footer li a { display: flex; align-items: center; padding: 15px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 16px; transition: 0.3s; }
        .sidebar-footer li a:hover { background-color: rgba(255,255,255,0.2); color: white; }
        .btn-logout { background-color: #D90429; color: white !important; }
        .btn-logout:hover { background-color: #b0021f; }

        /* Main Content */
        .main-content { margin-left: 250px; width: calc(100% - 250px); display: flex; flex-direction: column; }
        .top-navbar { height: 60px; background-color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .nav-left i { font-size: 24px; cursor: pointer; color: #333; }
        .nav-right img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; }
        .content-container { padding: 30px; }
        .page-title { font-size: 28px; font-weight: bold; margin-bottom: 20px; color: #333; }

        /* --- ส่วนที่เพิ่มใหม่สำหรับหน้านี้ --- */
        
        /* ช่องค้นหา */
        .search-box {
            background-color: white;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            border-radius: 5px;
        }
        .search-input {
            flex-grow: 1; /* ให้ยาวเต็มพื้นที่ */
            padding: 10px 15px;
            border: none;
            background-color: #E0E0E0; /* สีเทาตามรูป */
            border-radius: 5px;
            font-size: 14px;
            outline: none;
        }
        .btn-search {
            background-color: #007bff; /* สีฟ้า */
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-search:hover { background-color: #0069d9; }

        /* ตาราง */
        .table-container {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto; /* เผื่อตารางล้นจอ */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* ความกว้างขั้นต่ำ */
        }
        th, td {
            text-align: left;
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }
        th {
            background-color: #E0E0E0; /* หัวตารางสีเทา */
            font-weight: 600;
            color: #333;
        }
        
        /* รูปภาพสินค้าจำลอง */
        .product-img-placeholder {
            width: 40px;
            height: 40px;
            background-color: #ddd;
            display: inline-block;
        }

        /* ปุ่มจัดการ (Edit / Delete) */
        .btn-action {
            padding: 5px 10px;
            border-radius: 15px;
            color: white;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-edit { background-color: #007bff; }
        .btn-edit:hover { background-color: #0069d9; }
        .btn-delete { background-color: #dc3545; }
        .btn-delete:hover { background-color: #c82333; }

        /* ปุ่มเพิ่มข้อมูล (ลอยขวาล่าง) */
        .btn-add-container {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        .btn-add {
            background-color: #1FC938; /* สีเขียว */
            color: white;
            padding: 10px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-add:hover { background-color: #1aa830; }

       /* Responsive */
        @media (max-width: 1024px) {
            .card-grid { grid-template-columns: repeat(2, 1fr); } /* จอเล็กลง เหลือ 2 คอลัมน์ */
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header, .menu-text { display: none; } /* ย่อเมนู */
            .menu-list li a { justify-content: center; padding: 15px 0; }
            .menu-list li a i { width: auto; font-size: 24px; }
            .main-content { margin-left: 70px; width: calc(100% - 70px); }
            .card-grid { grid-template-columns: 1fr; } /* มือถือ เหลือ 1 คอลัมน์ */
        }
    </style>
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
            <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Dashboard</span></a></li>
            <li><a href="product_list.php" class="active"><i class="fa-solid fa-box-open"></i> <span class="menu-text">ข้อมูลสินค้า</span></a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">ข้อมูลประเภทสินค้า</span></a></li>
            <li><a href="#"><i class="fa-solid fa-cart-shopping"></i> <span class="menu-text">สต๊อกสินค้า</span></a></li>
            <li><a href="#"><i class="fa-solid fa-heart"></i> <span class="menu-text">สินค้ายอดนิยม</span></a></li>
            <li><a href="#"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">รายงาน</span></a></li>
        </ul>

        <div class="sidebar-footer menu-list">
            <li><a href="#"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">การจัดการบัญชี</span></a></li>
            <li><a href="index.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i> <span class="menu-text">ออกจากระบบ</span></a></li>
        </div>
    </nav>

    <div class="main-content">
        <div class="top-navbar">
            <div class="nav-left">
                <i class="fa-solid fa-bars"></i>
            </div>
            <div class="nav-right">
                <?php $user_display = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-weight:500; color:#333;"><?php echo $user_display; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_display); ?>&background=0D8ABC&color=fff" alt="User Profile">
                </div>
            </div>
        </div>

        <div class="content-container">
            <h2 class="page-title">ข้อมูลสินค้า</h2>

            <div class="search-box">
                <input type="text" class="search-input" placeholder="ค้นหาสินค้า...">
                <button class="btn-search">ค้นหา</button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>รูปภาพ</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภทสินค้า</th>
                            <th>จำนวนสินค้าคงเหลือ</th>
                            <th>วันหมดอายุ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search_q = isset($_GET['search']) ? $_GET['search'] : '';
                        $sql = "SELECT * FROM products WHERE name LIKE '%$search_q%' OR product_code LIKE '%$search_q%'";
                        $result = mysqli_query($conn, $sql);

                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $date_th = date("d/m/", strtotime($row['expiry_date'])) . (date("Y", strtotime($row['expiry_date'])) + 543);
                                echo "<tr>";
                                echo "<td>" . $row['product_code'] . "</td>";
                                echo "<td>" . ($row['image'] ? "<img src='uploads/" . $row['image'] . "' width='40' height='40' style='object-fit:contain;'>" : "<div style='width:40px; height:40px; background:#ddd;'></div>") . "</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td style='" . ($row['quantity'] == 0 ? 'color:red;' : '') . "'>" . $row['quantity'] . "</td>";
                                echo "<td>" . $date_th . "</td>";
                                echo "<td>
                                        <button class='btn-action btn-edit'><i class='fa-solid fa-pen-to-square'></i> Edit</button>
                                        <a href='product_list.php?delete_id=" . $row['id'] . "' class='btn-action btn-delete' onclick=\"return confirm('ลบข้อมูล?');\"><i class='fa-solid fa-trash'></i> Delete</a>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;'>ไม่พบข้อมูล</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="btn-add-container">
                <button onclick="openAddModal()" class="btn-add"><i class="fa-solid fa-plus"></i> เพิ่มข้อมูล</button>
            </div>
        </div>
    </div>

    <div id="addProductModal" class="modal-overlay">
        <div class="login-box modal-lg"> <div class="header-text" style="text-align: left; margin-bottom: 20px;">
                <h1>เพิ่มข้อมูลสินค้า</h1>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">
                
                <div class="add-product-layout">
                    <div class="form-section">
                        <div class="input-row">
                            <div class="form-group">
                                <label>รหัสสินค้า</label>
                                <input type="text" name="product_code" required>
                            </div>
                            <div class="form-group">
                                <label>ต้นทุน</label>
                                <input type="number" step="0.01" name="cost_price" required>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>ชื่อสินค้า</label>
                                <input type="text" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>ราคาขาย</label>
                                <input type="number" step="0.01" name="selling_price" required>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>ประเภทสินค้า</label>
                                <select name="category" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px;">
                                    <option value="ขนม">ขนม</option>
                                    <option value="เครื่องดื่ม">เครื่องดื่ม</option>
                                    <option value="ของใช้">ของใช้</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>วันผลิต</label>
                                <input type="date" name="mfg_date">
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>หน่วย</label>
                                <select name="unit" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px;">
                                    <option value="ชิ้น">ชิ้น</option>
                                    <option value="ห่อ">ห่อ</option>
                                    <option value="ขวด">ขวด</option>
                                    <option value="กล่อง">กล่อง</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>วันหมดอายุ</label>
                                <input type="date" name="expiry_date">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>จำนวน</label>
                            <input type="number" name="quantity" required>
                        </div>
                    </div>

                    <div class="image-upload-section">
                        <div class="image-preview" id="imgPreview">
                            <span style="color:#aaa;">รูปภาพ</span>
                        </div>
                        <label for="product_image" class="btn-upload">อัพโหลดรูปภาพ</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" onchange="previewImage(event)">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="login-box success-modal-content">
            <i class="fa-solid fa-circle-check success-icon"></i>
            <h2 style="color:#333; margin-bottom:10px;">สำเร็จ!</h2>
            <p style="color:#666; margin-bottom:20px;">บันทึกข้อมูลสินค้าเรียบร้อยแล้ว</p>
            <button class="btn-save" onclick="closeSuccessModal()">ตกลง</button>
        </div>
    </div>
    <div id="logoutModal" class="modal-overlay">
    <div class="login-box logout-modal-content">
        <i class="fa-solid fa-right-from-bracket logout-icon"></i>
        <h2 class="logout-title">ยืนยันการออกจากระบบ</h2>
        <p class="logout-desc">คุณต้องการออกจากระบบใช่หรือไม่?</p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button class="btn-cancel" onclick="closeLogoutModal()">ยกเลิก</button>
            <a href="logout.php" class="btn-confirm-logout">ออกจากระบบ</a>
        </div>
    </div>
</div>

    <script>
        // ฟังก์ชันเปิด-ปิด Modal เพิ่มสินค้า
        function openAddModal() {
            document.getElementById('addProductModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addProductModal').style.display = 'none';
        }

        // ฟังก์ชันปิด Modal สำเร็จ และรีเฟรชหน้า
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            window.location.href = 'product_list.php'; // รีเฟรชเพื่อเคลียร์ค่า
        }

        // ฟังก์ชันแสดงรูปพรีวิวเมื่อเลือกไฟล์
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('imgPreview');
                output.innerHTML = '<img src="' + reader.result + '">';
            };
            reader.readAsDataURL(event.target.files[0]);
        }
        // ฟังก์ชันเปิด Popup Logout
        function confirmLogout() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        // ฟังก์ชันปิด Popup Logout
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // เช็ค PHP ว่าบันทึกสำเร็จไหม ถ้าใช่ให้เปิด Success Modal
        <?php if ($save_success): ?>
            document.getElementById('successModal').style.display = 'flex';
        <?php endif; ?>
    </script>

</body>
</html>