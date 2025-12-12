<?php
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ตรวจสอบว่ามี id ถูกส่งมา
if (!isset($_GET['id'])) {
    echo "<script>alert('ไม่พบสินค้าที่ต้องการลบ'); window.location='product_list.php';</script>";
    exit();
}

$id = intval($_GET['id']);

// ดึงข้อมูลสินค้าเพื่อตรวจสอบรูปภาพ
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('ไม่พบข้อมูลสินค้า'); window.location='product_list.php';</script>";
    exit();
}

$product = $result->fetch_assoc();
$image_file = $product['image'];

// ลบข้อมูลออกจากฐานข้อมูล
$delete = $conn->prepare("DELETE FROM products WHERE id = ?");
$delete->bind_param("i", $id);

if ($delete->execute()) {

    // ลบรูปในโฟลเดอร์ uploads ถ้ามี
    if (!empty($image_file) && file_exists("uploads/" . $image_file)) {
        unlink("uploads/" . $image_file);
    }

    echo "<script>alert('ลบสินค้าเรียบร้อย!'); window.location='product_list.php';</script>";
    exit();

} else {
    echo "<script>alert('เกิดข้อผิดพลาด ไม่สามารถลบสินค้าได้'); window.location='product_list.php';</script>";
}
?>
