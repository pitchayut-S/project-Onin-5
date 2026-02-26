<?php
ob_start();
session_start();
require_once "db.php";

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ตรวจสอบว่ามี id ถูกส่งมาหรือไม่
if (!isset($_GET['id'])) {
    $_SESSION['msg_error'] = "ไม่พบสินค้าที่ต้องการลบ";
    header("Location: product_list.php");
    exit();
}

$id = intval($_GET['id']);

// 1. ดึงข้อมูลสินค้าเพื่อเอารูปภาพมาลบ
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['msg_error'] = "ไม่พบข้อมูลสินค้าในระบบ";
    header("Location: product_list.php");
    exit();
}

$product = $result->fetch_assoc();
$image_file = $product['image'];

// 2. ลบข้อมูลออกจากฐานข้อมูล
$delete = $conn->prepare("DELETE FROM products WHERE id = ?");
$delete->bind_param("i", $id);

if ($delete->execute()) {
    // 3. ลบไฟล์รูปภาพออกจากโฟลเดอร์ uploads (ถ้ามี)
    if (!empty($image_file) && file_exists("uploads/" . $image_file)) {
        unlink("uploads/" . $image_file);
    }

    // --- [จุดที่แก้ไข] ใช้ชื่อตัวแปรให้ตรงกับหน้า product_list.php ---
    $_SESSION['msg_success'] = "ลบข้อมูลสินค้าเรียบร้อยแล้ว";

} else {
    // กรณีลบไม่สำเร็จ
    $_SESSION['msg_error'] = "เกิดข้อผิดพลาด: " . $conn->error;
}

// 4. ดีดกลับไปหน้ารายการสินค้า
header("Location: product_list.php");
exit();
?>