<?php
session_start();
require_once "db.php";

<<<<<<< HEAD
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// รับค่าตัวกรองจาก URL (ถ้าไม่มีให้เป็นค่าว่าง)
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$date_start = isset($_GET['start']) ? $_GET['start'] : '';
$date_end = isset($_GET['end']) ? $_GET['end'] : '';

=======
if (!isset($_GET['id'])) {
    die("ไม่พบสินค้า");
}

$id = intval($_GET['id']);

$sql_product = "SELECT name FROM products WHERE id = $id";
$product = $conn->query($sql_product)->fetch_assoc();

$sql_history = "
SELECT * FROM stock_history 
WHERE product_id = $id 
ORDER BY id DESC
";
$history = $conn->query($sql_history);
>>>>>>> upadate
?>

<!DOCTYPE html>
<html lang="th">
<head>
<<<<<<< HEAD
    <meta charset="UTF-8">
    <title>ประวัติการเคลื่อนไหวสต็อก - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
            <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="product_list.php"><i class="fa-solid fa-box-open"></i> ข้อมูลสินค้า</a></li>
            <li><a href="stock_in.php"><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="stock_out.php"><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            <li><a href="stock_adjust.php"><i class="fa-solid fa-clipboard-check"></i> ตรวจนับ/ปรับปรุง</a></li>
            
            <li><a href="report_low_stock.php"><i class="fa-solid fa-triangle-exclamation"></i> รายงานสินค้าใกล้หมด</a></li>
            <li><a href="stock_history.php" class="active"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติสต็อก</a></li>
        </ul>
        <div class="sidebar-footer menu-list">
            <li><a href="#"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">การจัดการบัญชี</span></a></li>
            <li><a href="index.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i> <span class="menu-text">ออกจากระบบ</span></a></li>
        </div>
    </nav>

   <div class="main-content">
        <div class="top-navbar">
            <div class="nav-left">
                <i class="fa-solid fa-bars"></i>
            </div>
            <div class="nav-right">
                <?php $user_display = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-weight:500; color:#333;"><?php echo $user_display; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_display); ?>&background=0D8ABC&color=fff" alt="User Profile">
                </div>
            </div>
        </div>

        <div class="content-container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="page-title" style="margin-bottom:0;">ประวัติการเคลื่อนไหว (Stock History)</h2>
                <button onclick="window.print()" class="btn-print">
                    <i class="fa-solid fa-print"></i> พิมพ์รายงาน
                </button>
            </div>

            <div class="search-box" style="flex-wrap: wrap; gap: 15px; align-items: flex-end;">
                <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                    
                    <div style="flex: 1; min-width: 200px;">
                        <label style="font-size: 14px; color: #666;">ค้นหาสินค้า:</label>
                        <input type="text" name="search" class="search-input" placeholder="รหัส หรือ ชื่อสินค้า..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>" style="margin-top: 5px; width: 100%;">
                    </div>

                    <div>
                        <label style="font-size: 14px; color: #666;">ประเภทรายการ:</label>
                        <select name="type" style="display: block; margin-top: 5px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                            <option value="all" <?php echo ($type_filter == 'all') ? 'selected' : ''; ?>>ทั้งหมด</option>
                            <option value="in" <?php echo ($type_filter == 'in') ? 'selected' : ''; ?>>รับเข้า (In)</option>
                            <option value="out" <?php echo ($type_filter == 'out') ? 'selected' : ''; ?>>เบิกออก (Out)</option>
                        </select>
                    </div>

                    <div>
                        <label style="font-size: 14px; color: #666;">ตั้งแต่วันที่:</label>
                        <input type="date" name="start" value="<?php echo $date_start; ?>" style="display: block; margin-top: 5px; padding: 9px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>

                    <div>
                        <label style="font-size: 14px; color: #666;">ถึงวันที่:</label>
                        <input type="date" name="end" value="<?php echo $date_end; ?>" style="display: block; margin-top: 5px; padding: 9px; border: 1px solid #ccc; border-radius: 5px;">
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn-search" style="height: 42px;">ค้นหา</button>
                        <a href="stock_history.php" style="margin-left: 10px; color: #666; text-decoration: none; padding-bottom: 10px; font-size: 14px;">ล้างค่า</a>
                    </div>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>วัน/เวลา</th>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>ประเภท</th>
                            <th>จำนวน</th>
                            <th>หมายเหตุ/สาเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // สร้าง SQL Query ตามตัวกรองที่เลือก
                        $sql = "SELECT t.*, p.product_code, p.name as product_name, p.unit 
                                FROM stock_transactions t 
                                LEFT JOIN products p ON t.product_id = p.id 
                                WHERE 1=1 "; // ใช้ 1=1 เพื่อให้ต่อ AND ได้ง่าย

                        // กรองตามคำค้นหา
                        if (isset($_GET['search']) && !empty($_GET['search'])) {
                            $s = $_GET['search'];
                            $sql .= " AND (p.name LIKE '%$s%' OR p.product_code LIKE '%$s%') ";
                        }

                        // กรองตามประเภท
                        if ($type_filter != 'all') {
                            $sql .= " AND t.transaction_type = '$type_filter' ";
                        }

                        // กรองตามวันที่
                        if (!empty($date_start)) {
                            $sql .= " AND DATE(t.created_at) >= '$date_start' ";
                        }
                        if (!empty($date_end)) {
                            $sql .= " AND DATE(t.created_at) <= '$date_end' ";
                        }

                        // เรียงลำดับจากล่าสุดไปเก่าสุด
                        $sql .= " ORDER BY t.created_at DESC";

                        $result = mysqli_query($conn, $sql);

                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                // แปลงวันที่เป็นไทย
                                $dt = strtotime($row['created_at']);
                                $date_th = date("d/m/", $dt) . (date("Y", $dt) + 543) . " " . date("H:i", $dt);

                                echo "<tr>";
                                echo "<td style='color:#666; font-size:14px;'>" . $date_th . "</td>";
                                echo "<td>" . $row['product_code'] . "</td>";
                                echo "<td>" . $row['product_name'] . "</td>";
                                
                                // แสดงป้ายสถานะ (Badge)
                                if ($row['transaction_type'] == 'in') {
                                    echo "<td><span class='badge bg-in'>รับเข้า</span></td>";
                                    echo "<td style='color:#28a745; font-weight:bold;'>+" . $row['quantity'] . " " . $row['unit'] . "</td>";
                                } else {
                                    echo "<td><span class='badge bg-out'>เบิกออก</span></td>";
                                    echo "<td style='color:#dc3545; font-weight:bold;'>-" . $row['quantity'] . " " . $row['unit'] . "</td>";
                                }

                                echo "<td>" . $row['note'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center;'>ไม่พบประวัติการทำรายการในช่วงเวลานี้</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
       // ... Script Logout (ถ้ามี) ...
    </script>
</body>
</html>
=======
<meta charset="UTF-8">
<title>ประวัติสต๊อก</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;500&display=swap" rel="stylesheet">

<style>
body{ font-family:"Prompt",sans-serif; background:#f4f6fb; }
.container{
    width:900px; margin:40px auto; background:#fff;
    padding:25px; border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}
table{
    width:100%; border-collapse:collapse; margin-top:20px;
}
th,td{
    padding:12px; border-bottom:1px solid #eee;
}
th{
    background:#f3f6fb; font-weight:600;
}
.badge{
    padding:6px 12px; border-radius:12px; font-size:12px; font-weight:bold;
}
.inc{ background:#e6f6ed; color:#27ae60; }
.dec{ background:#fdecea; color:#c0392b; }
</style>
</head>

<body>

<div class="container">

<h2>ประวัติสต๊อกของสินค้า: <?= $product['name'] ?></h2>

<table>
    <thead>
        <tr>
            <th>ประเภท</th>
            <th>ก่อนหน้า</th>
            <th>เปลี่ยน</th>
            <th>หลังปรับ</th>
            <th>หมายเหตุ</th>
            <th>วันที่</th>
        </tr>
    </thead>
    <tbody>
    <?php while($row = $history->fetch_assoc()): ?>
        <tr>
            <td>
                <span class="badge <?= $row['action_type']=='increase'?'inc':'dec' ?>">
                    <?= $row['action_type']=='increase' ? 'เพิ่ม' : 'ลด' ?>
                </span>
            </td>
            <td><?= $row['old_qty'] ?></td>
            <td><?= $row['change_qty'] ?></td>
            <td><?= $row['new_qty'] ?></td>
            <td><?= $row['note'] ?></td>
            <td><?= $row['created_at'] ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<br>
<a href="product_Stock.php">← กลับหน้า สต๊อกสินค้า</a>

</div>

</body>
</html>
>>>>>>> upadate
