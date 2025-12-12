<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ดึงสินค้ายอดนิยม (Top 6 จากยอดขาย)
$popular = $conn->query("
    SELECT p.id, p.name, p.image, p.selling_price, p.quantity, c.category_name,
           COALESCE(SUM(s.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN product_category c ON p.category = c.id
    LEFT JOIN sales_history s ON s.product_id = p.id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 6
");

// ดึงข้อมูลยอดขายรายเดือนเพื่อทำกราฟ
$sales_chart = $conn->query("
    SELECT DATE_FORMAT(sale_date, '%Y-%m') AS month, SUM(quantity) AS total
    FROM sales_history
    GROUP BY month
    ORDER BY month ASC
");

$chart_labels = [];
$chart_values = [];

while ($row = $sales_chart->fetch_assoc()) {
    $chart_labels[] = $row['month'];
    $chart_values[] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สินค้ายอดนิยม - Onin Shop Stock</title>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">

<style>
.page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; }

.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 25px;
}

.product-card {
    width: 260px;
    background: white;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    text-align: center;
}

.product-card img {
    width: 100%;
    height: 150px;
    object-fit: cover;
    border-radius: 12px;
}

.card-title { font-weight: 700; margin: 12px 0 5px; }
.card-info { font-size: 14px; margin-bottom: 5px; }

.card-buttons {
    display:flex; gap:10px; justify-content:center; margin-top:10px;
}

.btn-edit { background:#3498db; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; }
.btn-delete { background:#e74c3c; color:white; padding:6px 12px; border-radius:6px; text-decoration:none; }

.chart-box {
    background:white; padding:20px; margin-top:35px;
    border-radius:14px; box-shadow:0 4px 15px rgba(0,0,0,0.05);
}
</style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">

    <div class="content-container">

        <div class="page-title">สินค้ายอดนิยม</div>

        <!-- 📌 แสดงสินค้าแบบ Card -->
        <div class="cards-container">
        <?php 
        $rank = 1;
        while ($row = $popular->fetch_assoc()): 
        ?>
            <div class="product-card">
                <div class="card-title">สินค้าอันดับ <?= $rank++ ?></div>

                <?php if ($row["image"]): ?>
                    <img src="uploads/<?= $row["image"] ?>">
                <?php else: ?>
                    <img src="img/no-image.png">
                <?php endif; ?>

                <div class="card-info"><?= $row["name"] ?></div>
                <div class="card-info"><?= number_format($row["selling_price"],2) ?> บาท</div>
                <div class="card-info">คงเหลือ: <?= $row["quantity"] ?> ชิ้น</div>
                <div class="card-info">ขายได้: <?= $row["total_sold"] ?> ชิ้น</div>

                <div class="card-buttons">
                    <a class="btn-edit" href="products_edit.php?id=<?= $row['id'] ?>"><i class="fa fa-edit"></i> Edit</a>
                    <a class="btn-delete" href="products_delete.php?id=<?= $row['id'] ?>"
                       onclick="return confirm('ต้องการลบสินค้านี้หรือไม่?');">
                       <i class="fa fa-trash"></i> Delete
                    </a>
                </div>
            </div>
        <?php endwhile; ?>
        </div>

        <!-- 📊 กราฟแนวโน้มยอดขาย -->
        <div class="chart-box">
            <h3>แนวโน้มยอดขายรายเดือน</h3>
            <canvas id="salesChart"></canvas>
        </div>

    </div>

</div>

<!-- โหลด Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('salesChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'ยอดขาย (ชิ้น)',
            data: <?= json_encode($chart_values) ?>,
            borderWidth: 2,
            borderColor: "#3498db",
            backgroundColor: "rgba(52,152,219,0.2)",
            fill: true,
            tension: 0.3
        }]
    }
});
</script>

</body>
</html>
