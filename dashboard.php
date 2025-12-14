<?php
session_start();
require_once "db.php";

// เช็คสิทธิ์ Login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// =========================================================
// PART 1: ข้อมูลการ์ด 4 ใบ (Cards Data)
// =========================================================

// 1. ผลรวมสินค้า
$sql_prod_count = "SELECT COUNT(*) as total_items FROM products";
$total_products = $conn->query($sql_prod_count)->fetch_assoc()['total_items'];

// 2. สินค้าคงเหลือทั้งหมด
$sql_stock_sum = "SELECT SUM(quantity) as total_qty FROM products";
$total_stock_qty = $conn->query($sql_stock_sum)->fetch_assoc()['total_qty'] ?? 0;

// 3. ยอดขาย
$sql_sales_all = "
    SELECT SUM(t.amount * p.selling_price) as grand_total
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.type = 'reduce' AND t.reason = 'ขายหน้าร้าน'
";
$total_sales_all = $conn->query($sql_sales_all)->fetch_assoc()['grand_total'] ?? 0;

$sql_sales_today = "
    SELECT SUM(t.amount * p.selling_price) as today_total
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.type = 'reduce' AND t.reason = 'ขายหน้าร้าน' AND DATE(t.created_at) = CURDATE()
";
$total_sales_today = $conn->query($sql_sales_today)->fetch_assoc()['today_total'] ?? 0;

// 4. สินค้าใกล้หมด
$sql_low = "SELECT COUNT(*) as low_count FROM products WHERE quantity <= 10";
$low_stock_count = $conn->query($sql_low)->fetch_assoc()['low_count'];


// =========================================================
// PART 2: ข้อมูลกราฟและตาราง (Graph & Activity Data)
// =========================================================

// 5. กราฟสัดส่วนหมวดหมู่
$sql_cat_chart = "
    SELECT c.category_name, COUNT(p.id) as count
    FROM products p
    LEFT JOIN product_category c ON p.category = c.id
    GROUP BY p.category
";
$res_cat = $conn->query($sql_cat_chart);
$cat_labels = [];
$cat_data = [];
while($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['category_name'] ?? 'ไม่ระบุ';
    $cat_data[] = $row['count'];
}

// 6. ความเคลื่อนไหวล่าสุด (5 รายการ)
$sql_recent = "
    SELECT t.*, p.product_code, p.name 
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC LIMIT 5
";
$res_recent = $conn->query($sql_recent);

// 7. สินค้าใกล้หมดอายุ (ภายใน 30 วัน)
$sql_exp = "
    SELECT name, exp_date, DATEDIFF(exp_date, CURDATE()) as days_left 
    FROM products 
    WHERE exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)111111111111
    ORDER BY exp_date ASC LIMIT 5
";
$res_exp = $conn->query($sql_exp);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; font-family: 'Prompt', sans-serif; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 25px; color: #2c3e50; }

        /* --- Grid Layout การ์ด 4 ใบ --- */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        /* Card Design */
        .stat-card {
            background: #fff; border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e0e0e0;
            display: flex; flex-direction: column; height: 170px; /* เพิ่มความสูงการ์ดรวมนิดนึง */
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }

        .stat-content { padding: 20px; flex: 1; }
        .stat-title { font-size: 18px; font-weight: 700; color: #555; margin-bottom: 5px; }
        .stat-value { font-size: 24px; font-weight: 600; color: #333; }
        .stat-sub { font-size: 14px; color: #888; }
        
        /* --- ปรับแต่งปุ่ม Footer ให้สูงขึ้น --- */
        .stat-footer {
            padding: 10px 24px; /* เพิ่ม Padding บนล่าง */
            color: white; 
            text-decoration: none; 
            font-size: 15px; /* ขยายฟอนต์นิดนึง */
            font-weight: 500;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .bg-blue { background-color: #1a237e; }
        .bg-gold { background-color: #b7950b; }
        .bg-green { background-color: #009900; }
        .bg-red { background-color: #c62828; }

        /* --- Grid ส่วนล่าง --- */
        .lower-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }
        @media (max-width: 992px) { .lower-section { grid-template-columns: 1fr; } }

        .dash-box {
            background: #fff; border-radius: 14px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #eee;
            margin-bottom: 20px;
        }
        .box-header {
            font-size: 16px; font-weight: 600; color: #2c3e50; margin-bottom: 15px;
            border-bottom: 1px solid #eee; padding-bottom: 10px;
            display: flex; justify-content: space-between; align-items: center;
        }

        .mini-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .mini-table th { text-align: left; color: #888; padding: 8px; border-bottom: 1px solid #eee; font-weight: 500; }
        .mini-table td { padding: 12px 8px; border-bottom: 1px solid #f9f9f9; color: #444; }
        
        .badge-mini { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .b-in { background: #e8f8f5; color: #27ae60; }
        .b-out { background: #fdedec; color: #c0392b; }

        .warn-item {
            padding: 10px 0; border-bottom: 1px solid #f5f5f5; display: flex; justify-content: space-between; font-size: 14px;
        }
        .warn-days { color: #e74c3c; font-weight: bold; font-size: 12px; background: #ffebee; padding: 2px 8px; border-radius: 10px; }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">

        <div class="page-title">Dashboard ภาพรวม</div>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-title">ผลรวมสินค้า</div>
                    <div class="stat-value">จำนวน <?= number_format($total_products) ?> สินค้า</div>
                    <div class="stat-sub">รายการสินค้าทั้งหมดในระบบ</div>
                </div>
                <a href="product_list.php" class="stat-footer bg-blue">
                    <span>ดูรายการสินค้า</span> <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-title">สินค้าคงเหลือ</div>
                    <div class="stat-value">จำนวน <?= number_format($total_stock_qty) ?> ชิ้น</div>
                    <div class="stat-sub">นับรวมทุกรายการ</div>
                </div>
                <a href="product_Stock.php" class="stat-footer bg-gold">
                    <span>เช็คสต๊อก</span> <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-title">ยอดขายทั้งหมด</div>
                    <div class="stat-value">รวม <?= number_format($total_sales_all) ?> บาท</div>
                    <div class="stat-sub" style="color:#009900;">
                        วันนี้ <?= number_format($total_sales_today) ?> บาท
                    </div>
                </div>
                <a href="ProductPoppular.php" class="stat-footer bg-green">
                    <span>ดูเพิ่มเติม</span> <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <div class="stat-card">
                <div class="stat-content">
                    <div class="stat-title">สินค้าใกล้หมด / ค้างสต็อก</div>
                    <div class="stat-value">จำนวน <?= number_format($low_stock_count) ?> ชิ้น</div>
                    <div class="stat-sub" style="color:#c62828;">รายการที่เหลือน้อยกว่า 10</div>
                </div>
                <a href="product_Stock.php?filter=lowstock" class="stat-footer bg-red">
                    <span>ดูเพิ่มเติม</span> <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>

        <div class="lower-section">
            
            <div class="dash-box">
                <div class="box-header">
                    <span><i class="fa-solid fa-clock-rotate-left"></i> ความเคลื่อนไหวล่าสุด (Recent Activity)</span>
                    <a href="ReportStock.php" style="font-size:12px; color:#3498db; text-decoration:none;">ดูทั้งหมด</a>
                </div>
                
                <table class="mini-table">
                    <thead>
                        <tr>
                            <th>เวลา</th>
                            <th>User</th>
                            <th>สินค้า</th>
                            <th>สถานะ</th>
                            <th>จำนวน</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($res_recent->num_rows > 0): ?>
                        <?php while($row = $res_recent->fetch_assoc()): 
                            $isAdd = ($row['type']=='add');
                        ?>
                        <tr>
                            <td><?= date("d/m H:i", strtotime($row['created_at'])) ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:5px;">
                                    <i class="fa-solid fa-user-circle" style="color:#ccc;"></i>
                                    <?= htmlspecialchars($row['username'] ?? 'System') ?>
                                </div>
                            </td>
                            <td><?= $row['name'] ?></td>
                            <td>
                                <?php if($isAdd): ?>
                                    <span class="badge-mini b-in">เข้า</span>
                                <?php else: ?>
                                    <span class="badge-mini b-out">ออก</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:bold;"><?= $row['amount'] ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#999;">ยังไม่มีรายการเคลื่อนไหว</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div>
                <div class="dash-box">
                    <div class="box-header"><span><i class="fa-solid fa-chart-pie"></i> สัดส่วนสินค้าตามหมวด</span></div>
                    <div style="height:200px; width:100%;">
                        <canvas id="catChart"></canvas>
                    </div>
                </div>

                <div class="dash-box">
                    <div class="box-header" style="color:#c0392b;">
                        <span><i class="fa-solid fa-hourglass-half"></i> ใกล้หมดอายุ (30 วัน)</span>
                    </div>
                    
                    <?php if($res_exp->num_rows > 0): ?>
                        <?php while($e = $res_exp->fetch_assoc()): ?>
                            <div class="warn-item">
                                <span><?= $e['name'] ?></span>
                                <span class="warn-days">อีก <?= $e['days_left'] ?> วัน</span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align:center; color:#999; padding:10px;">ไม่มีสินค้าใกล้หมดอายุ</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    const ctx = document.getElementById('catChart');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($cat_labels) ?>,
            datasets: [{
                data: <?= json_encode($cat_data) ?>,
                backgroundColor: [
                    '#3498db', '#e74c3c', '#f1c40f', '#2ecc71', '#9b59b6', '#34495e', '#1abc9c'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 10, font: {size: 11} } }
            }
        }
    });
</script>

</body>
</html>