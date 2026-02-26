<?php
ob_start();
session_start();
require_once "db.php";

// -----------------------------------------------------------
// 1. ส่วนจัดการอัปเดตสต๊อก
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_stock') {
    $p_id = intval($_POST['product_id']);
    $amount = intval($_POST['amount']);
    $type = $_POST['adj_type']; // 'add' หรือ 'reduce'
    $reason = $_POST['reason'];

    $supplier = isset($_POST['supplier']) ? trim($_POST['supplier']) : "";

    // ดึงค่าเก่าก่อน
    $check = $conn->query("SELECT quantity FROM products WHERE id = $p_id");
    if ($check->num_rows > 0) {
        $curr = $check->fetch_assoc();
        $current_qty = intval($curr['quantity']);

        // คำนวณค่าใหม่
        if ($type == 'add') {
            $new_qty = $current_qty + $amount;
        } else {
            $new_qty = max(0, $current_qty - $amount);
        }

        // 1. อัปเดตยอดคงเหลือในตาราง products
        $update = $conn->prepare("UPDATE products SET quantity = ? WHERE id = ?");
        $update->bind_param("ii", $new_qty, $p_id);

        if ($update->execute()) {

            // 2. บันทึกประวัติลงตาราง stock_transactions
            $user = $_SESSION['username'];

            $log_sql = "INSERT INTO stock_transactions (username, product_id, type, amount, reason, supplier, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("sisiss", $user, $p_id, $type, $amount, $reason, $supplier);
            $log_stmt->execute();

            $_SESSION['msg_success'] = "บันทึกเรียบร้อย (คงเหลือ: $new_qty)";
        } else {
            $_SESSION['msg_error'] = "เกิดข้อผิดพลาดในการบันทึก";
        }
    }

    // รีเฟรชหน้าเดิม (ส่งค่าค้นหากลับไปด้วย)
    $redirect_url = "product_Stock.php";
    $params = [];
    if (isset($_GET['filter'])) $params[] = "filter=" . $_GET['filter'];
    if (isset($_GET['search'])) $params[] = "search=" . $_GET['search'];
    if (isset($_GET['search_category'])) $params[] = "search_category=" . $_GET['search_category'];

    if (!empty($params)) {
        $redirect_url .= "?" . implode("&", $params);
    }

    header("Location: " . $redirect_url);
    exit();
}

// -----------------------------------------------------------
// 2. ส่วน Logic แสดงผล & ค้นหา & Pagination
// -----------------------------------------------------------

if (!isset($_SESSION['first_login'])) {
    $_SESSION['first_login'] = true;
}
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ดึงหมวดหมู่มาทำ Dropdown
$cate_query = $conn->query("SELECT id, category_name FROM product_category ORDER BY category_name ASC");
$categories = [];
while ($cat = $cate_query->fetch_assoc()) {
    $categories[] = $cat;
}

// ตั้งค่า Pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $limit;

// รับค่าตัวแปรค้นหา
$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$search_category = isset($_GET['search_category']) ? $_GET['search_category'] : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";

// สร้าง SQL Condition (ใช้ร่วมกันทั้ง Count และ Data)
$condition_sql = " WHERE 1=1 ";

if ($search_text !== "") {
    $like = "%" . $conn->real_escape_string($search_text) . "%";
    $condition_sql .= " AND (p.product_code LIKE '$like' OR p.name LIKE '$like' OR c.category_name LIKE '$like')";
}

if ($search_category !== "") {
    $safe_cat = $conn->real_escape_string($search_category);
    $condition_sql .= " AND p.category = '$safe_cat' ";
}

// กรองด้วยปุ่ม Filter
if ($filter === "lowstock") {
    $condition_sql .= " AND p.quantity > 0 AND p.quantity <= 10";
}
if ($filter === "outofstock") {
    $condition_sql .= " AND p.quantity = 0";
}
if ($filter === "expired") {
    $condition_sql .= " AND p.exp_date < CURDATE() AND p.exp_date != '0000-00-00'";
}

// 1. หาจำนวนรายการทั้งหมด (Count)
$sql_count = "SELECT COUNT(*) as total FROM products p LEFT JOIN product_category c ON p.category = c.id" . $condition_sql;
$query_count = $conn->query($sql_count);
$row_count = $query_count->fetch_assoc();
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

// 2. ดึงข้อมูลจริง (ใส่ LIMIT)
$sql = "SELECT p.id, p.product_code, p.name, c.category_name, p.quantity, p.exp_date, p.image, p.unit 
        FROM products p LEFT JOIN product_category c ON p.category = c.id "
    . $condition_sql
    . " ORDER BY p.id DESC LIMIT $start, $limit";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สต๊อกสินค้า - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <link rel='icon' type='image/png' href='favicon.png'>

    <style>
        /* --- Styling หลัก --- */
        .content-container {
            padding: 30px;
            background-color: #f3f4f6;
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-buttons a {
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            color: white;
            transition: 0.2s;
        }

        .filter-buttons a:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-all {
            background: #3498db;
        }

        .btn-low {
            background: #f1c40f;
            color: black !important;
        }

        .btn-out {
            background: #7f8c8d;
        }

        .btn-expired {
            background: #e74c3c;
        }

        .filter-buttons a.active {
            box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.2) inset;
            transform: scale(0.98);
        }

        /* Updated Search Box */
        .search-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .search-box input {
            flex: 1;
            border: none;
            background: #eef2f6;
            padding: 12px 14px;
            border-radius: 10px;
            font-family: 'Prompt';
            min-width: 200px;
        }

        .search-select {
            border: none;
            background: #eef2f6;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Prompt', sans-serif;
            cursor: pointer;
            min-width: 150px;
        }

        .btn-search {
            background: #356CB5;
            padding: 10px 18px;
            border-radius: 10px;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-reset {
            background: #e7ebf0;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
        }

        /* --- Scrollable Table CSS --- */
        .table-scroll-container {
            width: 100%;
            max-height: 65vh;
            overflow-y: auto;
            overflow-x: auto;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            position: relative;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
        }

        /* Sticky Header */
        th {
            background: #f3f6fb;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
        }

        tr:hover {
            background-color: #f0f7ff;
        }

        .product-img {
            width: 65px;
            height: 65px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid #ccc;
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .stock-normal {
            background: #e6f6ed;
            color: #1b9c5a;
        }

        .stock-low {
            background: #fdecea;
            color: #c0392b;
        }

        .stock-warn {
            background: #fff8e1;
            color: #f39c12;
        }

        .btn-adjust {
            background: #3498db;
            padding: 7px 12px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .btn-adjust:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }

        /* Pagination Style */
        .pagination-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination {
            display: flex;
            justify-content: center;
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

        .text-muted {
            color: #666;
            font-size: 14px;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 90%;
            max-width: 450px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s ease-out;
            font-family: 'Prompt', sans-serif;
            position: relative;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            box-sizing: border-box;
            background: #f9f9f9;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            background: #fff;
        }

        .btn-submit {
            width: 100%;
            background: #27ae60;
            color: white;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #219150;
            transform: translateY(-1px);
        }

        .adjust-options {
            display: flex;
            gap: 15px;
            margin-bottom: 5px;
        }

        .adjust-radio {
            display: none;
        }

        .adjust-label {
            flex: 1;
            padding: 12px;
            text-align: center;
            border: 2px solid #eee;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
            color: #777;
        }

        #type_add:checked+label {
            background: #e8f8f5;
            border-color: #2ecc71;
            color: #27ae60;
            font-weight: bold;
        }

        #type_reduce:checked+label {
            background: #fdedec;
            border-color: #e74c3c;
            color: #c0392b;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <?php include "topbar.php"; ?>

        <div class="content-container">
            <div class="page-title">สต๊อกสินค้า</div>

            <?php
            if (isset($_SESSION['first_login']) && $_SESSION['first_login'] === true) {
                $low = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity > 0 AND quantity <= 10");
                $row_low = $low->fetch_assoc();
                if ($row_low['total'] > 0) {
                    echo "<script>Swal.fire({icon: 'warning', title: 'สินค้าใกล้หมด!', text: 'มีสินค้าใกล้หมดจำนวน {$row_low['total']} รายการ', confirmButtonText: 'รับทราบ', confirmButtonColor: '#f1c40f'});</script>";
                }
                $_SESSION['first_login'] = false;
            }

            if (isset($_SESSION['msg_success'])) {
                echo "<script>Swal.fire({icon: 'success', title: 'สำเร็จ', text: '" . $_SESSION['msg_success'] . "', timer: 1500, showConfirmButton: false});</script>";
                unset($_SESSION['msg_success']);
            }
            ?>

            <div class="filter-buttons">
                <a href="product_Stock.php?filter=all" class="btn-all <?= $filter == 'all' ? 'active' : '' ?>">สินค้าทั้งหมด</a>
                <a href="product_Stock.php?filter=lowstock" class="btn-low <?= $filter == 'lowstock' ? 'active' : '' ?>">สินค้าใกล้หมด</a>
                <a href="product_Stock.php?filter=outofstock" class="btn-out <?= $filter == 'outofstock' ? 'active' : '' ?>">สินค้าหมด</a>
                <a href="product_Stock.php?filter=expired" class="btn-expired <?= $filter == 'expired' ? 'active' : '' ?>">สินค้าหมดอายุ</a>
            </div>

            <form class="search-box" method="get" action="product_Stock.php">
                <input type="hidden" name="filter" value="<?= $filter ?>">

                <input type="text" name="search" placeholder="ค้นหา (ชื่อ / รหัสสินค้า)" value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">

                <select name="search_category" class="search-select">
                    <option value="">-- ทุกหมวดหมู่ --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $search_category == $cat['id'] ? 'selected' : '' ?>>
                            <?= $cat['category_name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-search">ค้นหา</button>
                <?php if ($search_text !== "" || $search_category !== "" || $filter !== "all"): ?>
                    <a href="product_Stock.php" class="btn-reset">ล้าง</a>
                <?php endif; ?>
            </form>

            <div class="table-scroll-container">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">ลำดับ</th>
                            <th>รูป</th>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภท</th>
                            <th>คงเหลือ</th>
                            <th>วันหมดอายุ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0):
                            $i = $start + 1; // รันเลขลำดับต่อจากหน้าเดิม
                            while ($row = $products->fetch_assoc()):
                                if ($row['quantity'] <= 0) {
                                    $badge = "stock-low";
                                    $label = "หมด";
                                } elseif ($row['quantity'] <= 10) {
                                    $badge = "stock-warn";
                                    $label = "ใกล้หมด";
                                } else {
                                    $badge = "stock-normal";
                                    $label = "ปกติ";
                                }

                                $expired = ($row['exp_date'] != '0000-00-00' && $row['exp_date'] < date("Y-m-d"));
                        ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?php if ($row['image']): ?><img src="uploads/<?= $row['image'] ?>" class="product-img"><?php else: ?><span style="color:#777;">ไม่มีรูป</span><?php endif; ?></td>
                                    <td><?= $row['product_code'] ?></td>
                                    <td>
                                        <?= $row['name'] ?>
                                        <?php if ($expired): ?>
                                            <br><span style="color:red; font-size:11px; font-weight:bold;"><i class="fa-solid fa-circle-exclamation"></i> หมดอายุ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['category_name'] ?></td>
                                    <td>
                                        <span class="badge <?= $badge ?>">
                                            <?= number_format($row['quantity']) ?> <?php echo isset($row['unit']) ? $row['unit'] : 'หน่วย'; ?> | <?= $label ?>
                                        </span>
                                    </td>
                                    <td style="<?= $expired ? 'color:red;font-weight:bold;' : '' ?>"><?= $row['exp_date'] ?></td>
                                    <td>
                                        <button type="button" onclick="openStockModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>', <?= $row['quantity'] ?>, '<?= isset($row['unit']) ? $row['unit'] : 'หน่วย' ?>')" class="btn-adjust">
                                            <i class="fa-solid fa-pen-to-square"></i> ปรับสต๊อก
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding: 20px;">ไม่พบข้อมูลสินค้า</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <div class="text-muted">
                        แสดง <?= $products->num_rows ?> รายการ (จากทั้งหมด <?= number_format($total_records) ?>) - หน้า <?= $page ?> / <?= $total_pages ?>
                    </div>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=1&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>&filter=<?= $filter ?>" title="หน้าแรก"><i class="fa-solid fa-angles-left"></i> หน้าแรก</a>
                            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>&filter=<?= $filter ?>" title="ย้อนกลับ"><i class="fa-solid fa-angle-left"></i></a>
                        <?php endif; ?>

                        <?php
                        $range = 2;
                        for ($p = 1; $p <= $total_pages; $p++):
                            if ($p == 1 || $p == $total_pages || ($p >= $page - $range && $p <= $page + $range)):
                        ?>
                                <a href="?page=<?= $p ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>&filter=<?= $filter ?>" class="<?= $page == $p ? 'active' : '' ?>"><?= $p ?></a>
                            <?php elseif (($p == $page - $range - 1) || ($p == $page + $range + 1)): ?>
                                <span style="padding:8px; color:#999;">...</span>
                        <?php endif;
                        endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>&filter=<?= $filter ?>" title="ถัดไป"><i class="fa-solid fa-angle-right"></i></a>
                            <a href="?page=<?= $total_pages ?>&search=<?= urlencode($search_text) ?>&search_category=<?= urlencode($search_category) ?>&filter=<?= $filter ?>" title="หน้าสุดท้าย">หน้าสุดท้าย <i class="fa-solid fa-angles-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div id="stockModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStockModal()">&times;</span>
            <h3 style="margin-top:0; color:#333;">ปรับสต๊อกสินค้า</h3>
            <p id="modal_product_name" style="color:#666; font-size:14px; margin-bottom:15px; border-bottom:1px solid #eee; padding-bottom:10px;"></p>

            <form method="post" action="">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" id="modal_product_id" name="product_id">

                <div class="form-group" style="text-align:center;">
                    <label style="font-size:14px; color:#888;">คงเหลือปัจจุบัน</label>
                    <div style="display:flex; align-items:baseline; justify-content:center; gap:5px;">
                        <h1 id="modal_current_qty" style="color:#3498db; margin:0; font-size:48px; line-height:1;">0</h1>
                        <span id="modal_unit" style="color:#666; font-size:14px;">หน่วย</span>
                    </div>
                </div>

                <div class="form-group">
                    <label>เลือกการทำรายการ</label>
                    <div class="adjust-options">
                        <input type="radio" id="type_add" name="adj_type" value="add" class="adjust-radio" onchange="updateReasonOptions()" checked>
                        <label for="type_add" class="adjust-label"><i class="fa-solid fa-plus"></i> เพิ่มสต๊อก</label>

                        <input type="radio" id="type_reduce" name="adj_type" value="reduce" class="adjust-radio" onchange="updateReasonOptions()">
                        <label for="type_reduce" class="adjust-label"><i class="fa-solid fa-minus"></i> ลดสต๊อก</label>
                    </div>
                </div>

                <div class="form-group">
                    <label>เหตุผล / สาเหตุ</label>
                    <select name="reason" id="reason_select" class="form-control" required></select>
                </div>

                <div class="form-group" id="supplier_group" style="display:none;">
                    <label>รับจาก / ชื่อบริษัท / ร้านค้า</label>
                    <input type="text" name="supplier" class="form-control" placeholder="ระบุแหล่งที่มา (เช่น บ.ขนส่ง A, ร้าน B)">
                </div>

                <div class="form-group">
                    <label>
                        จำนวน
                        <span style="color:#666; font-weight:normal;">
                            (<span id="modal_input_unit">หน่วย</span>)
                        </span>
                    </label>
                    <input type="number" id="amount_input" name="amount" class="form-control" placeholder="ระบุจำนวน" min="1" oninput="calculateNewQty()" required>
                    
                    <div id="preview-box" style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-radius: 8px; text-align: center; display: none;">
                        <span style="color: #555; font-size: 14px;">ยอดคงเหลือหลังปรับ: </span>
                        <strong id="preview_qty" style="font-size: 20px; color: #356CB5;">0</strong>
                    </div>
                </div>

                <button type="submit" class="btn-submit">บันทึกข้อมูล</button>
            </form>
        </div>
    </div>

    <script>
        const reasons = {
            add: [{
                    value: 'รับสินค้าเข้า',
                    text: 'รับสินค้าเข้า'
                },
                {
                    value: 'ตรวจนับเจอเกิน',
                    text: 'ตรวจนับเจอเกิน'
                },
                {
                    value: 'เพิ่ม-อื่นๆ',
                    text: 'เพิ่ม-อื่นๆ'
                }
            ],
            reduce: [{
                    value: 'ขายหน้าร้าน',
                    text: 'ขายหน้าร้าน'
                },
                {
                    value: 'สินค้าชำรุด',
                    text: 'สินค้าชำรุด/เสียหาย'
                },
                {
                    value: 'สินค้าหมดอายุ',
                    text: 'สินค้าหมดอายุ'
                },
                {
                    value: 'เบิกใช้ภายใน',
                    text: 'เบิกใช้ภายใน'
                },
                {
                    value: 'สินค้าสูญหาย',
                    text: 'สินค้าสูญหาย/นับขาด'
                },
                {
                    value: 'ลด-อื่นๆ',
                    text: 'ลด-อื่นๆ'
                }
            ]
        };

        let currentStockQty = 0; // เก็บค่าสต๊อกปัจจุบันไว้คำนวณ

        function updateReasonOptions() {
            const isAdd = document.getElementById('type_add').checked;
            const select = document.getElementById('reason_select');
            const supplierGroup = document.getElementById('supplier_group');
            const amountInput = document.getElementById('amount_input');

            const options = isAdd ? reasons.add : reasons.reduce;
            select.innerHTML = "";
            options.forEach(opt => {
                const el = document.createElement("option");
                el.value = opt.value;
                el.textContent = opt.text;
                select.appendChild(el);
            });

            if (isAdd) {
                supplierGroup.style.display = "block";
                amountInput.removeAttribute('max'); // ถ้าเพิ่มสต๊อก ไม่จำกัดจำนวน
            } else {
                supplierGroup.style.display = "none";
                amountInput.setAttribute('max', currentStockQty); // ถ้าลดสต๊อก ห้ามลดเกินที่มี
            }
            
            calculateNewQty(); // คำนวณใหม่เมื่อเปลี่ยนประเภท เพิ่ม/ลด
        }

        function calculateNewQty() {
            const isAdd = document.getElementById('type_add').checked;
            const inputVal = parseInt(document.getElementById('amount_input').value) || 0;
            const previewBox = document.getElementById('preview-box');
            const previewQty = document.getElementById('preview_qty');

            if (inputVal > 0) {
                previewBox.style.display = "block";
                let newQty = isAdd ? currentStockQty + inputVal : currentStockQty - inputVal;
                
                // กันติดลบ
                if(newQty < 0) newQty = 0; 

                previewQty.innerText = new Intl.NumberFormat().format(newQty);
                previewQty.style.color = isAdd ? '#27ae60' : '#e74c3c'; // สีเขียวถ้าเพิ่ม สีแดงถ้าลด
            } else {
                previewBox.style.display = "none";
            }
        }

        function openStockModal(id, name, qty, unit) {
            var modal = document.getElementById('stockModal');
            modal.style.display = "block";

            currentStockQty = parseInt(qty); // เก็บค่า

            document.getElementById('modal_product_id').value = id;
            document.getElementById('modal_product_name').innerText = name;
            document.getElementById('modal_current_qty').innerText = new Intl.NumberFormat().format(qty);
            document.getElementById('modal_input_unit').innerText = unit ? unit : 'หน่วย';

            // Reset ค่าในฟอร์มเมื่อเปิดใหม่
            document.getElementById('amount_input').value = '';
            document.getElementById('preview-box').style.display = "none";
            document.querySelector('input[name="supplier"]').value = '';
            document.getElementById('type_add').checked = true;
            
            updateReasonOptions();
        }

        function closeStockModal() {
            document.getElementById('stockModal').style.display = "none";
        }


        window.onclick = function(event) {
            var modal = document.getElementById('stockModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateReasonOptions();
        });
    </script>

</body>

</html>