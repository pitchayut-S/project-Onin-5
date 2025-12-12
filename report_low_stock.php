<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// รับค่าเกณฑ์ขั้นต่ำจาก URL (ถ้าไม่มีให้ใช้ค่าเริ่มต้น 10)
$threshold = isset($_GET['min']) ? intval($_GET['min']) : 10;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานสินค้าใกล้หมด - Onin Shop Stock</title>
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

            <li><a href="report_low_stock.php" class="active"><i class="fa-solid fa-triangle-exclamation"></i> รายงานสินค้าใกล้หมด</a></li>
            <li><a href="stock_history.php"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติสต็อก</a></li>
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
                <h2 class="page-title" style="margin-bottom:0;">รายงานสินค้าใกล้หมด</h2>
                
                <button onclick="window.print()" class="btn-print">
                    <i class="fa-solid fa-print"></i> พิมพ์รายงาน
                </button>
            </div>

            <div class="search-box" style="align-items: center; background-color: #fff3cd; border: 1px solid #ffeeba;">
                <i class="fa-solid fa-filter" style="color: #856404; margin-right: 10px;"></i>
                <span style="color: #856404; font-weight: 500;">แสดงสินค้าที่มีจำนวนน้อยกว่า: </span>
                
                <form method="GET" style="display: flex; gap: 10px; margin-left: 10px;">
                    <input type="number" name="min" value="<?php echo $threshold; ?>" 
                           style="width: 80px; text-align: center; padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                    <button type="submit" class="btn-search" style="padding: 5px 15px;">ตกลง</button>
                </form>
            </div>

            <div class="table-container">
                <div style="display: none; margin-bottom: 10px;" class="print-only-show">
                    <p><strong>วันที่ออกรายงาน:</strong> <?php echo date("d/m/Y H:i"); ?></p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>ราคาต้นทุน</th>
                            <th>คงเหลือ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // ดึงข้อมูลสินค้าที่น้อยกว่าหรือเท่ากับเกณฑ์ที่กำหนด
                        $sql = "SELECT * FROM products WHERE quantity <= $threshold ORDER BY quantity ASC";
                        $result = mysqli_query($conn, $sql);
                        $count = 0;

                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                $count++;
                                
                                // กำหนดสถานะความเร่งด่วน
                                $qty = $row['quantity'];
                                if ($qty == 0) {
                                    $status = "<span style='color:red; font-weight:bold;'>สินค้าหมด</span>";
                                    $row_style = "background-color: #ffe6e6;"; // สีพื้นหลังแดงอ่อนๆ
                                } else {
                                    $status = "<span style='color:#e67e22; font-weight:bold;'>ใกล้หมด</span>";
                                    $row_style = "";
                                }

                                echo "<tr style='$row_style'>";
                                echo "<td>" . $row['product_code'] . "</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td>" . number_format($row['cost_price'], 2) . "</td>";
                                echo "<td style='color:red; font-weight:bold; font-size:16px;'>" . $qty . " " . $row['unit'] . "</td>";
                                echo "<td>" . $status . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #28a745;'>
                                    <i class='fa-solid fa-circle-check' style='font-size: 40px; margin-bottom: 10px; display:block;'></i>
                                    สต็อกสินค้าปกติ (ไม่มีสินค้าต่ำกว่าเกณฑ์ $threshold ชิ้น)
                                  </td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
                
                <p style="margin-top: 20px; color: #666;">
                    รวมรายการสินค้าที่ต้องสั่งซื้อ: <strong><?php echo $count; ?></strong> รายการ
                </p>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .print-only-show { display: block !important; }
        }
    </style>

    <script>
        // ... Script Logout (ถ้ามี) ...
    </script>

</body>
</html>