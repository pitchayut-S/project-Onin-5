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
    <title>Dashboard - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        /* --- ตั้งค่าพื้นฐาน --- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Prompt', sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: #E5E5E5; /* สีพื้นหลังเทาอ่อนๆ ตามรูป */
        }

        /* --- Sidebar (เมนูด้านซ้าย) --- */
        .sidebar {
            width: 250px;
            background-color: #356CB5; /* สีน้ำเงินหลัก */
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .sidebar-header { padding: 20px; font-size: 20px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); }

        .menu-list {
            list-style: none;
            flex-grow: 1; /* ดันเนื้อหาที่เหลือลงล่าง */
            padding-top: 10px;
        }

        .menu-list li a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 16px;
            transition: 0.3s;
        }

        .menu-list li a:hover, .menu-list li a.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border-left: 4px solid white;
        }

        .menu-list li a i {
            width: 30px; /* จัดระยะห่างไอคอน */
            font-size: 18px;
        }

        /* เมนูส่วนล่าง (บัญชี / ออกจากระบบ) */
        .sidebar-footer {
            margin-top: auto;   /* ดันลงล่างสุด (จะทำงานได้เมื่อมีที่ว่าง) */
            flex-grow: 0 !important; /* สำคัญ! บังคับไม่ให้กล่องนี้ยืดแย่งพื้นที่กับข้างบน */
            width: 100%;
        }
        
        .btn-logout {
            background-color: #D90429; /* สีแดง */
            color: white !important;
        }
        .btn-logout:hover { background-color: #b0021f; }

        /* --- Main Content (เนื้อหาหลัก) --- */
        .main-content {
            margin-left: 250px; /* เว้นที่ให้ Sidebar */
            width: calc(100% - 250px);
            display: flex;
            flex-direction: column;
        }

        /* Navbar ด้านบน */
        .top-navbar {
            height: 60px;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .nav-left i {
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        .nav-right img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        /* พื้นที่ Dashboard */
        .content-container {
            padding: 30px;
        }

        .page-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        /* Grid ของการ์ด */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr); /* แบ่ง 4 คอลัมน์ */
            gap: 20px;
        }

        /* ตัวการ์ด */
        .card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 160px;
            border: 1px solid #ddd;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .card-value {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }
        
        .card-sub-value {
            font-size: 14px;
            color: #888;
        }

        /* Footer ของการ์ด (แถบสีด้านล่าง) */
        .card-footer {
            color: white;
            padding: 8px 15px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            text-decoration: none;
        }

        /* สีของการ์ดแต่ละใบ */
        .card-blue .card-footer { background-color: #3544C4; } /* น้ำเงินเข้ม */
        .card-yellow .card-footer { background-color: #D4AF37; } /* เหลืองทอง */
        .card-green .card-footer { background-color: #1DA828; } /* เขียว */
        .card-red .card-footer { background-color: #C70000; } /* แดง */

        /* Responsive */
        @media (max-width: 1024px) {
            .card-grid { grid-template-columns: repeat(2, 1fr); } /* จอเล็กลง เหลือ 2 คอลัมน์ */
        }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar-header, .menu-text { display: none; } /* ย่อเมนู */
            .menu-list li a { justify-content: center; padding: 15px 0; }
            .menu-list li a i { width: auto; font-size: 24px; }
            .main-content { margin-left: 70px; width: calc(100% - 70px); }
            .card-grid { grid-template-columns: 1fr; } /* มือถือ เหลือ 1 คอลัมน์ */
        }
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
                <?php $user_display = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-weight:500; color:#333;"><?php echo $user_display; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_display); ?>&background=0D8ABC&color=fff" alt="User Profile">
                </div>
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
</div>

<script>
    // ฟังก์ชันเปิด Popup Logout
    function confirmLogout() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    // ฟังก์ชันปิด Popup Logout
    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }
</script>

</body>
</html>