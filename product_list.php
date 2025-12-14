<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
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

   // SQL Insert แบบ Secure
    $sql = "INSERT INTO products (product_code, name, category, unit, cost_price, selling_price, mfg_date, expiry_date, quantity, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    // "ssssddssis" คือชนิดตัวแปร: s=string, d=decimal(ทศนิยม), i=integer(จำนวนเต็ม)
    mysqli_stmt_bind_param($stmt, "ssssddssis", $code, $name, $category, $unit, $cost, $price, $mfg, $exp, $qty, $image_name);

    if (mysqli_stmt_execute($stmt)) {
        $save_success = true;
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
    mysqli_stmt_close($stmt);
}

// --- ส่วน PHP ลบข้อมูล (คงเดิม) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM products WHERE id = $id");
    echo "<script>window.location='product_list.php';</script>";
}

if (isset($_GET['get_edit_id'])) {
    $id = $_GET['get_edit_id'];
    $sql = "SELECT * FROM products WHERE id = $id";
    $result = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($result);
    echo json_encode($data); // ส่งค่ากลับเป็น JSON
    exit(); // จบการทำงานทันที (ไม่ให้โหลดหน้าเว็บต่อ)
}

// --- ส่วนที่ 2: บันทึกการแก้ไขข้อมูล (Update) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_product') {
    $id = $_POST['edit_id']; // รับ ID ที่จะแก้ไข
    $code = $_POST['product_code'];
    $name = $_POST['name'];
    $category = $_POST['category'];
    $unit = $_POST['unit'];
    $cost = $_POST['cost_price'];
    $price = $_POST['selling_price'];
    $mfg = $_POST['mfg_date'];
    $exp = $_POST['expiry_date'];
    $qty = $_POST['quantity'];
    $old_image = $_POST['old_image']; // ชื่อรูปเดิม

    // จัดการรูปภาพ (ถ้ามีการอัปโหลดใหม่)
    $image_name = $old_image; // ค่าเริ่มต้นคือรูปเดิม
    if (isset($_FILES['product_image']['name']) && $_FILES['product_image']['name'] != "") {
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $image_name = "prod_" . time() . "." . $ext;
        move_uploaded_file($_FILES['product_image']['tmp_name'], "uploads/" . $image_name);
        // (Optional: ลบรูปเก่าทิ้งก็ได้ถ้าต้องการประหยัดพื้นที่)
    }

    // SQL Update แบบ Secure
    $sql = "UPDATE products SET 
            product_code=?, name=?, category=?, unit=?, cost_price=?, selling_price=?, 
            mfg_date=?, expiry_date=?, quantity=?, image=? 
            WHERE id=?";

    $stmt = mysqli_prepare($conn, $sql);
    // สังเกตว่ามี "i" (id) เพิ่มมาตอนท้าย
    mysqli_stmt_bind_param($stmt, "ssssddssisi", $code, $name, $category, $unit, $cost, $price, $mfg, $exp, $qty, $image_name, $id);

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location='product_list.php';</script>";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สินค้า - Onin Shop Stock</title>
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

        /* กล่องค้นหาโค้งมน */
        .search-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            border: none;
            background: #eef2f6;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
        }

        /* ปุ่มค้นหา / reset โค้งมน */
        .btn-search { 
            background:#356CB5; 
            color:white; 
            padding:10px 18px; 
            border:none; 
            border-radius:10px; 
            font-weight:600; 
            cursor:pointer;
        }

        .btn-reset { 
            background:#e7ebf0; 
            padding:10px 16px; 
            border-radius:10px; 
            text-decoration:none; 
            color:#333; 
        }

        /* ตารางโค้งมน */
        table { 
            width: 100%; 
            border-collapse: separate;
            border-spacing: 0;
            background:#fff; 
            border-radius: 14px;
            overflow: hidden;
            min-width:900px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        th, td { 
            padding:14px 12px; 
            border-bottom:1px solid #eee; 
        }

        th { 
            background:#f3f6fb; 
            font-weight:600; 
        }

        tr:last-child td { border-bottom: none; }

        /* รูปภาพโค้งมน */
        .product-img { 
            width: 65px; 
            height: 65px; 
            object-fit: cover; 
            border-radius:12px; 
            border:1px solid #ddd; 
        }

        /* Badge โค้งมน */
        .badge {
            padding:6px 12px;
            font-size:12px;
            font-weight:600;
            border-radius:20px;
        }
        .stock-ok { background:#e6f6ed; color:#1b9c5a; }
        .stock-low { background:#fdecea; color:#c0392b; }

        /* ปุ่มจัดการโค้งมน */
        .btn-edit { 
            background:#f1c40f; 
            padding:7px 12px; 
            color:white; 
            border-radius:8px; 
            text-decoration:none; 
        }
        .btn-delete { 
            background:#e74c3c; 
            padding:7px 12px; 
            color:white; 
            border-radius:8px; 
            text-decoration:none; 
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
            <li><a href="stock_in.php" ><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="stock_out.php" ><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            <li><a href="stock_adjust.php" ><i class="fa-solid fa-clipboard-check"></i> ตรวจนับ/ปรับปรุง</a></li>
            <li><a href="#"><i class="fa-solid fa-heart"></i> <span class="menu-text">สินค้ายอดนิยม</span></a></li>
            <li><a href="report_low_stock.php"><i class="fa-solid fa-triangle-exclamation"></i> <span class="menu-text">รายงานสินค้าใกล้หมด</span></a></li>
            <li><a href="stock_history.php"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติสต็อก</a></li>
        </ul>

        <div class="sidebar-footer menu-list">
            <li><a href="#"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">การจัดการบัญชี</span></a></li>
            <li><a href="index.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i> <span class="menu-text">ออกจากระบบ</span></a></li>
        </div>
    </nav>

<div class="main-content">

    <div class="top-navbar">
        <div class="nav-left"><i class="fa-solid fa-bars"></i></div>
        <div class="nav-right"><img src="img/profile.png"></div>
    </div>

        <div class="content-container">
            <h2 class="page-title">ข้อมูลสินค้า</h2>

           <form method="GET" class="search-box">
                <input type="text" 
                    name="search" 
                    class="search-input" 
                    placeholder="ค้นหาสินค้า..." 
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <button type="submit" class="btn-search">ค้นหา</button>
            </form>

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
                        // รับค่าค้นหา
                        $search = isset($_GET['search']) ? $_GET['search'] : '';
                        
                        // เตรียมตัวแปรผลลัพธ์
                        $result = null;

                        // ตรวจสอบว่ามีการค้นหาหรือไม่
                        if (!empty($search)) {
                            // กรณีมีคำค้นหา: ใช้ Prepared Statement เพื่อความปลอดภัย
                            $sql = "SELECT * FROM products WHERE name LIKE ? OR product_code LIKE ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            $search_param = "%" . $search . "%";
                            mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                        } else {
                            // กรณีไม่มีคำค้นหา: ดึงข้อมูลทั้งหมด
                            $sql = "SELECT * FROM products";
                            $result = mysqli_query($conn, $sql);
                        }

                        // แสดงผลลัพธ์
                        if ($result && mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                // แปลงวันที่
                                $date_th = "-";
                                if (!empty($row['expiry_date'])) {
                                    $date_th = date("d/m/", strtotime($row['expiry_date'])) . (date("Y", strtotime($row['expiry_date'])) + 543);
                                }
                                
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['product_code']) . "</td>";
                                
                                // แสดงรูปภาพ
                                if ($row['image'] && file_exists("uploads/" . $row['image'])) {
                                    echo "<td><img src='uploads/" . htmlspecialchars($row['image']) . "' width='40' height='40' style='object-fit:contain;'></td>";
                                } else {
                                    echo "<td><div style='width:40px; height:40px; background:#ddd; border-radius:4px;'></div></td>";
                                }
                                
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                
                                // เช็คจำนวนคงเหลือ (สีแดงถ้าหมด)
                                $qty_style = ($row['quantity'] == 0) ? 'color:red; font-weight:bold;' : '';
                                echo "<td style='$qty_style'>" . number_format($row['quantity']) . " " . htmlspecialchars($row['unit']) . "</td>";
                                
                                echo "<td>" . $date_th . "</td>";
                                echo "<td>
                                        <button type='button' class='btn-action btn-edit' onclick=\"openEditModal(" . $row['id'] . ")\">
                                            <i class='fa-solid fa-pen-to-square'></i> Edit
                                        </button>
                                        <button type='button' class='btn-action btn-delete' onclick=\"openDeleteModal(" . $row['id'] . ")\">
                                            <i class='fa-solid fa-trash'></i> Delete
                                        </button>
                                    </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center;'>ไม่พบข้อมูลสินค้า</td></tr>";
                        }
                        
                        // ปิด Statement ถ้ามีการใช้งาน (เพื่อคืนทรัพยากรระบบ)
                        if (isset($stmt) && $stmt) {
                            mysqli_stmt_close($stmt);
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

<div id="editProductModal" class="modal-overlay">
        <div class="login-box modal-lg">
            <div class="header-text" style="text-align: left; margin-bottom: 20px;">
                <h1>แก้ไขข้อมูลสินค้า</h1>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" id="edit_id" name="edit_id"> <input type="hidden" id="edit_old_image" name="old_image"> <div class="add-product-layout">
                    <div class="form-section">
                        <div class="input-row">
                            <div class="form-group">
                                <label>รหัสสินค้า</label>
                                <input type="text" id="edit_product_code" name="product_code" required>
                            </div>
                            <div class="form-group">
                                <label>ต้นทุน</label>
                                <input type="number" step="0.1" id="edit_cost_price" name="cost_price" required>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>ชื่อสินค้า</label>
                                <input type="text" id="edit_name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label>ราคาขาย</label>
                                <input type="number" step="0.1" id="edit_selling_price" name="selling_price" required>
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>ประเภทสินค้า</label>
                                <select id="edit_category" name="category" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px;">
                                    <option value="ขนม">ขนม</option>
                                    <option value="เครื่องดื่ม">เครื่องดื่ม</option>
                                    <option value="ของใช้">ของใช้</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>วันผลิต</label>
                                <input type="date" id="edit_mfg_date" name="mfg_date">
                            </div>
                        </div>

                        <div class="input-row">
                            <div class="form-group">
                                <label>หน่วย</label>
                                <select id="edit_unit" name="unit" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px;">
                                    <option value="ชิ้น">ชิ้น</option>
                                    <option value="ห่อ">ห่อ</option>
                                    <option value="ขวด">ขวด</option>
                                    <option value="กล่อง">กล่อง</option>
                                    <option value="ซอง">ซอง</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>วันหมดอายุ</label>
                                <input type="date" id="edit_expiry_date" name="expiry_date">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>จำนวน</label>
                            <input type="number" id="edit_quantity" name="quantity" required>
                        </div>
                    </div>

                    <div class="image-upload-section">
                        <div class="image-preview" id="edit_imgPreview">
                            </div>
                        <label for="edit_product_image" class="btn-upload">อัพโหลดรูปภาพใหม่</label>
                        <input type="file" id="edit_product_image" name="product_image" accept="image/*" onchange="previewEditImage(event)" style="display:none;">
                        <p style="font-size:12px; color:#666; margin-top:5px;">(ไม่ต้องเลือกหากไม่ต้องการเปลี่ยน)</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
    <div id="deleteModal" class="modal-overlay">
        <div class="login-box logout-modal-content"> <i class="fa-solid fa-trash-can logout-icon"></i>
            <h2 class="logout-title">ยืนยันการลบข้อมูล</h2>
            <p class="logout-desc">คุณแน่ใจหรือไม่ว่าต้องการลบสินค้าชิ้นนี้?<br>การกระทำนี้ไม่สามารถย้อนกลับได้</p>
            
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button class="btn-cancel" onclick="closeDeleteModal()">ยกเลิก</button>
                <a href="#" id="btn-confirm-delete" class="btn-confirm-logout">ลบข้อมูล</a>
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

        function openEditModal(id) {
            // เรียก PHP ผ่าน AJAX เพื่อขอข้อมูลสินค้า ID นี้
            fetch('product_list.php?get_edit_id=' + id)
                .then(response => response.json())
                .then(data => {
                    // เอาข้อมูลที่ได้มาใส่ลงใน Input แต่ละช่อง
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_product_code').value = data.product_code;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_cost_price').value = data.cost_price;
                    document.getElementById('edit_selling_price').value = data.selling_price;
                    document.getElementById('edit_quantity').value = data.quantity;
                    document.getElementById('edit_mfg_date').value = data.mfg_date;
                    document.getElementById('edit_expiry_date').value = data.expiry_date;
                    document.getElementById('edit_category').value = data.category;
                    document.getElementById('edit_unit').value = data.unit;
                    document.getElementById('edit_old_image').value = data.image; // เก็บชื่อรูปเดิมไว้

                    // แสดงรูปภาพตัวอย่าง
                    var imgDiv = document.getElementById('edit_imgPreview');
                    if (data.image) {
                        imgDiv.innerHTML = '<img src="uploads/' + data.image + '" style="width:100%; height:100%; object-fit:contain;">';
                    } else {
                        imgDiv.innerHTML = '<span style="color:#aaa;">ไม่มีรูปภาพ</span>';
                    }

                    // เปิด Modal
                    document.getElementById('editProductModal').style.display = 'flex';
                })
                .catch(error => console.error('Error:', error));
        }

        function closeEditModal() {
            document.getElementById('editProductModal').style.display = 'none';
        }

        // ฟังก์ชันพรีวิวรูปภาพในหน้าแก้ไข (เหมือนของหน้าเพิ่ม)
        function previewEditImage(event) {
            var reader = new FileReader();
            reader.onload = function(){
                var output = document.getElementById('edit_imgPreview');
                output.innerHTML = '<img src="' + reader.result + '" style="width:100%; height:100%; object-fit:contain;">';
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        function openDeleteModal(id) {
            // สร้างลิงก์สำหรับลบ โดยเอา ID ที่ส่งมาต่อท้าย URL
            var deleteUrl = 'product_list.php?delete_id=' + id;
            
            // เอาลิงก์ไปใส่ในปุ่ม "ลบข้อมูล"
            document.getElementById('btn-confirm-delete').href = deleteUrl;
            
            // เปิด Modal
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // เช็ค PHP ว่าบันทึกสำเร็จไหม ถ้าใช่ให้เปิด Success Modal
        <?php if ($save_success): ?>
            document.getElementById('successModal').style.display = 'flex';
        <?php endif; ?>
    </script>

</body>
</html>
