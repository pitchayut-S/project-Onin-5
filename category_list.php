<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";

// Query ประเภทสินค้า
$sql = "SELECT id, category_name, prefix FROM product_category";

if ($search_text !== "") {
    $like = "%".$search_text."%";
    $sql .= " WHERE category_name LIKE '$like' OR prefix LIKE '$like'";
}

$sql .= " ORDER BY id DESC";

$categories = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประเภทสินค้า - Onin Shop Stock</title>

    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; font-family: "Prompt"; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

        /* Search Box */
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

        .btn-search {
            background:#356CB5;
            color:white;
            padding:10px 18px;
            border-radius:10px;
            border:none;
            cursor:pointer;
            font-weight:600;
        }
        .btn-reset {
            background:#e7ebf0;
            padding:10px 16px;
            border-radius:10px;
            text-decoration:none;
            color:#333;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background:#fff;
            border-radius: 14px;
            overflow: hidden;
            min-width: 700px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        th, td {
            padding:14px 12px;
            border-bottom:1px solid #eee;
        }
        th { background:#f3f6fb; font-weight:600; }
        tr:last-child td { border-bottom: none; }

        /* Buttons */
        .btn-add {
            background:#28a745;
            color:white;
            padding:10px 18px;
            border-radius:10px;
            text-decoration:none;
            font-weight:600;
        }
        .btn-edit {
            background:#f1c40f;
            padding:6px 12px;
            border-radius:8px;
            color:white;
            text-decoration:none;
        }
        .btn-delete {
            background:#e74c3c;
            padding:6px 12px;
            border-radius:8px;
            color:white;
            text-decoration:none;
        }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">

    <div class="top-navbar">
        <div class="nav-left"><i class="fa-solid fa-bars"></i></div>
        <div class="nav-right"><img src="img/profile.png"></div>
    </div>

    <div class="content-container">

        <div class="page-title">ข้อมูลประเภทสินค้า</div>

        <!-- ปุ่มเพิ่ม -->
        <div style="text-align:right; margin-bottom:20px;">
            <a href="category_add.php" class="btn-add">+ เพิ่มประเภทสินค้า</a>
        </div>

        <!-- Search -->
        <form class="search-box" method="get" action="category_list.php">
            <input type="text" name="search" placeholder="ค้นหาชื่อประเภทสินค้า / Prefix"
                   value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">
            <button type="submit" class="btn-search">ค้นหา</button>

            <?php if ($search_text !== ""): ?>
                <a href="category_list.php" class="btn-reset">ล้าง</a>
            <?php endif; ?>
        </form>

        <!-- ตาราง -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>รหัสย่อสินค้า</th>
                    <th>ชื่อประเภทสินค้า</th>
                    <th>จัดการ</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($categories->num_rows > 0): ?>
                    <?php $i = 1; while ($row = $categories->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $row['prefix'] ?></td>
                        <td><?= $row['category_name'] ?></td>
                        <td>
                            <a href="category_edit.php?id=<?= $row['id'] ?>" class="btn-edit">แก้ไข</a>
                            <a href="category_delete.php?id=<?= $row['id'] ?>" class="btn-delete"
                               onclick="return confirm('ต้องการลบประเภทสินค้านี้หรือไม่?');">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; padding:20px;">ไม่มีข้อมูลประเภทสินค้า</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>
