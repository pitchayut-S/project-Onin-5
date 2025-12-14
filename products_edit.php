<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------
// ดึงข้อมูลสินค้าตาม ID
// ---------------------------
if (!isset($_GET['id'])) {
    die("ไม่พบสินค้าที่ต้องการแก้ไข");
}

$id = intval($_GET['id']);

$sql = "SELECT * FROM products WHERE id = $id";
$result = $conn->query($sql);
$product = $result->fetch_assoc();

if (!$product) {
    die("ไม่พบข้อมูลสินค้า");
}

// ---------------------------
// ดึงหมวดหมู่สินค้า
// ---------------------------
$categories = $conn->query("SELECT * FROM product_category ORDER BY category_name ASC");

// ---------------------------
// เมื่อกดบันทึกแก้ไข
// ---------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_code  = $_POST['product_code'];
    $name          = $_POST['name'];
    $category      = $_POST['category'];
    $quantity      = $_POST['quantity'];
    $exp_date      = $_POST['exp_date'];

    $image = $product['image']; // ใช้รูปเดิมก่อน

    // หากมีการอัปโหลดรูปใหม่
    if (!empty($_FILES['image']['name'])) {

        $img = $_FILES['image'];
        $ext = strtolower(pathinfo($img['name'], PATHINFO_EXTENSION));
        $newName = uniqid("img_") . "." . $ext;

        if (move_uploaded_file($img['tmp_name'], "uploads/" . $newName)) {

            // ลบรูปเก่า
            if (!empty($product['image']) && file_exists("uploads/" . $product['image'])) {
                unlink("uploads/" . $product['image']);
            }

            $image = $newName;
        }
    }

    // บันทึกข้อมูลใหม่ลง DB
    $stmt = $conn->prepare("
        UPDATE products SET 
            product_code = ?, 
            name = ?, 
            category = ?, 
            quantity = ?, 
            exp_date = ?,
            image = ?
        WHERE id = ?
    ");

    $stmt->bind_param("sssissi", 
        $product_code,
        $name,
        $category,
        $quantity,
        $exp_date,
        $image,
        $id
    );

    $stmt->execute();

    echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location='product_list.php';</script>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขข้อมูลสินค้า</title>

<style>
    body { font-family: "Prompt"; background:#f3f6fb; }

    .form-wrapper {
        width: 450px;
        background: #fff;
        padding: 25px;
        margin: 40px auto;
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    input, select {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 10px;
        border: 1px solid #ccc;
        font-size: 14px;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #356CB5;
        border: none;
        color: white;
        font-size: 16px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
    }

    .product-img {
        width: 100px;
        height: 100px;
        border-radius: 12px;
        object-fit: cover;
        display: block;
        margin-bottom: 10px;
    }
</style>

</head>
<body>

<div class="form-wrapper">

    <h2 style="text-align:center;">แก้ไขข้อมูลสินค้า</h2>

    <form method="POST" enctype="multipart/form-data">

        <label>รหัสสินค้า</label>
        <input type="text" name="product_code" value="<?= $product['product_code'] ?>" required>

        <label>ชื่อสินค้า</label>
        <input type="text" name="name" value="<?= $product['name'] ?>" required>

        <label>ประเภทสินค้า</label>
        <select name="category" required>
            <?php while($cat = $categories->fetch_assoc()): ?>
                <option value="<?= $cat['id'] ?>" 
                    <?= $cat['id'] == $product['category'] ? "selected" : "" ?>>
                    <?= $cat['category_name'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>จำนวนคงเหลือ</label>
        <input type="number" name="quantity" value="<?= $product['quantity'] ?>" required>

        <label>วันหมดอายุ</label>
        <input type="date" name="exp_date" value="<?= $product['exp_date'] ?>">

        <label>รูปปัจจุบัน</label><br>
        <?php if ($product['image']): ?>
            <img src="uploads/<?= $product['image'] ?>" class="product-img">
        <?php else: ?>
            <p style="color:#888;">ไม่มีรูป</p>
        <?php endif; ?>

        <label>เลือกรูปใหม่ (ถ้าต้องการ)</label>
        <input type="file" name="image">

        <button type="submit">บันทึกการเปลี่ยนแปลง</button>
    </form>

    <div style="text-align:center; margin-top: 10px;">
        <a href="product_list.php">← กลับไปหน้าสินค้า</a>
    </div>

</div>

</body>
</html>
