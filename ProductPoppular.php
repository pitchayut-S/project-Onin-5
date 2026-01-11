<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// 1. ดึงสินค้ายอดนิยม (Top 5) - (เหมือนเดิม)
// ---------------------------------------------------------
$sql_popular = "
    SELECT 
        p.id, p.product_code, p.name, p.image, p.selling_price, p.quantity, c.category_name,
        COALESCE(SUM(t.amount), 0) AS total_sold
    FROM products p
    LEFT JOIN product_category c ON p.category = c.id
    LEFT JOIN stock_transactions t ON p.id = t.product_id 
         AND t.type = 'reduce' 
         AND t.reason = 'ขายหน้าร้าน'
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
";
$popular = $conn->query($sql_popular);

// ---------------------------------------------------------
// 2. ดึงยอดขายรายเดือน (ย้อนหลัง 6 เดือน) ** ปรับใหม่ **
// ---------------------------------------------------------

// 2.1 เตรียม Array เดือนย้อนหลัง 6 เดือน (ตั้งค่าเริ่มต้นเป็น 0)
$monthly_sales_data = [];
$thai_months = [
    '01'=>'ม.ค.', '02'=>'ก.พ.', '03'=>'มี.ค.', '04'=>'เม.ย.', '05'=>'พ.ค.', '06'=>'มิ.ย.',
    '07'=>'ก.ค.', '08'=>'ส.ค.', '09'=>'ก.ย.', '10'=>'ต.ค.', '11'=>'พ.ย.', '12'=>'ธ.ค.'
];

for ($i = 5; $i >= 0; $i--) {
    // สร้าง Key ปี-เดือน เช่น "2023-12"
    $date = date('Y-m', strtotime("-$i months"));
    
    // สร้าง Label ภาษาไทย เช่น "ธ.ค. 66"
    $y = date('Y', strtotime("-$i months")) + 543;
    $m = date('m', strtotime("-$i months"));
    $label = $thai_months[$m] . " " . substr($y, 2);
    
    $monthly_sales_data[$date] = [
        'label' => $label,
        'value' => 0
    ];
}

// 2.2 ดึงข้อมูลจริงจาก Database Group ตามเดือน
$sql_chart = "
    SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m') AS sale_month,
        SUM(t.amount * p.selling_price) AS total_revenue
    FROM stock_transactions t
    JOIN products p ON t.product_id = p.id
    WHERE t.type = 'reduce' 
      AND t.reason = 'ขายหน้าร้าน'
      AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) -- ย้อนหลัง 6 เดือน
    GROUP BY sale_month
";
$sales_chart = $conn->query($sql_chart);

// 2.3 หยอดข้อมูลจริงใส่ Array
while ($row = $sales_chart->fetch_assoc()) {
    $monthKey = $row['sale_month']; // เช่น 2023-12
    if (isset($monthly_sales_data[$monthKey])) {
        $monthly_sales_data[$monthKey]['value'] = floatval($row['total_revenue']);
    }
}

// 2.4 แยก Data เพื่อส่งให้กราฟ
$chart_labels = array_column($monthly_sales_data, 'label');
$chart_values = array_column($monthly_sales_data, 'value');
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard สินค้ายอดนิยม - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; font-family: 'Prompt', sans-serif; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 25px; color:#2c3e50; }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .product-card {
            background: white; border-radius: 16px; padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); text-align: center;
            position: relative; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid #f0f0f0;
        }

        .rank-badge {
            position: absolute; top: -10px; left: -10px; width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; font-size: 24px; background: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15); border: 2px solid #fff; z-index: 10;
        }
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FDB931); color: #fff; }
        .rank-2 { background: linear-gradient(135deg, #E0E0E0, #BDBDBD); color: #fff; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #A0522D); color: #fff; }
        .rank-other { background: #f8f9fa; color: #7f8c8d; font-size: 18px; font-weight: bold; width: 35px; height: 35px; }

        .product-card img { width: 100%; height: 180px; object-fit: cover; border-radius: 12px; margin-bottom: 15px; border: 1px solid #eee; }
        .card-code { font-size: 12px; color: #95a5a6; margin-bottom: 5px; }
        .card-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: -10px; height: 50px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .card-price { color: #e67e22; font-weight: bold; font-size: 16px; margin-bottom: 10px; }
        
        .stats-box {
            background: #f8f9fa; border-radius: 10px; padding: 10px;
            display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 13px;
        }
        .stat-item strong { display: block; font-size: 16px; color: #2c3e50; }
        .stat-label { color: #7f8c8d; }

        .card-buttons { display:flex; gap:10px; justify-content:center; }
        .btn-action {
            padding: 8px 15px; border-radius: 8px; font-size: 13px; text-decoration: none; 
            transition: 0.2s; display: flex; align-items: center; gap: 5px;
        }
        .btn-edit { background: #eef2f6; color: #2980b9; }
        .btn-edit:hover { background: #d6eaf8; }
        .btn-delete { background: #fdedec; color: #c0392b; }
        .btn-delete:hover { background: #fadbd8; }

        .chart-section {
            background: white; padding: 30px; margin-top: 20px;
            border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">

        <div class="page-title">สินค้ายอดนิยม (Top 5 Best Sellers)</div>

        <div class="cards-container">
        <?php 
        $rank = 1;
        while ($row = $popular->fetch_assoc()): 
            $rankClass = "rank-other";
            $icon = $rank;
            if ($rank == 1) { $rankClass = "rank-1"; $icon = "1"; }
            elseif ($rank == 2) { $rankClass = "rank-2"; $icon = "2"; }
            elseif ($rank == 3) { $rankClass = "rank-3"; $icon = "3"; }
        ?>
            <div class="product-card">
                <div class="rank-badge <?= $rankClass ?>"><?= $icon ?></div>

                <?php if ($row["image"]): ?>
                    <img src="uploads/<?= $row["image"] ?>">
                <?php else: ?>
                    <div style="height:180px; background:#f9f9f9; display:flex; align-items:center; justify-content:center; border-radius:12px; margin-bottom:15px; border:1px solid #eee;">
                        <span style="color:#ccc;">ไม่มีรูป</span>
                    </div>
                <?php endif; ?>

                <div class="card-code"><?= $row['product_code'] ?> | <?= $row['category_name'] ?></div>
                <div class="card-title"><?= $row["name"] ?></div>
                <div class="card-price">฿ <?= number_format($row["selling_price"], 2) ?></div>

                <div class="stats-box">
                    <div class="stat-item">
                        <span class="stat-label">ขายแล้ว</span>
                        <strong style="color:#27ae60;"><?= number_format($row["total_sold"]) ?></strong>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">คงเหลือ</span>
                        <strong style="color:#e67e22;"><?= number_format($row["quantity"]) ?></strong>
                    </div>
                </div>

                <div class="card-buttons">
                    <a class="btn-action btn-edit" href="product_list.php?search=<?= $row['product_code'] ?>">
                        <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                    </a>
                    <a class="btn-action btn-delete" href="products_delete.php?id=<?= $row['id'] ?>" onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?');">
                        <i class="fa-solid fa-trash"></i> ลบ
                    </a>
                </div>
            </div>
        <?php $rank++; endwhile; ?>
        
        <?php if ($rank == 1): ?>
            <div style="grid-column: 1/-1; text-align:center; padding:40px; color:#888;">
                <i class="fa-solid fa-circle-exclamation" style="font-size:40px; margin-bottom:10px;"></i><br>
                ยังไม่มีข้อมูลการขายหน้าร้าน
            </div>
        <?php endif; ?>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <h3 style="margin:0;">
                    <i class="fa-solid fa-chart-line" style="color:#3498db;"></i> 
                    แนวโน้มยอดขายรายเดือน (6 เดือนล่าสุด)
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
    type: 'bar', // เปลี่ยนเป็น 'bar' (แท่ง) ดูง่ายกว่าสำหรับรายเดือน
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'ยอดขาย (บาท)',
            data: <?= json_encode($chart_values) ?>,
            backgroundColor: "rgba(52, 152, 219, 0.6)", // สีแท่ง
            borderColor: "#2980b9",
            borderWidth: 1,
            borderRadius: 5 // มุมโค้งของแท่ง
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(context.parsed.y);
                        }
                        return label;
                    }
                },
                backgroundColor: '#2c3e50',
                titleFont: { size: 14, family: 'Prompt' },
                bodyFont: { size: 14, family: 'Prompt' },
                padding: 10,
                cornerRadius: 8,
                displayColors: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f0f0' },
                ticks: { 
                    font: { family: 'Prompt' },
                    callback: function(value) { return '฿' + new Intl.NumberFormat('th-TH').format(value); } 
                }
            },
            x: {
                grid: { display: false },
                ticks: { font: { family: 'Prompt' } }
            }
        }
    }
});
</script>

</body>
</html>