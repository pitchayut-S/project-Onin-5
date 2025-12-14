<?php
session_start();
require_once "db.php";

// ตั้งค่าเริ่มต้นเมื่อเข้าระบบครั้งแรก
if (!isset($_SESSION['first_login'])) {
    $_SESSION['first_login'] = true;
}

// ถ้าเป็นการเข้าใช้งานครั้งแรกใน session
if ($_SESSION['first_login'] === true) {

    // ตรวจสอบสินค้าใกล้หมด (<=10 ชิ้น)
    $low = $conn->query("SELECT COUNT(*) AS total FROM products WHERE quantity > 0 AND quantity <= 10");
    $row = $low->fetch_assoc();

    if ($row['total'] > 0) {

        echo "
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'สินค้าใกล้หมด!',
                text: 'มีสินค้าใกล้หมดจำนวน {$row['total']} รายการ',
                confirmButtonText: 'รับทราบ',
                confirmButtonColor: '#f1c40f'
            });
        </script>";
    }

    // ปิดการแจ้งเตือนหลังแสดงแล้ว
    $_SESSION['first_login'] = false;
}

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// รับค่าจาก GET
$search_text = isset($_GET['search']) ? trim($_GET['search']) : "";
$filter = isset($_GET['filter']) ? $_GET['filter'] : "all";

// Base SQL
$sql = "
SELECT 
    p.id,
    p.product_code,
    p.name,
    c.category_name,
    p.quantity,
    p.exp_date,
    p.image
FROM products p
LEFT JOIN product_category c ON p.category = c.id
WHERE 1
";

//  เงื่อนไขค้นหา
if ($search_text !== "") {
    $like = "%" . $conn->real_escape_string($search_text) . "%";
    $sql .= " AND (p.product_code LIKE '$like'
              OR p.name LIKE '$like'
              OR c.category_name LIKE '$like')";
}

//  เงื่อนไข Filter แยกชัดเจน
if ($filter === "lowstock") {
    // สินค้าใกล้หมด (1–10)
    $sql .= " AND p.quantity > 0 AND p.quantity <= 10";
}

if ($filter === "outofstock") {
    // สินค้าหมด (0 ชิ้น)
    $sql .= " AND p.quantity = 0";
}

if ($filter === "expired") {
    // หมดอายุ
    $sql .= " AND p.exp_date < CURDATE()";
}

$sql .= " ORDER BY p.id DESC";
$products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สต๊อกสินค้า - Onin Shop Stock</title>
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> -->
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">


<style>
/* --- Styling เดิม --- */
.content-container { padding: 30px; }
.page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

.filter-buttons { display:flex; gap:10px; margin-bottom:20px; }
.filter-buttons a {
    padding:10px 18px; border-radius:10px;
    text-decoration:none; font-weight:600; color:white;
}
.btn-all { background:#3498db; }
.btn-low { background:#f1c40f; color:black; }
.btn-out { background:#7f8c8d; }
.btn-expired { background:#e74c3c; }

.filter-buttons a.active { box-shadow:0 0 0 3px rgba(0,0,0,0.15); }

.search-box {
    background:#fff; padding:18px 20px; border-radius:14px;
    display:flex; gap:10px; align-items:center;
    margin-bottom:20px;
    box-shadow:0 4px 15px rgba(0,0,0,0.05);
}
.search-box input {
    flex:1; border:none; background:#eef2f6;
    padding:12px 14px; border-radius:10px;
}
.btn-search { background:#356CB5; padding:10px 18px; border-radius:10px; color:white; border:none; }

table {
    width:100%; border-collapse:separate; border-spacing:0;
    background:white; border-radius:14px;
    box-shadow:0 4px 15px rgba(0,0,0,0.05);
}
th, td { padding:14px 12px; border-bottom:1px solid #eee; }
th { background:#f3f6fb; font-weight:600; }

.product-img {
    width:65px; height:65px; object-fit:cover;
    border-radius:10px; border:1px solid #ccc;
}

.badge {
    padding:6px 12px; border-radius:20px;
    font-size:12px; font-weight:600;
}
.stock-normal { background:#e6f6ed; color:#1b9c5a; }
.stock-low { background:#fdecea; color:#c0392b; }
.stock-warn { background:#f9e79f; color:#af601a; }

.btn-adjust {
    background:#3498db; padding:7px 12px;
    border-radius:8px; color:white; text-decoration:none;
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

        <div class="page-title">สต๊อกสินค้า</div>

        <!--  ปุ่ม Filter -->
        <div class="filter-buttons">
            <a href="product_Stock.php?filter=all" class="btn-all <?= $filter=='all'?'active':'' ?>">สินค้าทั้งหมด</a>

            <a href="product_Stock.php?filter=lowstock" class="btn-low <?= $filter=='lowstock'?'active':'' ?>">สินค้าใกล้หมด</a>

            <a href="product_Stock.php?filter=outofstock" class="btn-out <?= $filter=='outofstock'?'active':'' ?>">สินค้าหมด</a>

            <a href="product_Stock.php?filter=expired" class="btn-expired <?= $filter=='expired'?'active':'' ?>">สินค้าหมดอายุ</a>
        </div>

        <!-- 🔍 ช่องค้นหา -->
        <form class="search-box" method="get" action="product_Stock.php">
            <input type="hidden" name="filter" value="<?= $filter ?>">
            <input type="text" name="search" placeholder="ค้นหา (ชื่อ / รหัส / ประเภท)" 
                   value="<?= htmlspecialchars($search_text, ENT_QUOTES) ?>">
            <button type="submit" class="btn-search">ค้นหา</button>
        </form>

        <!-- ตารางสินค้า -->
        <table>
            <thead>
                <tr>
                    <th>#</th>
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

<?php
if ($products->num_rows > 0):
    $i = 1;
    while ($row = $products->fetch_assoc()):

        //  Badge ตรงกับ Filter
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

        $expired = ($row['exp_date'] < date("Y-m-d"));
?>
                <tr>
                    <td><?= $i++ ?></td>

                    <td>
                        <?php if ($row['image']): ?>
                            <img src="uploads/<?= $row['image'] ?>" class="product-img">
                        <?php else: ?>
                            <span style="color:#777;">ไม่มีรูป</span>
                        <?php endif; ?>
                    </td>

                    <td><?= $row['product_code'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['category_name'] ?></td>

                    <td>
                        <span class="badge <?= $badge ?>">
                            <?= $row['quantity'] ?> | <?= $label ?>
                        </span>
                    </td>

                    <td style="<?= $expired ? 'color:red;font-weight:bold;' : '' ?>">
                        <?= $row['exp_date'] ?>
                    </td>

                    <td><a href="stock_form.php?id=<?= $row['id'] ?>" class="btn-adjust">ปรับสต๊อก</a></td>
                </tr>

<?php
    endwhile;
else:
?>
                <tr><td colspan="8" style="text-align:center;">ไม่มีข้อมูลสินค้า</td></tr>
<?php endif; ?>

            </tbody>
        </table>

    </div>
</div>

</body>
</html>
