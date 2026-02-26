<?php
ob_start();
session_start();
require_once "db.php";

// เช็คสิทธิ์ Login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// =========================================================
// PART 1: QUERY DATA
// =========================================================
// 1. ผลรวมสินค้า
$sql_prod_count = "SELECT COUNT(*) as total_items FROM products";
$total_products = $conn->query($sql_prod_count)->fetch_assoc()['total_items'];

// 2. หมวดหมู่สินค้า (เปลี่ยนจากนับจำนวนชิ้นรวม เป็นนับหมวดหมู่)
$sql_cat_count = "SELECT COUNT(*) as total_cat FROM product_category";
$total_categories = $conn->query($sql_cat_count)->fetch_assoc()['total_cat'] ?? 0;

// 3. ยอดขาย (ปรับเป็นยอดขายเดือนนี้ และ วันนี้)
// --- ส่วนที่เพิ่มเข้ามา: หาชื่อเดือนและปี พ.ศ. ปัจจุบัน ---
$thai_months_full = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$current_month_name = $thai_months_full[(int)date('m')]; // ได้ชื่อเดือน เช่น กุมภาพันธ์
$current_year_th = date('Y') + 543; // แปลง ค.ศ. เป็น พ.ศ.

// ยอดขายเดือนนี้
$sql_sales_month = "SELECT SUM(t.amount * p.selling_price) as month_total 
                    FROM stock_transactions t 
                    JOIN products p ON t.product_id = p.id 
                    WHERE t.type = 'reduce' 
                    AND t.reason = 'ขายหน้าร้าน' 
                    AND MONTH(t.created_at) = MONTH(CURDATE()) 
                    AND YEAR(t.created_at) = YEAR(CURDATE())";
$total_sales_month = $conn->query($sql_sales_month)->fetch_assoc()['month_total'] ?? 0;

// ยอดขายวันนี้
$sql_sales_today = "SELECT SUM(t.amount * p.selling_price) as today_total 
                    FROM stock_transactions t 
                    JOIN products p ON t.product_id = p.id 
                    WHERE t.type = 'reduce' 
                    AND t.reason = 'ขายหน้าร้าน' 
                    AND DATE(t.created_at) = CURDATE()";
$total_sales_today = $conn->query($sql_sales_today)->fetch_assoc()['today_total'] ?? 0;

// 4. สินค้าใกล้หมด
$sql_low = "SELECT COUNT(*) as low_count FROM products WHERE quantity <= 10";
$low_stock_count = $conn->query($sql_low)->fetch_assoc()['low_count'];

// 5. กราฟ (เปลี่ยนเป็นผลรวมจำนวนชิ้น SUM(quantity) แยกตามหมวดหมู่)
$sql_cat_chart = "SELECT c.category_name, SUM(p.quantity) as stock_qty 
                  FROM products p 
                  LEFT JOIN product_category c ON p.category = c.id 
                  GROUP BY p.category";
$res_cat = $conn->query($sql_cat_chart);
$cat_labels = [];
$cat_data = [];
while ($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['category_name'] ?? 'อื่นๆ';
    $cat_data[] = $row['stock_qty'] ?? 0;
}

// 6. ล่าสุด
$sql_recent = "SELECT t.*, p.product_code, p.name FROM stock_transactions t JOIN products p ON t.product_id = p.id ORDER BY t.created_at DESC LIMIT 5";
$res_recent = $conn->query($sql_recent);

// 7. ใกล้หมดอายุ
$sql_exp = "SELECT name, exp_date, DATEDIFF(exp_date, CURDATE()) as days_left FROM products WHERE exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY exp_date ASC LIMIT 5";
$res_exp = $conn->query($sql_exp);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel='icon' type='image/png' href='favicon.png'>

    <style>
        /* === DASHBOARD STYLE (Redesign) === */
        .content-container {
            padding: 30px;
            font-family: 'Prompt', sans-serif;
            background-color: #f3f4f6;
            min-height: 100vh;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        /* --- THE CARD DESIGN --- */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 180px;
            overflow: hidden;
            position: relative;
            border: 1px solid #f3f4f6;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .stat-body {
            padding: 25px 25px;
            flex: 1;
            z-index: 2;
            position: relative;
        }

        .stat-title {
            font-size: 18px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }

        .stat-unit {
            font-size: 16px;
            font-weight: 500;
            color: #9ca3af;
            margin-left: 5px;
        }

        .stat-desc {
            font-size: 14px;
            color: #9ca3af;
            margin-top: 4px;
        }

        /* Icon Background (Watermark) */
        .stat-icon {
            position: absolute;
            right: 15px;
            top: 20px;
            font-size: 70px;
            opacity: 0.1;
            z-index: 1;
            transition: 0.3s;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
            opacity: 0.15;
        }

        /* Footer Bar */
        .stat-footer {
            padding: 10px 25px;
            color: white;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
            transition: background-color 0.2s;
        }

        .stat-footer:hover {
            filter: brightness(90%);
        }

        /* Darken on hover */

        /* Colors */
        .card-blue .stat-val {
            color: #2563eb;
            font-weight: 600;
            font-size: 18px;
        }

        .card-blue .stat-footer {
            background-color: #1a237e;
        }

        .card-blue .stat-icon {
            color: #2563eb;
        }

        .card-yellow .stat-val {
            color: #d97706;
            font-weight: 600;
            font-size: 18px;
        }

        .card-yellow .stat-footer {
            background-color: #b7950b;
        }

        .card-yellow .stat-icon {
            color: #d97706;
        }

        .card-green .stat-val {
            color: #059669;
            font-weight: 600;
            font-size: 18px;
        }

        .card-green .stat-footer {
            background-color: #009900;
        }

        .card-green .stat-icon {
            color: #059669;
        }

        .card-red .stat-val {
            color: #dc2626;
            font-weight: 600;
            font-size: 18px;
        }

        .card-red .stat-footer {
            background-color: #c62828;
        }

        .card-red .stat-icon {
            color: #dc2626;
        }

        /* --- LOWER SECTION --- */
        .lower-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 992px) {
            .lower-section {
                grid-template-columns: 1fr;
            }
        }

        .dash-box {
            background: #fff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #f3f4f6;
            height: 100%;
        }

        .box-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 10px;
        }

        .box-title {
            font-size: 18px;
            font-weight: 700;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Table */
        .simple-table {
            width: 100%;
            border-collapse: collapse;
        }

        .simple-table th {
            text-align: left;
            padding: 12px;
            color: #6b7280;
            font-size: 13px;
            font-weight: 600;
            background: #f9fafb;
            border-radius: 6px;
        }

        .simple-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #f3f4f6;
            color: #1f2937;
            font-size: 14px;
        }

        .simple-table tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f0f7ff;
        }

        /* Badges */
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .bg-in {
            background: #d1fae5;
            color: #065f46;
        }

        .bg-out {
            background: #fee2e2;
            color: #991b1b;
        }

        .expire-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px dashed #e5e7eb;
        }

        .expire-days {
            background: #fee2e2;
            color: #dc2626;
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <?php include "topbar.php"; ?>

        <div class="content-container">

            <div class="page-title">Dashboard ภาพรวม</div>

            <div class="dashboard-grid">

                <div class="stat-card card-blue">
                    <i class="fa-solid fa-box-open stat-icon"></i>
                    <div class="stat-body">
                        <div class="stat-title">ผลรวมสินค้า</div>
                        <div class="stat-val">จำนวน <?= number_format($total_products) ?> <span class="stat-unit">รายการ</span></div>
                        <div class="stat-desc">สินค้าทั้งหมดในระบบ</div>
                    </div>
                    <a href="product_list.php" class="stat-footer">
                        ดูรายการสินค้า <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card card-yellow">
                    <i class="fa-solid fa-layer-group stat-icon"></i>
                    <div class="stat-body">
                        <div class="stat-title">หมวดหมู่สินค้า</div>
                        <div class="stat-val"><?= number_format($total_categories) ?> <span class="stat-unit">หมวดหมู่</span></div>
                        <div class="stat-desc">จัดกลุ่มสินค้าทั้งหมด</div>
                    </div>
                    <a href="category_list.php" class="stat-footer">
                        ดูหมวดหมู่ <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card card-green">
                    <i class="fa-solid fa-sack-dollar stat-icon"></i>
                    <div class="stat-body">
                        <div class="stat-title">ยอดขาย <?= $current_month_name ?> <?= $current_year_th ?></div>
                        <div class="stat-val">฿<?= number_format($total_sales_month, 2) ?></div>
                        <div class="stat-desc">
                            <span style="color: #059669; font-weight: 600;"><i class="fa-solid fa-calendar-day"></i> วันนี้:</span> ฿<?= number_format($total_sales_today, 2) ?>
                        </div>
                    </div>
                    <a href="ProductPoppular.php" class="stat-footer">
                        ดูสินค้ายอดนิยม <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="stat-card card-red">
                    <i class="fa-solid fa-triangle-exclamation stat-icon"></i>
                    <div class="stat-body">
                        <div class="stat-title">สินค้าใกล้หมด / ค้างสต๊อก</div>
                        <div class="stat-val"><?= number_format($low_stock_count) ?> <span class="stat-unit">รายการ</span></div>
                        <div class="stat-desc">รายการที่เหลือน้อยกว่า 10</div>
                    </div>
                    <a href="product_Stock.php?filter=lowstock" class="stat-footer">
                        ดูเพิ่มเติม <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

            </div>

            <div class="lower-section">

                <div class="dash-box">
                    <div class="box-header">
                        <div class="box-title"><i class="fa-solid fa-clock-rotate-left"></i> ความเคลื่อนไหวล่าสุด</div>
                        <a href="ReportStock.php" style="font-size:16px; color:#2563eb; text-decoration:none; font-weight:700;">ดูทั้งหมด</a>
                    </div>

                    <table class="simple-table">
                        <thead>
                            <tr>
                                <th width="20%">เวลา</th>
                                <th width="40%">สินค้า</th>
                                <th width="20%">สถานะ</th>
                                <th width="20%">จำนวน</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_recent->num_rows > 0): ?>
                                <?php while ($row = $res_recent->fetch_assoc()):
                                    $isAdd = ($row['type'] == 'add');
                                ?>
                                    <tr>
                                        <td style="color:#6b7280; font-size:13px;">
                                            <?= date("d/m H:i", strtotime($row['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div style="font-weight:600; color:#1f2937;"><?= $row['name'] ?></div>
                                            <div style="font-size:12px; color:#9ca3af;"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($row['username']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($isAdd): ?>
                                                <span class="status-badge bg-in"><i class="fa-solid fa-arrow-down"></i> เข้า</span>
                                            <?php else: ?>
                                                <span class="status-badge bg-out"><i class="fa-solid fa-arrow-up"></i> ออก</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight:700; font-size:15px;"><?= number_format($row['amount']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; padding:30px; color:#9ca3af;">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display:flex; flex-direction:column; gap:25px;">
                    <div class="dash-box">
                        <div class="box-header">
                            <div class="box-title"><i class="fa-solid fa-chart-pie" style="color:#d97706;"></i>สต๊อกคงเหลือ (แยกตามหมวดหมู่)</div>
                        </div>
                        <div style="height:200px; width:100%; display:flex; justify-content:center;">
                            <canvas id="catChart"></canvas>
                        </div>
                    </div>

                    <div class="dash-box">
                        <div class="box-header">
                            <div class="box-title" style="color:#dc2626;"><i class="fa-solid fa-calendar-xmark"></i> ใกล้หมดอายุ (30 วัน)</div>
                        </div>
                        <?php if ($res_exp->num_rows > 0): ?>
                            <?php while ($e = $res_exp->fetch_assoc()): ?>
                                <div class="expire-item">
                                    <span style="color:#374151; font-weight:500;"><?= $e['name'] ?></span>
                                    <span class="expire-days">อีก <?= $e['days_left'] ?> วัน</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align:center; color:#9ca3af; padding:20px;">ไม่มีสินค้าใกล้หมดอายุ</div>
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
                    backgroundColor: ['#36a2eb', '#ff6384', '#ff9f40', '#4bc0c0', '#9966ff',
                        '#ffcd56', '#00d2d3', '#ff9ff3', '#5f27cd', '#2e86de'
                    ],
                    borderWidth: 0,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: {
                                family: 'Prompt',
                                size: 11
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
    </script>

</body>

</html>