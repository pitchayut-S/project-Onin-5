<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$products = [];

// $base_sql = "SELECT id, product_code, name, category, unit, selling_price, quantity FROM products";
// $order_by = " ORDER BY id DESC";

// if ($search_text !== "") {
//     $like = "%" . $search_text . "%";
//     $stmt = $conn->prepare($base_sql . " WHERE product_code LIKE ? OR name LIKE ? OR category LIKE ?" . $order_by);
//     $stmt->bind_param("sss", $like, $like, $like);
//     $stmt->execute();
//     $products = $stmt->get_result();
//     $stmt->close();
// } else {
//     $products = $conn->query($base_sql . $order_by);
// }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้า - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        /* กล่องค้นหา + ตาราง */
        .content-container { padding: 30px; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; color: #333; }
        .search-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 10px;
            display: flex;
            gap: 10px;
            align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            margin-bottom: 16px;
        }
        .search-box input {
            flex: 1;
            border: none;
            background: #eef2f6;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }
        .btn-search,
        .btn-reset {
            border: none;
            cursor: pointer;
            border-radius: 8px;
            padding: 11px 18px;
            font-weight: 600;
            font-size: 14px;
        }
        .btn-search { background: #356CB5; color: #fff; display: inline-flex; align-items: center; gap: 8px; }
        .btn-search:hover { background: #285291; }
        .btn-reset { background: #e7ebf0; color: #333; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-reset:hover { background: #d8dde4; }
        .table-container {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.04);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 720px; }
        th, td { padding: 14px 12px; text-align: left; border-bottom: 1px solid #eef1f4; }
        th { background: #f3f6fb; color: #333; font-weight: 600; }
        tr:hover td { background: #f9fbff; }
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 12px;
            background: #e7f1ff;
            color: #356CB5;
            font-weight: 600;
            font-size: 12px;
        }
        .stock-ok { color: #1b9c5a; background: #e6f6ed; }
        .stock-low { color: #c0392b; background: #fdecea; }
    </style>
</head>
<body>
    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <div class="top-navbar">
            <div class="nav-left">
                <i class="fa-solid fa-bars"></i>
            </div>
            <div class="nav-right">
                <img src="img/profile.png" alt="Profile">
            </div>
        </div>

        <div class="content-container">
            <div class="page-title">รายงาน</div>

            <form class="search-box" method="get" action="product.php">
                <input type="text" name="search" placeholder="พิมพ์เพื่อค้นหาสินค้า (รหัส / ชื่อ / ประเภท)" value="<?= htmlspecialchars($search_text, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i> ค้นหา</button>
                <?php if ($search_text !== ""): ?>
                    <a class="btn-reset" href="product.php"><i class="fa-solid fa-rotate-left"></i> ล้างการค้นหา</a>
                <?php endif; ?>
            </form>

        </div>
    </div>
</body>
</html>
