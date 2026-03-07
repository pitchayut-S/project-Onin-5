<?php
ob_start();
session_start();
require_once "db.php";

/* ======================
   AUTH CHECK
====================== */
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* ======================
   ADD CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] == 'add') {

    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        // --- 1. ตรวจสอบข้อมูลซ้ำก่อนเพิ่ม ---
        $check = $conn->prepare("SELECT id FROM product_category WHERE category_name = ? OR prefix = ?");
        $check->bind_param("ss", $category_name, $prefix);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // ถ้ามีซ้ำ ให้แจ้งเตือน
            $_SESSION['swal'] = ['icon' => 'warning', 'title' => 'ข้อมูลซ้ำ', 'text' => 'ชื่อประเภทสินค้า หรือ Prefix นี้มีอยู่ในระบบแล้ว!'];
        } else {
            // ถ้าไม่ซ้ำ ให้บันทึกตามปกติ
            $stmt = $conn->prepare("INSERT INTO product_category (category_name, prefix) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $prefix);

            if ($stmt->execute()) {
                $_SESSION['swal'] = ['icon' => 'success', 'title' => 'สำเร็จ', 'text' => 'เพิ่มประเภทสินค้าเรียบร้อยแล้ว'];
            } else {
                $_SESSION['swal'] = ['icon' => 'error', 'title' => 'ผิดพลาด', 'text' => 'เกิดข้อผิดพลาดในการบันทึก'];
            }
        }
    }
    header("Location: category_list.php");
    exit();
}

/* ======================
   EDIT CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') {

    $id = intval($_POST['id']);
    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        // --- 1. ตรวจสอบข้อมูลซ้ำก่อนแก้ไข (โดยยกเว้น ID ของตัวเอง) ---
        $check = $conn->prepare("SELECT id FROM product_category WHERE (category_name = ? OR prefix = ?) AND id != ?");
        $check->bind_param("ssi", $category_name, $prefix, $id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // ถ้ามีซ้ำกับรายการอื่น
            $_SESSION['swal'] = ['icon' => 'warning', 'title' => 'ข้อมูลซ้ำ', 'text' => 'ชื่อประเภทสินค้า หรือ Prefix นี้ถูกใช้ไปแล้ว!'];
        } else {
            // --- 2. ถ้าไม่ซ้ำ ให้แก้ไขตาราง product_category ---
            $stmt = $conn->prepare("UPDATE product_category SET category_name = ?, prefix = ? WHERE id = ?");
            $stmt->bind_param("ssi", $category_name, $prefix, $id);

            if ($stmt->execute()) {
                // =========================================================================
                // 3. [เพิ่มใหม่] สั่งอัปเดตรหัสสินค้าในตาราง products ให้สอดคล้องกัน
                // =========================================================================
                $sql_update_products = "UPDATE products 
                                        SET product_code = CONCAT(?, '-', SUBSTRING_INDEX(product_code, '-', -1)) 
                                        WHERE category = ?";
                $stmt_prod = $conn->prepare($sql_update_products);
                $stmt_prod->bind_param("si", $prefix, $id);
                $stmt_prod->execute();
                $stmt_prod->close();
                // =========================================================================

                $_SESSION['swal'] = ['icon' => 'success', 'title' => 'สำเร็จ', 'text' => 'แก้ไขหมวดหมู่และรหัสสินค้าเรียบร้อยแล้ว'];
            } else {
                $_SESSION['swal'] = ['icon' => 'error', 'title' => 'ผิดพลาด', 'text' => 'เกิดข้อผิดพลาดในการบันทึก'];
            }
        }
    }
    header("Location: category_list.php");
    exit();
}

/* ======================
   SEARCH
====================== */
$search_text = $_GET['search'] ?? "";
$sql = "SELECT * FROM product_category";
if ($search_text !== "") {
    $like = "%{$search_text}%";
    $sql .= " WHERE category_name LIKE '$like' OR prefix LIKE '$like'";
}
$sql .= " ORDER BY id DESC";
$categories = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ประเภทสินค้า - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="style.css">
    <link rel='icon' type='image/png' href='favicon.png'>

    <style>
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

        /* Search Box */
        .search-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
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

        .btn-search {
            background: #356CB5;
            color: white;
            padding: 10px 18px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-reset {
            background: #e7ebf0;
            padding: 10px 16px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f3f6fb;
            font-weight: 600;
        }

        tr:hover {
            background-color: #a1c9ff1f;
        }

        /* Buttons */
        .btn-add {
            background: #28a745;
            color: white;
            padding: 10px 18px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Prompt';
        }

        .btn-edit {
            background: #f1c40f;
            padding: 7px 12px;
            color: white;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-delete {
            background: #e74c3c;
            padding: 7px 12px;
            color: white;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        /* --- Custom Modal CSS (เหมือนหน้าสินค้า) --- */
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
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
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
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .btn-submit {
            width: 100%;
            background: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
        }

        .btn-submit:hover {
            background: #218838;
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <?php include "topbar.php"; ?>

        <div class="content-container">

            <div class="page-title">ข้อมูลประเภทสินค้า</div>

            <div style="text-align:right; margin-bottom:20px;">
                <button class="btn-add" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> เพิ่มประเภทสินค้า
                </button>
            </div>

            <form class="search-box" method="get">
                <input type="text" name="search" placeholder="ค้นหาชื่อประเภทสินค้า / Prefix"
                    value="<?= htmlspecialchars($search_text) ?>">
                <button class="btn-search">ค้นหา</button>
                <?php if ($search_text !== ""): ?>
                    <a href="category_list.php" class="btn-reset">ล้าง</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ลำดับ</th>
                            <th>Prefix</th>
                            <th>ชื่อประเภทสินค้า</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories->num_rows > 0): ?>
                            <?php $i = 1;
                            while ($row = $categories->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><span
                                            style="background:#eef2f6; padding:4px 8px; border-radius:6px; font-weight:500;"><?= $row['prefix'] ?></span>
                                    </td>
                                    <td><?= $row['category_name'] ?></td>
                                    <td>
                                        <button class="btn-edit" onclick='openEditModal(<?php echo json_encode($row); ?>)'>
                                            <i class="fa-solid fa-pen"></i> แก้ไข
                                        </button>

                                        <button class="btn-delete" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            <i class="fa-solid fa-trash"></i> ลบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align:center; padding:20px;">ไม่มีข้อมูล</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h3 style="margin-top:0;">เพิ่มประเภทสินค้า</h3>
            <hr style="border:0; border-top:1px solid #eee; margin-bottom:20px;">

            <form method="post">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <label>Prefix (รหัสนำหน้า)</label>
                    <input type="text" name="prefix" class="form-control" placeholder="เช่น EL, SN" required>
                </div>

                <div class="form-group">
                    <label>ชื่อประเภทสินค้า</label>
                    <input type="text" name="category_name" class="form-control" placeholder="เช่น เครื่องใช้ไฟฟ้า"
                        required>
                </div>

                <button type="submit" class="btn-submit">บันทึกข้อมูล</button>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h3 style="margin-top:0; color:#f39c12;">แก้ไขประเภทสินค้า</h3>
            <hr style="border:0; border-top:1px solid #eee; margin-bottom:20px;">

            <form method="post">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id">

                <div class="form-group">
                    <label>Prefix (รหัสนำหน้า)</label>
                    <input type="text" id="edit_prefix" name="prefix" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>ชื่อประเภทสินค้า</label>
                    <input type="text" id="edit_name" name="category_name" class="form-control" required>
                </div>

                <button type="submit" class="btn-submit" style="background:#f1c40f; color:#000;">บันทึกการแก้ไข</button>
            </form>
        </div>
    </div>

    <script>
        // เปิด Modal เพิ่ม
        function openAddModal() {
            document.getElementById('addModal').style.display = "block";
        }

        // เปิด Modal แก้ไข (รับค่าจากปุ่มมาใส่ในฟอร์ม)
        function openEditModal(data) {
            document.getElementById('editModal').style.display = "block";
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_prefix').value = data.prefix;
            document.getElementById('edit_name').value = data.category_name;
        }

        // ปิด Modal
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

        // ปิดเมื่อคลิกพื้นหลัง
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = "none";
            }
        }

        // แจ้งเตือนลบ
        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'ข้อมูลนี้จะไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    // ลิงก์ไปยังไฟล์ลบ (ต้องมีไฟล์ category_delete.php รองรับ)
                    window.location.href = 'category_delete.php?id=' + id;
                }
            });
        }
    </script>

    <?php if (isset($_SESSION['swal'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['swal']['icon'] ?>',
                title: '<?= $_SESSION['swal']['title'] ?>',
                text: '<?= $_SESSION['swal']['text'] ?>',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        </script>
        <?php unset($_SESSION['swal']);
    endif; ?>

</body>

</html>