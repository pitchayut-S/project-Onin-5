<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";

// ดึงข้อมูลสินค้า + JOIN ชื่อหมวดหมู่
$sql = "
SELECT 
    p.id,
    p.product_code,
    p.name,
    c.category_name AS category_name,
    p.quantity,
    p.exp_date,
    p.image
FROM products p
LEFT JOIN product_category c ON p.category = c.id
";

if ($search_text !== "") {
    $like = "%".$search_text."%";
    $sql .= " WHERE p.product_code LIKE '$like' 
           OR p.name LIKE '$like' 
           OR c.category_name LIKE '$like'";
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

<?php include "sidebar.php"; ?>

<div class="main-content">

    <div class="top-navbar">
        <div class="nav-left"><i class="fa-solid fa-bars"></i></div>
        <div class="nav-right"><img src="img/profile.png"></div>
    </div>

    <div class="content-container">

        <div class="page-title">ข้อมูลสินค้า</div>

        <!-- ปุ่มเพิ่มสินค้า -->
        <div style="text-align:right; margin-bottom:20px;">
            <a href="product_add.php" 
               style="background:#28a745;color:white;padding:10px 18px;border-radius:10px;text-decoration:none;font-weight:600;">
               + เพิ่มสินค้า
            </a>
        </div>

        <!-- กล่องค้นหา -->
        <form class="search-box" method="get" action="product_list.php">
            <input type="text" name="search" placeholder="ค้นหา (รหัส / ชื่อ / ประเภทสินค้า)"
                   value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">
            <button type="submit" class="btn-search">ค้นหา</button>

            <?php if ($search_text !== ""): ?>
                <a href="product_list.php" class="btn-reset">ล้าง</a>
            <?php endif; ?>
        </form>

        <!-- ตารางสินค้า -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>รหัสสินค้า</th>
                    <th>รูปภาพ</th>
                    <th>ชื่อสินค้า</th>
                    <th>ประเภทสินค้า</th>
                    <th>จำนวนคงเหลือ</th>
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
                    <td><?= $i++ ?></td>
                    <td><?= $row['product_code'] ?></td>

                    <td>
                        <?php if ($row['image']): ?>
                            <img src="uploads/<?= $row['image'] ?>" class="product-img">
                        <?php else: ?>
                            <span style="color:#888;">ไม่มีรูป</span>
                        <?php endif; ?>
                    </td>

                    <td><?= $row['name'] ?></td>
                    <td><?= $row['category_name'] ?></td>

                    <td><span class="badge <?= $stock_class ?>"><?= $row['quantity'] ?> | <?= $stock_label ?></span></td>

                    <td><?= $row['exp_date'] ?></td>

                    <td>
                        <a href="products_edit.php?id=<?= $row['id'] ?>" class="btn-edit">แก้ไข</a>
                        <a href="products_delete.php?id=<?= $row['id'] ?>" class="btn-delete"
                           onclick="return confirm('ต้องการลบสินค้านี้ใช่หรือไม่?');">ลบ</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8" style="text-align:center; padding:20px;">ไม่มีข้อมูลสินค้า</td></tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

</body>
</html>
