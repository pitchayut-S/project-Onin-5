<?php
ob_start();
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 0. ตั้งค่าระบบค้นหาเดือน/ปี ย้อนหลัง
// ---------------------------------------------------------
$selected_month = isset($_GET['month']) ? str_pad($_GET['month'], 2, '0', STR_PAD_LEFT) : date('m');
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// อาเรย์สำหรับแสดงชื่อเดือนภาษาไทย
$thai_months_full = [
    '01' => 'มกราคม',
    '02' => 'กุมภาพันธ์',
    '03' => 'มีนาคม',
    '04' => 'เมษายน',
    '05' => 'พฤษภาคม',
    '06' => 'มิถุนายน',
    '07' => 'กรกฎาคม',
    '08' => 'สิงหาคม',
    '09' => 'กันยายน',
    '10' => 'ตุลาคม',
    '11' => 'พฤศจิกายน',
    '12' => 'ธันวาคม'
];
$display_month_name = $thai_months_full[$selected_month];
$display_year_th = $selected_year + 543;

// ---------------------------------------------------------
// 1. ดึงยอดขายรวม ของเดือนที่เลือก
// ---------------------------------------------------------
$sql_total_sales = "
    SELECT SUM(t.amount * p.selling_price) as total_sales
    FROM stock_transactions t 
    JOIN products p ON t.product_id = p.id 
    WHERE t.type = 'reduce' 
      AND p.is_deleted = 0
      AND t.reason = 'ขายหน้าร้าน'
      AND MONTH(t.created_at) = '$selected_month'
      AND YEAR(t.created_at) = '$selected_year'
";
$total_sales_result = $conn->query($sql_total_sales)->fetch_assoc();
$total_sales_month = $total_sales_result['total_sales'] ?? 0;

// ---------------------------------------------------------
// 2. ดึงสินค้ายอดนิยม (Top 5) เฉพาะเดือนที่เลือก
// ---------------------------------------------------------
$sql_popular = "
    SELECT 
        p.id, p.product_code, p.name, p.image, p.selling_price, p.quantity, c.category_name,
        SUM(t.amount) AS total_sold
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    LEFT JOIN product_category c ON p.category = c.id
    WHERE t.type = 'reduce' 
      AND p.is_deleted = 0
      AND t.reason = 'ขายหน้าร้าน'
      AND MONTH(t.created_at) = '$selected_month'
      AND YEAR(t.created_at) = '$selected_year'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
";
$popular = $conn->query($sql_popular);

// ---------------------------------------------------------
// 3. ดึงยอดขาย 12 เดือน (ตามปีที่เลือก) สำหรับกราฟเส้น
// ---------------------------------------------------------
$monthly_sales_data = [];
$thai_months_short = [
    '01' => 'ม.ค.',
    '02' => 'ก.พ.',
    '03' => 'มี.ค.',
    '04' => 'เม.ย.',
    '05' => 'พ.ค.',
    '06' => 'มิ.ย.',
    '07' => 'ก.ค.',
    '08' => 'ส.ค.',
    '09' => 'ก.ย.',
    '10' => 'ต.ค.',
    '11' => 'พ.ย.',
    '12' => 'ธ.ค.'
];

// เตรียมข้อมูล 12 เดือนของปีที่เลือกไว้ล่วงหน้า (ถ้าเดือนไหนไม่มีขาย จะได้เป็น 0)
for ($m = 1; $m <= 12; $m++) {
    $monthStr = str_pad($m, 2, '0', STR_PAD_LEFT);
    $dateKey = $selected_year . '-' . $monthStr; // เช่น 2024-01
    $monthly_sales_data[$dateKey] = [
        'label' => $thai_months_short[$monthStr],
        'value' => 0
    ];
}

// ดึงข้อมูลยอดขายจากฐานข้อมูล เฉพาะปีที่เลือก
$sql_chart = "
    SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m') AS sale_month,
        SUM(t.amount * p.selling_price) AS total_revenue
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.type = 'reduce' 
      AND p.is_deleted = 0
      AND t.reason = 'ขายหน้าร้าน'
      AND YEAR(t.created_at) = '$selected_year'
    GROUP BY sale_month
";
$sales_chart = $conn->query($sql_chart);

// หยอดข้อมูลจริงใส่ Array
while ($row = $sales_chart->fetch_assoc()) {
    $monthKey = $row['sale_month'];
    if (isset($monthly_sales_data[$monthKey])) {
        $monthly_sales_data[$monthKey]['value'] = floatval($row['total_revenue']);
    }
}

$chart_labels = array_column($monthly_sales_data, 'label');
$chart_values = array_column($monthly_sales_data, 'value');
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานยอดขายและสินค้ายอดนิยม - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel='icon' type='image/png' href='favicon.png'>

    <style>
        .content-container {
            padding: 30px;
            background-color: #f3f4f6;
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #2c3e50;
        }

        /* --- Filter Bar Style --- */
        .filter-bar {
            background: #fff;
            padding: 20px;
            border-radius: 14px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .filter-select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-family: 'Prompt', sans-serif;
            font-size: 15px;
            min-width: 150px;
            outline: none;
        }

        .btn-filter {
            background: #356CB5;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Prompt', sans-serif;
        }

        .btn-filter:hover {
            background: #2a5298;
        }

        /* --- Summary Box --- */
        .summary-box {
            background: linear-gradient(135deg, #059669, #10b981);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }

        .summary-text {
            font-size: 18px;
            opacity: 0.9;
        }

        .summary-value {
            font-size: 36px;
            font-weight: 700;
        }

        /* --- Existing Styles --- */
        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #34495e;
            border-left: 4px solid #356CB5;
            padding-left: 10px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid #f0f0f0;
        }

        .rank-badge {
            position: absolute;
            top: -10px;
            left: -10px;
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 24px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            border: 2px solid #fff;
            z-index: 10;
        }

        .rank-1 {
            background: linear-gradient(135deg, #FFD700, #FDB931);
            color: #fff;
        }

        .rank-2 {
            background: linear-gradient(135deg, #E0E0E0, #BDBDBD);
            color: #fff;
        }

        .rank-3 {
            background: linear-gradient(135deg, #CD7F32, #A0522D);
            color: #fff;
        }

        .rank-other {
            background: #f8f9fa;
            color: #7f8c8d;
            font-size: 18px;
            font-weight: bold;
            width: 35px;
            height: 35px;
        }

        .product-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #eee;
        }

        .card-code {
            font-size: 12px;
            color: #95a5a6;
            margin-bottom: 5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: -15px;
            height: 50px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .card-price {
            color: #e67e22;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .stats-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 13px;
        }

        .stat-item strong {
            display: block;
            font-size: 16px;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
        }

        .chart-section {
            background: white;
            padding: 30px;
            margin-top: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <?php include "topbar.php"; ?>

        <div class="content-container">

            <div class="page-title">รายงานยอดขายและสินค้ายอดนิยม</div>

            <form class="filter-bar" method="GET" action="">
                <div style="font-weight: 600; color: #555;"><i class="fa-solid fa-filter"></i> เลือกเดือน/ปี:</div>
                <select name="month" class="filter-select">
                    <?php foreach ($thai_months_full as $m_num => $m_name): ?>
                        <option value="<?= $m_num ?>" <?= ($selected_month == $m_num) ? 'selected' : '' ?>>
                            <?= $m_name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="year" class="filter-select">
                    <?php
                    $current_y = date('Y');
                    for ($y = $current_y; $y >= $current_y - 2; $y--): // ย้อนหลังได้ 2 ปี 
                        ?>
                        <option value="<?= $y ?>" <?= ($selected_year == $y) ? 'selected' : '' ?>>
                            <?= $y + 543 ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-filter">เรียกดูข้อมูล</button>
            </form>

            <div class="summary-box">
                <div>
                    <div class="summary-text">ยอดขายรวมประจำเดือน <b><?= $display_month_name ?>
                            <?= $display_year_th ?></b></div>
                    <div class="summary-value">฿ <?= number_format($total_sales_month, 2) ?></div>
                </div>
                <div>
                    <i class="fa-solid fa-sack-dollar" style="font-size: 60px; opacity: 0.5;"></i>
                </div>
            </div>

            <div class="section-title">Top 5 สินค้าขายดีประจำเดือนนี้</div>
            <div class="cards-container">
                <?php
                $rank = 1;
                if ($popular->num_rows > 0):
                    while ($row = $popular->fetch_assoc()):
                        $rankClass = "rank-other";
                        $icon = $rank;
                        if ($rank == 1) {
                            $rankClass = "rank-1";
                            $icon = "1";
                        } elseif ($rank == 2) {
                            $rankClass = "rank-2";
                            $icon = "2";
                        } elseif ($rank == 3) {
                            $rankClass = "rank-3";
                            $icon = "3";
                        }
                        ?>
                        <div class="product-card">
                            <div class="rank-badge <?= $rankClass ?>"><?= $icon ?></div>

                            <?php if ($row["image"]): ?>
                                <img src="uploads/<?= $row["image"] ?>">
                            <?php else: ?>
                                <div
                                    style="height:180px; background:#f9f9f9; display:flex; align-items:center; justify-content:center; border-radius:12px; margin-bottom:15px; border:1px solid #eee;">
                                    <span style="color:#ccc;">ไม่มีรูป</span>
                                </div>
                            <?php endif; ?>

                            <div class="card-code"><?= $row['product_code'] ?> | <?= $row['category_name'] ?></div>
                            <div class="card-title"><?= $row["name"] ?></div>
                            <div class="card-price">฿ <?= number_format($row["selling_price"], 2) ?></div>

                            <div class="stats-box">
                                <div class="stat-item">
                                    <span class="stat-label">ขายไปแล้วในเดือนนี้</span>
                                    <strong style="color:#27ae60;"><?= number_format($row["total_sold"]) ?> ชิ้น</strong>
                                </div>
                            </div>
                        </div>
                        <?php $rank++;
                    endwhile; ?>
                <?php else: ?>
                    <div
                        style="grid-column: 1/-1; text-align:center; padding:50px; background:white; border-radius:16px; border:1px solid #eee; color:#888;">
                        <i class="fa-solid fa-box-open" style="font-size:50px; margin-bottom:15px; color:#ddd;"></i><br>
                        ยังไม่มีการขายสินค้ารายการใดเลยในเดือน <?= $display_month_name ?>     <?= $display_year_th ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="chart-section">
                <div class="chart-header">
                    <h3 style="margin:0;">
                        <i class="fa-solid fa-chart-line" style="color:#3498db;"></i>
                        สรุปยอดขายรายเดือน ประจำปี <?= $display_year_th ?>
                    </h3>
                </div>
                <div style="height: 400px;">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const ctx = document.getElementById('salesChart');

        new Chart(ctx, {
            type: 'line', // เปลี่ยนเป็นกราฟเส้น
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'ยอดขายรวม (บาท)',
                    data: <?= json_encode($chart_values) ?>,
                    backgroundColor: "rgba(52, 152, 219, 0.15)", // สีระบายใต้เส้น (จางๆ)
                    borderColor: "#2980b9", // สีของเส้น
                    borderWidth: 3, // ความหนาเส้น
                    pointBackgroundColor: "#e67e22", // สีของจุดแต่ละเดือน
                    pointBorderColor: "#fff",
                    pointBorderWidth: 2,
                    pointRadius: 5, // ขนาดจุด
                    pointHoverRadius: 7, // ขนาดจุดตอนเอาเมาส์ชี้
                    fill: true, // ให้ระบายสีใต้เส้น
                    tension: 0.3 // ความโค้งของเส้น (0 = เหลี่ยม, 0.5 = โค้งมาก)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('th-TH', {
                                        style: 'currency',
                                        currency: 'THB'
                                    }).format(context.parsed.y);
                                }
                                return label;
                            }
                        },
                        backgroundColor: '#2c3e50',
                        titleFont: {
                            size: 14,
                            family: 'Prompt'
                        },
                        bodyFont: {
                            size: 14,
                            family: 'Prompt'
                        },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f0f0f0'
                        },
                        ticks: {
                            font: {
                                family: 'Prompt'
                            },
                            callback: function (value) {
                                return '฿' + new Intl.NumberFormat('th-TH').format(value);
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                family: 'Prompt'
                            }
                        }
                    }
                }
            }
        });
    </script>

</body>

</html>