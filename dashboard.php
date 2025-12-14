<?php
session_start();
require_once "db.php"; // เรียกไฟล์เชื่อมต่อฐานข้อมูล

// ตรวจสอบ Login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// --- 1. หาผลรวมสินค้า (นับจำนวนรายการสินค้าทั้งหมด) ---
$sql1 = "SELECT COUNT(*) as total_items FROM products";
$result1 = mysqli_query($conn, $sql1);
$row1 = mysqli_fetch_assoc($result1);
$total_items = $row1['total_items'];

// --- 2. หาสินค้าคงเหลือ (ผลรวมจำนวนชิ้นทั้งหมด) ---
$sql2 = "SELECT SUM(quantity) as total_qty FROM products";
$result2 = mysqli_query($conn, $sql2);
$row2 = mysqli_fetch_assoc($result2);
$total_qty = $row2['total_qty'] ? $row2['total_qty'] : 0; // ถ้าไม่มีของให้เป็น 0

// --- 3. หายอดขายทั้งหมด (ดึงจากประวัติการเบิกออก 'out') ---
// สมมติ: เราจะคำนวณยอดขายคร่าวๆ จาก (จำนวนที่เบิกออก * ราคาขาย)
// หมายเหตุ: Query นี้ซับซ้อนหน่อย เพราะต้อง join ตาราง products เพื่อเอาราคาขายมาคูณ
$sql3 = "SELECT SUM(t.quantity * p.selling_price) as total_sales 
         FROM stock_transactions t 
         JOIN products p ON t.product_id = p.id 
         WHERE t.transaction_type = 'out'";
$result3 = mysqli_query($conn, $sql3);
$row3 = mysqli_fetch_assoc($result3);
$total_sales = $row3['total_sales'] ? $row3['total_sales'] : 0;

// ยอดขายวันนี้ (เพิ่มเงื่อนไขวันที่)
$sql3_today = "SELECT SUM(t.quantity * p.selling_price) as total_sales_today 
               FROM stock_transactions t 
               JOIN products p ON t.product_id = p.id 
               WHERE t.transaction_type = 'out' AND DATE(t.created_at) = CURDATE()";
$result3_today = mysqli_query($conn, $sql3_today);
$row3_today = mysqli_fetch_assoc($result3_today);
$total_sales_today = $row3_today['total_sales_today'] ? $row3_today['total_sales_today'] : 0;


// --- 4. หาสินค้าค้างสต๊อก (สินค้าที่ไม่มีการเคลื่อนไหวมานาน หรือเหลือน้อยมาก) ---
// ในที่นี้ขอปรับเป็น "สินค้าใกล้หมด" (Low Stock) แทน เพื่อให้มีประโยชน์กว่า
// นับสินค้าที่เหลือน้อยกว่า 10 ชิ้น
$sql4 = "SELECT COUNT(*) as low_stock_count FROM products WHERE quantity <= 10";
$result4 = mysqli_query($conn, $sql4);
$row4 = mysqli_fetch_assoc($result4);
$low_stock_count = $row4['low_stock_count'];

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

    <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
            <li><a href="dashboard.php" class="active"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Dashboard</span></a></li>
            <li><a href="product_list.php"><i class="fa-solid fa-box-open"></i> <span class="menu-text">ข้อมูลสินค้า</span></a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">ข้อมูลประเภทสินค้า</span></a></li>
            <li><a href="stock_in.php" ><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="stock_out.php" ><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            <li><a href="stock_adjust.php" ><i class="fa-solid fa-clipboard-check"></i> ตรวจนับ/ปรับปรุง</a></li>
            <li><a href="#"><i class="fa-solid fa-heart"></i> <span class="menu-text">สินค้ายอดนิยม</span></a></li>
            <li><a href="report_low_stock.php"><i class="fa-solid fa-triangle-exclamation"></i> <span class="menu-text">รายงานสินค้าใกล้หมด</span></a></li>
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
                <img src="img/profile.png" alt="Profile">
            </div>
        </div>

        <div class="content-container">
            <h2 class="page-title">Dashboard</h2>

            <div class="card-grid">
                
                <div class="card card-blue">
                    <div class="card-body">
                        <div class="card-title">รายการสินค้าทั้งหมด</div>
                        <div class="card-value">จำนวน <?php echo number_format($total_items); ?> รายการ</div>
                    </div>
                    <a href="product_list.php" class="card-footer">
                        ดูรายการสินค้า <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                <div class="card card-yellow">
                    <div class="card-body">
                        <div class="card-title">จำนวนชิ้นในคลัง</div>
                        <div class="card-value">รวม <?php echo number_format($total_qty); ?> ชิ้น</div>
                    </div>
                    <a href="stock_adjust.php" class="card-footer">
                        ตรวจนับสต็อก <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                <div class="card card-green">
                    <div class="card-body">
                        <div class="card-title">ยอดขายโดยประมาณ</div>
                        <div class="card-value">รวม ฿<?php echo number_format($total_sales, 2); ?></div>
                        <div class="card-sub-value">วันนี้ ฿<?php echo number_format($total_sales_today, 2); ?></div>
                    </div>
                    <a href="stock_history.php?type=out" class="card-footer">
                        ดูประวัติการขาย <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

                <div class="card card-red">
                    <div class="card-body">
                        <div class="card-title">สินค้าใกล้หมด (< 10)</div>
                        <div class="card-value">จำนวน <?php echo number_format($low_stock_count); ?> รายการ</div>
                    </div>
                    <a href="report_low_stock.php" class="card-footer">
                        ดูรายงาน <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>

            </div>
        </div>
    </div>
    <div id="logoutModal" class="modal-overlay">
    <div class="login-box logout-modal-content">
        <i class="fa-solid fa-right-from-bracket logout-icon"></i>
        <h2 class="logout-title">ยืนยันการออกจากระบบ</h2>
        <p class="logout-desc">คุณต้องการออกจากระบบใช่หรือไม่?</p>
        
        <div style="display: flex; justify-content: center; gap: 15px;">
            <button class="btn-cancel" onclick="closeLogoutModal()">ยกเลิก</button>
            <a href="logout.php" class="btn-confirm-logout">ออกจากระบบ</a>
        </div>
    </div>
</body>
</html>
