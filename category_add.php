<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// เมื่อกดบันทึกประเภทสินค้า
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_name = trim($_POST['category_name']);
    $prefix        = strtoupper(trim($_POST['prefix'])); // บังคับให้เป็นตัวพิมพ์ใหญ่

    if ($category_name === "" || $prefix === "") {
        echo "<script>alert('กรุณากรอกข้อมูลให้ครบ');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO product_category (category_name, prefix) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $prefix);

        if ($stmt->execute()) {
            echo "<script>alert('เพิ่มประเภทสินค้าสำเร็จ!'); window.location='category_list.php';</script>";
            exit();
        } else {
            echo "<script>alert('เกิดข้อผิดพลาด ไม่สามารถบันทึกข้อมูลได้');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>เพิ่มประเภทสินค้า</title>

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

    <h2>เพิ่มประเภทสินค้า</h2>

    <form method="POST">

        <label>รหัสย่อประเภทสินค้า</label>
        <input type="text" name="prefix" maxlength="5" placeholder="เช่น CN, KD, HB" required>

        <label>ชื่อประเภทสินค้า</label>
        <input type="text" name="category_name" placeholder="เช่น ขนม, เครื่องดื่ม, อาหารแห้ง" required>

        <button type="submit">บันทึกประเภทสินค้า</button>

    </form>

    <div style="text-align:center; margin-top:15px;">
        <a href="category_list.php">← กลับหน้าประเภทสินค้า</a>
    </div>

</div>

</body>
</html>
