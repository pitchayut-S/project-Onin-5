<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ตรวจสอบว่ามี id ถูกส่งมาหรือไม่
if (!isset($_GET['id'])) {
    echo "<script>alert('ไม่พบข้อมูลที่ต้องการลบ'); window.location='category_list.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// ลบประเภทสินค้า
$stmt = $conn->prepare("DELETE FROM product_category WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "<script>alert('ลบประเภทสินค้าสำเร็จ'); window.location='category_list.php';</script>";
    exit();
} else {
    echo "<script>alert('เกิดข้อผิดพลาด ไม่สามารถลบข้อมูลได้'); window.location='category_list.php';</script>";
}
?>
