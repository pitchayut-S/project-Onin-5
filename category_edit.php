<?php
session_start();
require_once "db.php";

// ถ้ายังไม่ล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ถ้าไม่มี id ส่งมา
if (!isset($_GET['id'])) {
    echo "<script>alert('ไม่พบข้อมูลประเภทสินค้า'); window.location='category_list.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// ดึงข้อมูลหมวดหมู่ที่ต้องการแก้ไข
$stmt = $conn->prepare("SELECT category_name, prefix FROM product_category WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลประเภทสินค้า'); window.location='category_list.php';</script>";
    exit();
}

$category = $result->fetch_assoc();

// เมื่อกดบันทึกแก้ไข
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name === "" || $prefix === "") {
        echo "<script>alert('กรุณากรอกข้อมูลให้ครบ');</script>";
    } else {
        $update = $conn->prepare("UPDATE product_category SET category_name = ?, prefix = ? WHERE id = ?");
        $update->bind_param("ssi", $category_name, $prefix, $id);
        
        if ($update->execute()) {
            echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location='category_list.php';</script>";
            exit();
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด ไม่สามารถแก้ไขข้อมูลได้');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>แก้ไขประเภทสินค้า</title>

<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
    body {
        font-family: "Prompt", sans-serif;
        background:#f3f6fb;
    }

    .form-wrapper {
        width: 450px;
        background:#fff;
        padding: 25px;
        margin: 50px auto;
        border-radius: 14px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    h2 {
        text-align:center;
        margin-bottom:20px;
    }

    input {
        width: 100%;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 10px;
        border:1px solid #ccc;
        font-size:14px;
    }

    button {
        width: 100%;
        padding: 12px;
        background:#356CB5;
        color:white;
        border:none;
        border-radius:10px;
        font-size:16px;
        font-weight:600;
        cursor:pointer;
    }

    button:hover {
        background:#264a85;
    }

    a {
        text-decoration:none;
        color:#356CB5;
        font-weight:600;
    }
</style>
</head>

<body>

<div class="form-wrapper">

    <h2>แก้ไขประเภทสินค้า</h2>

    <form method="POST">

        <label>ชื่อประเภทสินค้า</label>
        <input type="text" name="category_name" 
               value="<?= htmlspecialchars($category['category_name']) ?>" required>

        <label>รหัสย่อประเภทสินค้า (Prefix)</label>
        <input type="text" name="prefix" maxlength="5"
               value="<?= htmlspecialchars($category['prefix']) ?>" required>

        <button type="submit">บันทึกการแก้ไข</button>

    </form>

    <div style="text-align:center; margin-top:15px;">
        <a href="category_list.php">← กลับหน้าประเภทสินค้า</a>
    </div>

</div>

</body>
</html>
