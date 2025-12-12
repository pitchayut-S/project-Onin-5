<?php
session_start();
require_once "db.php";

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
?>

<!DOCTYPE html>
<html lang="th">
<head>
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
