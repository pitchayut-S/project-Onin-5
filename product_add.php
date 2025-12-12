<?php
session_start();
require_once "db.php";

// ถ้ายังไม่ login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ดึงประเภทสินค้าจากตาราง product_category
$category_query = $conn->query("SELECT id, category_name FROM product_category ORDER BY category_name ASC");


// เมื่อกดบันทึก
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_code  = $_POST['product_code'];
    $name          = $_POST['name'];
    $category      = $_POST['category'];  // จะเก็บเป็น id
    $unit          = $_POST['unit'];
    $cost          = $_POST['cost'];
    $selling_price = $_POST['selling_price'];
    $mfg_date      = $_POST['mfg_date'];
    $exp_date      = $_POST['exp_date']; 
    $quantity      = $_POST['quantity'];

    // อัพโหลดรูปสินค้า
    $image_name = null;
    if (!empty($_FILES["image"]["name"])) {

        // ตั้งชื่อไฟล์ใหม่ ป้องกันซ้ำ
        $image_name = time() . "_" . basename($_FILES["image"]["name"]);
        $target_path = "uploads/" . $image_name;

        // upload file
        move_uploaded_file($_FILES["image"]["tmp_name"], $target_path);
    }

    // บันทึกข้อมูลสินค้า
    $sql = "
        INSERT INTO products 
        (product_code, name, category, unit, cost, selling_price, mfg_date, exp_date, quantity, image)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssddsis",
        $product_code,
        $name,
        $category,
        $unit,
        $cost,
        $selling_price,
        $mfg_date,
        $exp_date,
        $quantity,
        $image_name
    );

    if ($stmt->execute()) {
        echo "<script>alert('บันทึกข้อมูลสำเร็จ'); window.location='product_list.php';</script>";
        exit();
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด ไม่สามารถบันทึกข้อมูลได้');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เพิ่มสินค้า</title>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    body { font-family:"Prompt", sans-serif; background:#f3f6fb; }
    .form-container {
        background:#fff; padding:25px; border-radius:10px;
        width:500px; margin:40px auto;
        box-shadow:0 4px 12px rgba(0,0,0,0.1);
    }
    input, select {
        width:100%; padding:12px; margin-bottom:15px;
        border-radius:8px; border:1px solid #ccc;
        font-size:14px;
    }
    button {
        width:100%; padding:12px; background:#356CB5;
        color:white; border:none; border-radius:8px;
        font-size:16px; cursor:pointer; font-weight:600;
    }
    button:hover { background:#2c5894; }
</style>

</head>

<body>

<div class="form-container">
    <h2 style="text-align:center;">เพิ่มข้อมูลสินค้า</h2>

    <form method="POST" enctype="multipart/form-data">

        <label>รหัสสินค้า</label>
        <input type="text" name="product_code" required>

        <label>ชื่อสินค้า</label>
        <input type="text" name="name" required>

        <label>ประเภทสินค้า</label>
        <select name="category" required>
            <option value="">-- เลือกประเภทสินค้า --</option>
            <?php while($row = $category_query->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['category_name']) ?></option>
                
            <?php endwhile; ?>
        </select>

        <label>หน่วยสินค้า</label>
        <select name="unit" required>
            <option value="">-- เลือกหน่วยสินค้า --</option>
            <option value="ชิ้น">ชิ้น</option>
            <option value="ซอง">ซอง</option>
            <option value="ห่อ">ห่อ</option>
            <option value="แพ็ค">แพ็ค</option>
            <option value="กล่อง">กล่อง</option>
        </select>

        <label>ต้นทุนสินค้า</label>
        <input type="number" step="0.01" name="cost" required>

        <label>ราคาขาย</label>
        <input type="number" step="0.01" name="selling_price" required>

        <label>วันที่ผลิต</label>
        <input type="date" name="mfg_date">

        <label>วันหมดอายุ</label>
        <input type="date" name="exp_date">

        <label>จำนวนสินค้า</label>
        <input type="number" name="quantity" required>

        <label>รูปสินค้า</label>
        <input type="file" name="image" accept="image/*">

        <button type="submit">บันทึกสินค้า</button>
    </form>

    <div style="text-align:center; margin-top:15px;">
        <a href="product_list.php">← กลับหน้ารายการสินค้า</a>
    </div>
</div>

</body>
</html>
