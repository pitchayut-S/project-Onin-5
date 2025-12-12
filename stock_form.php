<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ไม่พบสินค้า");
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("ไม่พบสินค้า");
}

$product = $result->fetch_assoc();

$conn->query("UPDATE products SET popularity = popularity + 1 WHERE id = $id");


?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ปรับสต๊อกสินค้า</title>
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family: "Prompt", sans-serif; background:#f3f6fb; }
.form-container {
    background:#fff; padding:30px; border-radius:12px;
    width:450px; margin:40px auto;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}
input, select, textarea {
    width:100%; padding:12px; margin-bottom:15px;
    border-radius:10px; border:1px solid #ccc;
}
button {
    width:100%; padding:12px; background:#356CB5;
    color:white; border:none; border-radius:10px;
    font-size:16px; cursor:pointer; font-weight:600;
}
button:hover { background:#2a5291; }
</style>
</head>

<body>

<div class="form-container">
    <h2 style="text-align:center;">ปรับสต๊อกสินค้า</h2>

    <p><b>ชื่อสินค้า:</b> <?= $product['name'] ?></p>
    <p><b>จำนวนคงเหลือ:</b> <?= $product['quantity'] ?></p>

    <form action="stock_save.php" method="POST">
        <input type="hidden" name="id" value="<?= $product['id'] ?>">

        <label>เลือกการปรับสต๊อก</label>
        <select name="action_type" required>
            <option value="increase">เพิ่มสินค้า</option>
            <option value="decrease">ลดสินค้า</option>
        </select>

        <label>จำนวนที่ต้องการปรับ</label>
        <input type="number" name="change_qty" min="1" required>

        <label>หมายเหตุ (ถ้ามี)</label>
        <textarea name="note" rows="3"></textarea>

        <button type="submit">บันทึกการปรับสต๊อก</button>
    </form>

    <div style="text-align:center; margin-top:10px;">
        <a href="product_Stock.php">← กลับ</a>
    </div>
</div>

</body>
</html>
