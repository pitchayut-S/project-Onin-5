<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// --------------------- Summary ---------------------

// สินค้าทั้งหมด
$total_products = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];

// สินค้าคงเหลือรวม
$total_qty = $conn->query("SELECT SUM(quantity) AS total FROM products")->fetch_assoc()['total'];

// สินค้าใกล้หมด (<=10)
$low_stock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity <= 10")->fetch_assoc()['total'];

// สินค้าหมดอายุ
$expired_products = $conn->query("SELECT COUNT(*) AS total FROM products WHERE exp_date < CURDATE()")->fetch_assoc()['total'];

// ยอดขายวันนี้ (Mock)
$today_sales = 1200;
$month_sales = 12000;

// --------------------- Query Table ---------------------
$sql = "
SELECT p.id, p.product_code, p.name, p.quantity, p.selling_price, p.exp_date, 
       c.category_name 
FROM products p
LEFT JOIN product_category c ON p.category = c.id
WHERE 1
";

if ($search !== "") {
    $like = "%" . $conn->real_escape_string($search) . "%";
    $sql .= " AND (p.product_code LIKE '$like' 
               OR p.name LIKE '$like'
               OR c.category_name LIKE '$like')";
}

$sql .= " ORDER BY p.id DESC";

$items = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>รายงาน - Onin Shop Stock</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
body { font-family: "Prompt", sans-serif; }

.content-container { padding: 30px; }
.page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

/* Summary Box */
.summary-box {
    display: flex;
    gap: 20px;
    margin-bottom: 25px;
}
.box {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.box h3 { margin: 0; font-size: 18px; }
.box .number { font-size: 26px; font-weight: 700; margin-top: 5px; }
.box small { color: gray; }

/* Search Box */
.search-box {
    background:#fff;
    padding:18px 20px;
    border-radius:14px;
    display:flex;
    gap:10px;
    align-items:center;
    margin-bottom:20px;
    box-shadow:0 4px 15px rgba(0,0,0,0.05);
}

.search-box input {
    flex:1;
    border:none;
    background:#eef2f6;
    padding:12px 14px;
    border-radius:10px;
    font-size:14px;
}

.btn-search {
    background:#356CB5;
    color:white;
    padding:10px 18px;
    border:none;
    border-radius:10px;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
}

.btn-search:hover {
    background:#285291;
}

.btn-reset {
    background:#e7ebf0;
    color:#333;
    padding:10px 18px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

.btn-reset:hover {
    background:#d8dde4;
}

/* Table */
table {
    width:100%; border-collapse:collapse;
    background:white; border-radius:12px; overflow:hidden;
    box-shadow:0 3px 10px rgba(0,0,0,0.1);
}
th, td { padding:12px; border-bottom:1px solid #eee; }
th { background:#f3f6fb; }

/* Status Tag */
.tag {
    padding:6px 12px; border-radius:10px; color:white; font-size:13px;
}
.green { background:#2ecc71; }
.yellow { background:#f1c40f; color:black; }
.red { background:#e74c3c; }

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

    <div class="page-title">รายงาน</div>

    <!-- Summary -->
    <div class="summary-box">
        <div class="box">
            <h3>ยอดขายวันนี้</h3>
            <div class="number">฿ <?= number_format($today_sales) ?></div>
            <small>+ 12 %</small>
        </div>

        <div class="box">
            <h3>สินค้าคงเหลือ</h3>
            <div class="number"><?= $total_products ?> รายการ</div>
            <small>จำนวนสินค้าทั้งหมด <?= $total_qty ?> ชิ้น</small>
        </div>

        <div class="box">
            <h3>สินค้าใกล้หมด</h3>
            <div class="number"><?= $low_stock ?> รายการ</div>
            <small style="color:red;">ต้องสั่งเพิ่ม</small>
        </div>

        <div class="box">
            <h3>ยอดขายทั้งเดือน</h3>
            <div class="number">฿ <?= number_format($month_sales) ?></div>
            <small>+ 10 %</small>
        </div>
    </div>

    <!-- 🔍 ช่องค้นหา -->
<form class="search-box" method="get">
    
    <input type="text" 
           name="search" 
           placeholder="พิมพ์เพื่อค้นหาสินค้า (ชื่อ / รหัส / ประเภท)"
           value="<?= htmlspecialchars($search, ENT_QUOTES) ?>">

    <button type="submit" class="btn-search">
        <i class="fa-solid fa-magnifying-glass"></i> ค้นหา
    </button>

    <?php if ($search !== ""): ?>
        <a href="Report.php" class="btn-reset">
            <i class="fa-solid fa-rotate-left"></i> ล้าง
        </a>
    <?php endif; ?>
</form>


    <!-- Table -->
    <table>
        <thead>
            <tr>
                <th>รหัสสินค้า</th>
                <th>ชื่อสินค้า</th>
                <th>ประเภท</th>
                <th>จำนวนคงเหลือ</th>
                <th>สถานะ</th>
                <th>วันหมดอายุ</th>
            </tr>
        </thead>

        <tbody>
<?php while ($row = $items->fetch_assoc()): ?>

<?php
// Determine Status
if ($row['quantity'] == 0) {
    $status = "<span class='tag red'>หมด</span>";
} elseif ($row['quantity'] <= 10) {
    $status = "<span class='tag yellow'>ใกล้หมด</span>";
} else {
    $status = "<span class='tag green'>เพียงพอ</span>";
}

// Expired?
$expired = ($row['exp_date'] < date("Y-m-d"));
?>
<tr>
    <td><?= $row['product_code'] ?></td>
    <td><?= $row['name'] ?></td>
    <td><?= $row['category_name'] ?></td>
    <td><?= $row['quantity'] ?></td>
    <td><?= $status ?></td>
    <td style="<?= $expired?'color:red;font-weight:bold;':'' ?>">
        <?= $row['exp_date'] ?>
    </td>
</tr>

<?php endwhile; ?>
        </tbody>
    </table>

</div>
</div>
</body>
</html>
