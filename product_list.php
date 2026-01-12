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
$unit_options = ["ชิ้น", "ห่อ", "ซอง", "กล่อง", "แพ็ค", "แท่ง","แผง","โหล", "ลัง", "คู่", "ชุด", "ขวด", "กระป๋อง", "ถุง", "ม้วน", "แถว", "ถ้วย", "ก้อน", "หลอด"];

// ------------------------------------------------------------------
// 2. ส่วนจัดการบันทึกข้อมูล (ADD & EDIT)
// ------------------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // --- กรณี: เพิ่มสินค้าใหม่ (ADD) ---
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $product_code  = $_POST['product_code'];
        $name          = $_POST['name'];
        $category      = $_POST['category'];
        $unit          = $_POST['unit']; 
        $cost          = $_POST['cost'];
        $selling_price = $_POST['selling_price'];
        $mfg_date      = $_POST['mfg_date'];
        $exp_date      = $_POST['exp_date']; 
        $quantity      = intval($_POST['quantity']);

        // อัปโหลดรูปภาพ
        $image_name = "";
        if (!empty($_FILES["image"]["name"])) {
            $image_name = time() . "_" . basename($_FILES["image"]["name"]);
            move_uploaded_file($_FILES["image"]["tmp_name"], "uploads/" . $image_name);
        }

        $stmt = $conn->prepare("INSERT INTO products (product_code, name, category, unit, cost, selling_price, mfg_date, exp_date, quantity, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssddsis", $product_code, $name, $category, $unit, $cost, $selling_price, $mfg_date, $exp_date, $quantity, $image_name);
        
        if ($stmt->execute()) {
            if ($quantity > 0) {
                 $new_product_id = $conn->insert_id;
                 $username = $_SESSION['username'];
                 $stmt_tr = $conn->prepare("INSERT INTO stock_transactions (product_id, type, amount, balance, username, reason, created_at) VALUES (?, 'add', ?, ?, ?, 'สินค้าใหม่ (เริ่มต้น)', NOW())");
                 $stmt_tr->bind_param("iiis", $new_product_id, $quantity, $quantity, $username);
                 $stmt_tr->execute();
            }
            $_SESSION['msg_success'] = "เพิ่มสินค้าเรียบร้อยแล้ว";
        } else {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการเพิ่มสินค้า";
        }
        header("Location: product_list.php");
        exit();
    }

     // --- กรณี: แก้ไขสินค้า (EDIT) ---
    // [จุดที่แก้ไข] เพิ่ม Logic เปลี่ยนรหัสสินค้าเมื่อหมวดหมู่เปลี่ยน
    if (isset($_POST['action']) && $_POST['action'] === 'edit') {
        $id            = intval($_POST['id']);
        $product_code  = $_POST['product_code']; 
        $name          = $_POST['name'];
        $category      = $_POST['category']; 
        $unit          = $_POST['unit']; 
        $new_quantity  = intval($_POST['quantity']);
        $cost          = floatval($_POST['cost']);
        $exp_date      = $_POST['exp_date'];
        $selling_price = floatval($_POST['selling_price']);
        $old_image     = $_POST['old_image']; 
        $image_name    = $old_image;

        // 1. ตรวจสอบหมวดหมู่เดิม
        $q_check = $conn->query("SELECT category, quantity FROM products WHERE id = $id");
        $row_check = $q_check->fetch_assoc();
        $old_category = $row_check['category'];
        $old_qty = intval($row_check['quantity']);

        // 2. ถ้าเปลี่ยนหมวดหมู่ -> เปลี่ยนรหัสสินค้าใหม่
        if ($category != $old_category) {
            $q_prefix = $conn->query("SELECT prefix FROM product_category WHERE id = '$category'");
            $row_prefix = $q_prefix->fetch_assoc();
            $new_prefix = !empty($row_prefix['prefix']) ? $row_prefix['prefix'] : 'PD';

            // หาเลข MAX ของหมวดใหม่
            $sql_max = "SELECT MAX(CAST(SUBSTRING_INDEX(product_code, '-', -1) AS UNSIGNED)) as max_num 
                        FROM products 
                        WHERE product_code LIKE '$new_prefix-%'";
            $res_max = $conn->query($sql_max);
            $row_max = $res_max->fetch_assoc();
            
            $next_num = 1;
            if (!empty($row_max['max_num'])) {
                $next_num = intval($row_max['max_num']) + 1;
            }
            $product_code = $new_prefix . "-" . str_pad($next_num, 4, "0", STR_PAD_LEFT); 
        }

        // จัดการรูปภาพ
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
        $stmt->bind_param("ssssiddssi", $product_code, $name, $category, $unit, $new_quantity, $selling_price, $cost, $exp_date, $image_name, $id);

        if ($stmt->execute()) {
             // บันทึก Log การเปลี่ยนแปลงสต็อก
             if ($new_quantity != $old_qty) {
                $diff = $new_quantity - $old_qty;
                $type = ($diff > 0) ? 'add' : 'reduce';
                $amount = abs($diff);
                $balance = $new_quantity;
                $username = $_SESSION['username'];
                $reason = "แก้ไขข้อมูลสินค้า (ปรับยอด)";
                $stmt_tr = $conn->prepare("INSERT INTO stock_transactions (product_id, type, amount, balance, username, reason, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt_tr->bind_param("isiis", $id, $type, $amount, $balance, $username, $reason);
                $stmt_tr->execute();
            }
            $_SESSION['msg_success'] = "แก้ไขข้อมูลสำเร็จ";
        } else {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
        $stmt->close(); 

        // ----------------------------------------------------
        // [จุดสำคัญ] สร้างลิงก์ Redirect กลับไปที่เดิม
        // ----------------------------------------------------
        $redirect_url = "product_list.php";
        $params = [];

        // เช็คว่ามีค่าค้นหาเดิมส่งมาไหม
        if (!empty($_POST['return_search'])) {
            $params[] = "search=" . urlencode($_POST['return_search']);
        }
        if (!empty($_POST['return_category'])) {
            // ชื่อตัวแปรต้องตรงกับที่รับ GET ด้านบน (search_category)
            $params[] = "search_category=" . urlencode($_POST['return_category']);
        }
        if (!empty($_POST['return_page'])) {
            $params[] = "page=" . intval($_POST['return_page']);
        }

        // ต่อ String URL
        if (!empty($params)) {
            $redirect_url .= "?" . implode("&", $params);
        }

        header("Location: " . $redirect_url);
        exit();
    }
}

// ------------------------------------------------------------------
// 3. เตรียมข้อมูลสำหรับแสดงผล
// ------------------------------------------------------------------

// 3.1 ดึงหมวดหมู่สินค้า
$cate_query = $conn->query("SELECT id, category_name, prefix FROM product_category ORDER BY category_name ASC");
$categories = [];
while ($cat = $cate_query->fetch_assoc()) {
    $categories[] = $cat;
}

// 3.2 คำนวณรหัสถัดไป (เก็บไว้ใช้ใน Javascript)
$next_codes_map = []; 
foreach ($categories as $cat) {
    $cat_id = $cat['id'];
    $prefix = !empty($cat['prefix']) ? $cat['prefix'] : 'PD'; 
    $sql_last = "SELECT product_code FROM products WHERE category = $cat_id ORDER BY id DESC LIMIT 1";
    $res_last = $conn->query($sql_last);
    $next_num = 1;
    if ($res_last->num_rows > 0) {
        $row_last = $res_last->fetch_assoc();
        $parts = explode('-', $row_last['product_code']);
        if (count($parts) > 1) {
            $next_num = intval(end($parts)) + 1;
        }
    }
    $next_codes_map[$cat_id] = $prefix . "-" . str_pad($next_num, 3, "0", STR_PAD_LEFT);
}

// ------------------------------------------------------------------
// 4. ส่วนแบ่งหน้า (Pagination) และ ค้นหา
// ------------------------------------------------------------------

// ตั้งค่าจำนวนต่อหน้า
$limit = 20; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// รับค่าค้นหา
$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : ""; 

// สร้าง SQL เงื่อนไข (ใช้ร่วมกันทั้ง Count และ Data)
$condition_sql = " WHERE 1=1 ";
if ($search_text !== "") {
    $like = "%".$conn->real_escape_string($search_text)."%";
    $condition_sql .= " AND (p.product_code LIKE '$like' OR p.name LIKE '$like' OR c.category_name LIKE '$like')";
}
if ($search_category !== "") {
    $safe_cat = $conn->real_escape_string($search_category);
    $condition_sql .= " AND p.category = '$safe_cat' ";
}

// 4.1 หาจำนวนรายการทั้งหมด (เพื่อคำนวณหน้า)
$sql_count = "SELECT COUNT(*) as total FROM products p LEFT JOIN product_category c ON p.category = c.id" . $condition_sql;
$query_count = $conn->query($sql_count);
$row_count = $query_count->fetch_assoc();
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

// 4.2 ดึงข้อมูลจริง (ใส่ LIMIT)
$sql = "SELECT p.*, c.category_name FROM products p LEFT JOIN product_category c ON p.category = c.id " . $condition_sql . " ORDER BY p.id DESC LIMIT $start, $limit";
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
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 20px; 
        }
        .search-box input { 
            flex: 1; border: none; background: #eef2f6; 
            padding: 12px 14px; border-radius: 10px; font-size: 14px; min-width: 200px;
        }
        .search-select {
            border: none; background: #eef2f6;
            padding: 12px 14px; border-radius: 10px; font-size: 14px;
            font-family: 'Prompt', sans-serif; cursor: pointer;
            min-width: 150px;
        }

        .btn-search { background:#356CB5; color:white; padding:10px 18px; border:none; border-radius:10px; font-weight:600; cursor:pointer; }
        .btn-reset { background:#e7ebf0; padding:10px 16px; border-radius:10px; text-decoration:none; color:#333; display: flex; align-items: center; }

        /* --- Scrollable Table CSS (ส่วนที่แก้ไข) --- */
        .table-scroll-container {
            width: 100%;
            /* กำหนดความสูงของตารางให้พอดีจอ (ปรับเลข 250px ได้ตามต้องการ) */
            max-height: 65vh; 
            overflow-y: auto; /* ให้มี scroll แนวตั้ง */
            overflow-x: auto; /* ให้มี scroll แนวนอนถ้าจอเล็ก */
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            position: relative;
        }

        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
        }
        
        th, td { padding:14px 12px; border-bottom:1px solid #eee; }
        
        /* Sticky Header: ตรึงหัวตารางไว้บนสุด */
        th { 
            background:#f3f6fb; 
            font-weight:600; 
            position: sticky;
            top: 0;
            z-index: 10; /* ให้ลอยอยู่เหนือข้อมูล */
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }
        tr:hover { background-color: #a1c9ff1f;}
        
        .product-img { width: 65px; height: 65px; object-fit: cover; border-radius:12px; border:1px solid #ddd; }
        .badge { padding:6px 12px; font-size:12px; font-weight:600; border-radius:20px; }
        .stock-ok { background:#e6f6ed; color:#1b9c5a; }
        .stock-low { background:#fdecea; color:#c0392b; }
        
        .btn-edit { background:#f1c40f; padding:7px 5px; color:white; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px;}
        .btn-delete { background:#e74c3c; padding:7px 5px; color:white; border-radius:8px; text-decoration:none; border:none; cursor:pointer; font-size:14px;}
        
        /* Pagination Style */
        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 0 10px;
        }
        .pagination {
            display: flex;
            gap: 5px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            background: white;
            transition: 0.2s;
            font-size: 14px;
        }
        .pagination a:hover {
            background-color: #f1f1f1;
        }
        .pagination a.active {
            background-color: #356CB5;
            color: white;
            border-color: #356CB5;
        }
        .text-muted { color: #666; font-size: 14px; }

        /* Modal Styles (คงเดิม) */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 25px; border: 1px solid #888; width: 90%; max-width: 600px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); animation: slideDown 0.3s ease-out; font-family: 'Prompt', sans-serif; position: relative; }
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
            <input type="text" name="search" placeholder="ค้นหา (รหัส / ชื่อสินค้า)" value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">
            
            <select name="search_category" class="search-select">
                <option value="">-- ทุกหมวดหมู่ --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $search_category == $cat['id'] ? 'selected' : '' ?>>
                        <?= $cat['category_name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-search">ค้นหา</button>
            
            <?php if ($search_text !== "" || $search_category !== ""): ?>
                <a href="product_list.php" class="btn-reset">ล้าง</a>
            <?php endif; ?>
        </form>

        <div class="table-scroll-container">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ลำดับ</th>
                        <th>รหัสสินค้า</th>
                        <th>รูปภาพ</th>
                        <th>ชื่อสินค้า</th>
                        <th>ประเภท</th>
                        <th>หน่วย</th> 
                        <th>คงเหลือ</th>
                        <th>ราคาทุน</th>
                        <th>ราคาขาย</th>
                        <th>วันหมดอายุ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($products->num_rows > 0): ?>
                    <?php 
                    // รันลำดับต่อเนื่องตามหน้า (เช่นหน้า 2 เริ่มที่ 21)
                    $i = $start + 1; 
                    while ($row = $products->fetch_assoc()): 
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
                        <td><?= $row['unit'] ?></td> 
                        <td><span class="badge <?= $stock_class ?>"><?= $row['quantity'] ?> | <?= $stock_label ?></span></td>
                        <td><?= number_format($row['cost'], 2) ?> บาท</td>
                        <td><?= number_format($row['selling_price'], 2) ?> บาท</td>
                        <td><?= $row['exp_date'] ?></td>
                        <td>
                            <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button type="button" class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center; padding:50px;">ไม่พบข้อมูลสินค้า</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($total_pages > 1): ?>
            <div class="pagination-container" style="display: flex; flex-direction: column; align-items: center; gap: 10px; margin-top: 20px;">
                
                <div class="text-muted" style="font-size: 14px;">
                    แสดง <?= $products->num_rows ?> รายการ (จากทั้งหมด <?= number_format($total_records) ?>) - หน้า <?= $page ?> / <?= $total_pages ?>
                </div>

                <div class="pagination" style="display: flex; justify-content: center; gap: 5px;">
                    
                    <?php if($page > 1): ?>
                        <a href="?page=1&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>" title="หน้าแรก">
                            <i class="fa-solid fa-angles-left"></i> หน้าแรก
                        </a>
                        <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>" title="ย้อนกลับ">
                            <i class="fa-solid fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php 
                    $range = 2; 
                    for($p = 1; $p <= $total_pages; $p++): 
                        if ($p == 1 || $p == $total_pages || ($p >= $page - $range && $p <= $page + $range)):
                    ?>
                        <a href="?page=<?= $p ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>" 
                        class="<?= $page == $p ? 'active' : '' ?>">
                            <?= $p ?>
                        </a>
                    <?php elseif (($p == $page - $range - 1) || ($p == $page + $range + 1)): ?>
                        <span style="padding:8px; color:#999;">...</span>
                    <?php endif; endfor; ?>

                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>" title="ถัดไป">
                            <i class="fa-solid fa-angle-right"></i>
                        </a>
                        <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>" title="หน้าสุดท้าย">
                            หน้าสุดท้าย <i class="fa-solid fa-angles-right"></i>
                        </a>
                    <?php endif; ?>
                    
                </div>
            </div>
            <?php endif; ?>

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
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['category_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>รหัสสินค้า <span style="color:red;">*</span></label>
                    <input type="text" name="product_code" id="add_product_code" class="form-control" placeholder="ระบบจะสร้างให้อัตโนมัติ..." readonly style="background-color: #eee; cursor: not-allowed; color:#555;" required>
                </div>
                <div class="form-group">
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
                    <label>วันที่ผลิต</label>
                    <input type="date" name="mfg_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>วันหมดอายุ</label>
                    <input type="date" name="exp_date" class="form-control">
                </div>
                <div class="form-group">
                    <label>จำนวนเริ่มต้น</label>
                    <input type="number" name="quantity" class="form-control" value="0">
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

            <input type="hidden" name="return_search" value="<?= htmlspecialchars($search_text) ?>">
            <input type="hidden" name="return_category" value="<?= htmlspecialchars($search_category) ?>">
            <input type="hidden" name="return_page" value="<?= $page ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>รหัสสินค้า</label>
                    <input type="text" id="edit_product_code" name="product_code" readonly style="background-color: #e9ecef; cursor: not-allowed; color: #6c757d;" required>
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
    const nextCodeMap = <?php echo json_encode($next_codes_map); ?>;

    function genProductCode() {
        const categorySelect = document.getElementById('add_category');
        const codeInput = document.getElementById('add_product_code');
        const catId = categorySelect.value;
        if (nextCodeMap[catId]) {
            codeInput.value = nextCodeMap[catId]; 
        } else {
            codeInput.value = ""; 
        }
    }

    function openAddModal() {
        document.getElementById('addModal').style.display = "block";
        document.getElementById('add_category').value = "";
        document.getElementById('add_product_code').value = "";
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
                window.location.href = 'products_delete.php?id=' + id;
            }
        })
    }
</script>

</body>
</html>