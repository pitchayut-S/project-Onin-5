<?php
session_start();
require_once "db.php";

// 1. ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ==========================================
// ตั้งค่าตัวเลือก "หน่วยนับ" ที่นี่
// ==========================================
$unit_options = ["ชิ้น", "กล่อง", "แพ็ค", "โหล", "ลัง", "คู่", "ชุด", "ขวด", "กระป๋อง", "ถุง", "ม้วน"];

// ------------------------------------------------------------------
// 2. ส่วนจัดการบันทึกข้อมูล (ADD & EDIT)
// ------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- กรณี: เพิ่มสินค้าใหม่ (ADD) ---
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
    // 1. รับค่าจากฟอร์ม (ไม่ต้องรับ product_code แล้ว เพราะเราจะสร้างเอง)
    $name          = trim($_POST['name']);
    $category      = trim($_POST['category']);
    $unit          = $_POST['unit'];
    $cost          = floatval($_POST['cost']);          // แปลงเป็นทศนิยม
    $selling_price = floatval($_POST['selling_price']); // แปลงเป็นทศนิยม
    $mfg_date      = $_POST['mfg_date'];
    $exp_date      = $_POST['exp_date']; 
    $quantity      = intval($_POST['quantity']);        // แปลงเป็นจำนวนเต็ม

    // ---------------------------------------------------------
    // 🚀 ส่วนสร้างรหัสสินค้าอัตโนมัติ (Auto Generate Code)
    // ---------------------------------------------------------
    
    // A. หา Prefix ของหมวดหมู่นี้ก่อน
    $prefix = "PD"; // ค่าเริ่มต้น (กรณีหาไม่เจอ)
    $stmt_cat = $conn->prepare("SELECT prefix FROM product_category WHERE category_name = ?");
    $stmt_cat->bind_param("s", $category);
    $stmt_cat->execute();
    $res_cat = $stmt_cat->get_result();
    if ($row_cat = $res_cat->fetch_assoc()) {
        $prefix = $row_cat['prefix']; // ได้ค่าเช่น 'SN', 'DR'
    }
    $stmt_cat->close();

    // B. หาเลขรันล่าสุด ของ Prefix นี้
    // ค้นหา product_code ที่ขึ้นต้นด้วย 'Prefix-' (เช่น 'SN-%')
    $search_prefix = $prefix . "-%";
    $stmt_last = $conn->prepare("SELECT product_code FROM products WHERE product_code LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt_last->bind_param("s", $search_prefix);
    $stmt_last->execute();
    $res_last = $stmt_last->get_result();

    $next_number = 1; // เริ่มต้นที่ 1
    if ($row_last = $res_last->fetch_assoc()) {
        // ถ้าเจอตัวล่าสุด เช่น "SN-0005"
        // ให้ตัด string เอาเฉพาะตัวเลขหลังขีด "-" มาบวก 1
        $parts = explode("-", $row_last['product_code']);
        if (isset($parts[1])) {
            $next_number = intval($parts[1]) + 1;
        }
    }
    $stmt_last->close();

    // C. สร้างรหัสใหม่ (เติม 0 ข้างหน้าให้ครบ 4 หลัก)
    // ผลลัพธ์จะเป็น: SN-0001, SN-0002 ...
    $new_product_code = $prefix . "-" . str_pad($next_number, 4, "0", STR_PAD_LEFT);

    // ---------------------------------------------------------

    // 2. อัปโหลดรูปภาพ
    $image_name = "";
    if (!empty($_FILES["image"]["name"])) {
        $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        // ตั้งชื่อไฟล์รูปใหม่เป็น รหัสสินค้า.นามสกุล (เช่น SN-0001.jpg) เพื่อความเป็นระเบียบ
        $image_name = $new_product_code . "_" . time() . "." . $ext;
        move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $image_name);
    }

    // 3. บันทึกลงฐานข้อมูล (ใช้ $new_product_code ที่สร้างขึ้น)
    $sql = "INSERT INTO products (product_code, name, category, unit, cost, selling_price, mfg_date, exp_date, quantity, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    // s=string, d=double(ทศนิยม), i=integer
    // ลำดับ: code(s), name(s), cat(s), unit(s), cost(d), price(d), mfg(s), exp(s), qty(i), img(s)
    $stmt->bind_param("ssssddssis", 
        $new_product_code, 
        $name, 
        $category, 
        $unit, 
        $cost, 
        $selling_price, 
        $mfg_date, 
        $exp_date, 
        $quantity, 
        $image_name
    );
    
    if ($stmt->execute()) {
        $_SESSION['msg_success'] = "เพิ่มสินค้าเรียบร้อย รหัสสินค้าคือ: " . $new_product_code;
    } else {
        $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: product_list.php");
    exit();
}

    // --- กรณี: แก้ไขสินค้า (EDIT) ---
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id            = intval($_POST['id']);
        $product_code  = $_POST['product_code'];
        $name          = $_POST['name'];
        $category      = $_POST['category'];
        $unit          = $_POST['unit']; 
        $quantity      = intval($_POST['quantity']); // แปลงเป็น int เพื่อความชัวร์
        $cost          = floatval($_POST['cost']);   // แปลงเป็น float เพื่อความชัวร์
        $exp_date      = $_POST['exp_date'];
        $selling_price = floatval($_POST['selling_price']); // แปลงเป็น float เพื่อความชัวร์
        $old_image     = $_POST['old_image']; 
        $image_name    = $old_image;

        // อัปโหลดรูปใหม่ (ถ้ามี)
        if (!empty($_FILES["image"]["name"])) {
            $ext = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $new_img_name = uniqid("img_") . "." . $ext;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $new_img_name)) {
                if (!empty($old_image) && file_exists("uploads/" . $old_image)) {
                    unlink("uploads/" . $old_image);
                }
                $image_name = $new_img_name;
            }
        }

        // อัปเดตข้อมูล
        $sql = "UPDATE products SET product_code=?, name=?, category=?, unit=?, quantity=?, selling_price=?, cost=?, exp_date=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        
        // แก้ไขบรรทัดนี้: Type definition string ต้องมี 10 ตัวอักษร ตรงกับจำนวนตัวแปร 10 ตัว
        // s=string, i=int, d=double/float
        $stmt->bind_param("ssssiddssi", 
            $product_code, 
            $name, 
            $category, 
            $unit, 
            $quantity, 
            $selling_price, 
            $cost, 
            $exp_date, 
            $image_name, 
            $id
        );

        if ($stmt->execute()) {
            $_SESSION['msg_success'] = "แก้ไขข้อมูลสำเร็จ";
        } else {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการแก้ไข: " . $stmt->error;
        }
        
        $stmt->close(); // อย่าลืมปิด statement
        header("Location: product_list.php");
        exit();
    }
}

// ------------------------------------------------------------------
// 3. เตรียมข้อมูลสำหรับแสดงผล
// ------------------------------------------------------------------

// ดึงหมวดหมู่สินค้า (เพิ่ม SELECT prefix)
$cate_query = $conn->query("SELECT id, category_name, prefix FROM product_category ORDER BY category_name ASC");
$categories = [];
while ($cat = $cate_query->fetch_assoc()) {
    $categories[] = $cat;
}

// ค้นหาและดึงสินค้า
$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN product_category c ON p.category = c.id";
if ($search_text !== "") {
    $like = "%".$conn->real_escape_string($search_text)."%";
    $sql .= " WHERE p.product_code LIKE '$like' OR p.name LIKE '$like' OR c.category_name LIKE '$like'";
}
$sql .= " ORDER BY p.id DESC";
$products = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สินค้า - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

        /* Search Box */
        .search-box {
            background: #fff; padding: 18px 20px; border-radius: 14px;
            display: flex; gap: 10px; align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .search-box input {
            flex: 1; border: none; background: #eef2f6;
            padding: 12px 14px; border-radius: 10px; font-size: 14px;
        }
        .btn-search { background:#356CB5; color:white; padding:10px 18px; border:none; border-radius:10px; font-weight:600; cursor:pointer; }
        .btn-reset { background:#e7ebf0; padding:10px 16px; border-radius:10px; text-decoration:none; color:#333; }

        /* Table */
        table { 
            width: 100%; border-collapse: separate; border-spacing: 0;
            background:#fff; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        th, td { padding:14px 12px; border-bottom:1px solid #eee; }
        th { background:#f3f6fb; font-weight:600; }
        
        .product-img { width: 65px; height: 65px; object-fit: cover; border-radius:12px; border:1px solid #ddd; }
        
        .badge { padding:6px 12px; font-size:12px; font-weight:600; border-radius:20px; }
        .stock-ok { background:#e6f6ed; color:#1b9c5a; }
        .stock-low { background:#fdecea; color:#c0392b; }

        .btn-edit { background:#f1c40f; padding:7px 12px; color:white; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px;}
        .btn-delete { background:#e74c3c; padding:7px 12px; color:white; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px;}

        /* --- Modal CSS --- */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; overflow: auto;
            background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px);
        }
        .modal-content {
            background-color: #fff; margin: 5% auto; padding: 25px;
            border: 1px solid #888; width: 90%; max-width: 600px;
            border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: slideDown 0.3s ease-out; font-family: 'Prompt', sans-serif;
            position: relative;
        }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .close { position: absolute; right: 20px; top: 15px; color: #aaa; font-size: 24px; font-weight: bold; cursor: pointer; }
        .close:hover { color: #000; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .full-width { grid-column: span 2; }
        
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color:#333; font-size:14px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
        
        .btn-submit { width: 100%; background: #28a745; color: white; padding: 12px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 15px; }
        .btn-submit:hover { background: #218838; }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">
        <div class="page-title">ข้อมูลสินค้า</div>

        <?php if (isset($_SESSION['msg_success'])): ?>
            <script>Swal.fire({icon: 'success', title: 'สำเร็จ', text: '<?= $_SESSION['msg_success'] ?>', timer: 1500, showConfirmButton: false});</script>
            <?php unset($_SESSION['msg_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['msg_error'])): ?>
            <script>Swal.fire({icon: 'error', title: 'ผิดพลาด', text: '<?= $_SESSION['msg_error'] ?>'});</script>
            <?php unset($_SESSION['msg_error']); ?>
        <?php endif; ?>

        <div style="text-align:right; margin-bottom:20px;">
            <button onclick="openAddModal()" style="background:#28a745;color:white;padding:10px 18px;border-radius:10px;border:none;font-weight:600;cursor:pointer;font-family:'Prompt';">
               <i class="fa-solid fa-plus"></i> เพิ่มสินค้า
            </button>
        </div>

        <form class="search-box" method="get" action="product_list.php">
            <input type="text" name="search" placeholder="ค้นหา (รหัส / ชื่อ / ประเภทสินค้า)" value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">
            <button type="submit" class="btn-search">ค้นหา</button>
            <?php if ($search_text !== ""): ?>
                <a href="product_list.php" class="btn-reset">ล้าง</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>รหัสสินค้า</th>
                        <th>รูปภาพ</th>
                        <th>ชื่อสินค้า</th>
                        <th>ประเภท</th>
                        <th>หน่วย</th> <th>คงเหลือ</th>
                        <th>ราคาทุน</th>
                        <th>ราคาขาย</th>
                        <th>วันหมดอายุ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products->num_rows > 0): ?>
                    <?php $i=1; while ($row = $products->fetch_assoc()): 
                        $stock_class = $row['quantity'] > 0 ? "stock-ok" : "stock-low";
                        $stock_label = $row['quantity'] > 0 ? "มีสต๊อก" : "หมด";
                    ?>
                    <tr>
                        <td style="text-align:center;"><?= $i++ ?></td>
                        <td><?= $row['product_code'] ?></td>
                        <td>
                            <?php if ($row['image']): ?>
                                <img src="uploads/<?= $row['image'] ?>" class="product-img">
                            <?php else: ?>
                                <span style="color:#888; font-size:12px;">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['name'] ?></td>
                        <td><?= $row['category_name'] ?></td>
                        <td><?= $row['unit'] ?></td> <td><span class="badge <?= $stock_class ?>"><?= $row['quantity'] ?> | <?= $stock_label ?></span></td>
                        <td><?= number_format($row['cost'], 2) ?> บาท</td>
                        <td><?= number_format($row['selling_price'], 2) ?> บาท</td>
                        <td><?= $row['exp_date'] ?></td>
                        <td>
                            <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                <i class="fa-solid fa-pen"></i> แก้ไข
                            </button>
                            <button type="button" class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                <i class="fa-solid fa-trash"></i> ลบ
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center; padding:20px;">ไม่มีข้อมูลสินค้า</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h3 style="margin-top:0;">เพิ่มสินค้าใหม่</h3>
        <hr style="border:0; border-top:1px solid #eee; margin-bottom:20px;">

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>ประเภทสินค้า <span style="color:red;">*</span></label>
                    <select name="category" id="add_category" class="form-control" onchange="genProductCode()" required>
                        <option value="" data-prefix="">-- เลือกประเภท --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-prefix="<?= isset($cat['prefix']) ? $cat['prefix'] : '' ?>">
                                <?= $cat['category_name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label>รหัสสินค้า <span style="color:red;">*</span></label>
                    <input type="text" name="product_code" id="add_product_code" class="form-control" placeholder="เลือกประเภทเพื่อรับรหัส..." required>
                </div> -->
                
                <div class="form-group" >
                    <label>ชื่อสินค้า <span style="color:red;">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>หน่วยนับ</label>
                    <select name="unit" class="form-control" required>
                        <option value="">-- เลือกหน่วยนับ --</option>
                        <?php foreach ($unit_options as $u): ?>
                            <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ราคาทุน</label>
                    <input type="number" step="0.01" name="cost" class="form-control">
                </div>
                <div class="form-group">
                    <label>ราคาขาย</label>
                    <input type="number" step="0.01" name="selling_price" class="form-control">
                </div>

                <div class="form-group">
                    <label>จำนวนเริ่มต้น</label>
                    <input type="number" name="quantity" class="form-control" value="0">
                </div>

                <div class="form-group">
                    <label>วันที่ผลิต</label>
                    <input type="date" name="mfg_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>วันหมดอายุ</label>
                    <input type="date" name="exp_date" class="form-control">
                </div>

                
                <div class="form-group full-width">
                    <label>รูปภาพสินค้า</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>

            <button type="submit" class="btn-submit">บันทึกสินค้า</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h3 style="margin-top:0; color:#f39c12;">แก้ไขสินค้า</h3>
        <hr style="border:0; border-top:1px solid #eee; margin-bottom:20px;">

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" id="edit_id" name="id">
            <input type="hidden" id="edit_old_image" name="old_image">

            <div class="form-grid">
                <div class="form-group">
                    <label>รหัสสินค้า</label>
                    <input type="text" id="edit_product_code" name="product_code" 
                        readonly 
                        style="background-color: #e9ecef; cursor: not-allowed; color: #6c757d;" 
                        required>
                    <small style="color:red; font-size:12px;">* รหัสสินค้าไม่สามารถแก้ไขได้</small>
                </div>
                <div class="form-group">
                    <label>ชื่อสินค้า</label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>ประเภทสินค้า</label>
                    <select id="edit_category" name="category" class="form-control" required>
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>หน่วยนับ</label>
                    <select id="edit_unit" name="unit" class="form-control" required>
                        <option value="">-- เลือกหน่วยนับ --</option>
                        <?php foreach ($unit_options as $u): ?>
                            <option value="<?= $u ?>"><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>ราคาทุน</label>
                    <input type="number" step="0.01" id="edit_cost" name="cost" class="form-control">
                </div>

                 <div class="form-group">
                    <label>ราคาขาย</label>
                    <input type="number" step="0.01" id="edit_selling_price" name="selling_price" class="form-control">
                </div>

                <div class="form-group">
                    <label>จำนวนคงเหลือ</label>
                    <input type="number" id="edit_quantity" name="quantity" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>วันหมดอายุ</label>
                    <input type="date" id="edit_exp_date" name="exp_date" class="form-control">
                </div>
                
                <div class="form-group full-width">
                    <label>รูปภาพใหม่ (อัปโหลดเพื่อเปลี่ยน)</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <small style="color:#888;">รูปเดิม: <span id="show_old_image_name"></span></small>
                </div>
            </div>

            <button type="submit" class="btn-submit" style="background:#f1c40f; color:#000;">บันทึกการแก้ไข</button>
        </form>
    </div>
</div>

<script>
    // ฟังก์ชันสร้าง Prefix อัตโนมัติเมื่อเลือกประเภท
    function genProductCode() {
        const categorySelect = document.getElementById('add_category');
        const codeInput = document.getElementById('add_product_code');
        
        // ดึงค่า data-prefix จาก option ที่เลือก
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const prefix = selectedOption.getAttribute('data-prefix');

        if (prefix) {
            let currentVal = codeInput.value;
            // ถ้าช่องว่าง หรือยังไม่มีขีด ให้ใส่ Prefix ใหม่เลย
            if (currentVal === "" || currentVal.indexOf('-') === -1) {
                 codeInput.value = prefix + "-"; 
            } else {
                // ถ้ามีเลขแล้ว ให้เปลี่ยนแค่ Prefix ข้างหน้า
                let parts = currentVal.split('-');
                if (parts.length > 1) {
                    codeInput.value = prefix + "-" + parts[1];
                } else {
                    codeInput.value = prefix + "-";
                }
            }
        }
    }

    function openAddModal() {
        document.getElementById('addModal').style.display = "block";
    }

    function openEditModal(data) {
        document.getElementById('editModal').style.display = "block";
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_product_code').value = data.product_code;
        document.getElementById('edit_name').value = data.name;
        document.getElementById('edit_quantity').value = data.quantity;
        document.getElementById('edit_exp_date').value = data.exp_date;
        document.getElementById('edit_category').value = data.category;
        document.getElementById('edit_selling_price').value = data.selling_price;
        document.getElementById('edit_cost').value = data.cost;
        // เซ็ตค่าหน่วยนับให้ตรงกับข้อมูลเดิม
        document.getElementById('edit_unit').value = data.unit; 

        document.getElementById('edit_old_image').value = data.image;
        document.getElementById('show_old_image_name').innerText = data.image ? data.image : "-ไม่มีรูป-";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }

    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "ข้อมูลนี้จะไม่สามารถกู้คืนได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบข้อมูล',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                // ถ้ากดยืนยัน ให้วิ่งไปหน้า delete
                window.location.href = 'products_delete.php?id=' + id;
            }
        })
    }
</script>

</body>
</html>