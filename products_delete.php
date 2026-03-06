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

// 1. ดึงข้อมูลสินค้าเพื่อเอารูปภาพมาลบ และดึงข้อมูลเพื่อบันทึกประวัติ
$stmt = $conn->prepare("SELECT name, quantity, image FROM products WHERE id = ?");
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

// 2. อัปเดตข้อมูลเป็น "ลบแล้ว" แทนการลบทิ้งจริง (Soft Delete)
$username = $_SESSION['username'];
$delete = $conn->prepare("UPDATE products SET is_deleted = 1, deleted_by = ?, deleted_at = NOW() WHERE id = ?");
$delete->bind_param("si", $username, $id);

if ($delete->execute()) {
    // 3. (ทางเลือก) ถ่ายรูปอาจเก็บไว้ก่อนยังไม่ต้องลบ หรือลบก็ได้ 
    // ในที่นี้ขอ Comment การลบรูปทิ้งไว้ เผื่ออยากกู้คืนในอนาคตครับ
    /*
    if (!empty($image_file) && file_exists("uploads/" . $image_file)) {
        unlink("uploads/" . $image_file);
    }
    */

    // 4. บันทึกประวัติการลบลงในตารางประวัติสต็อก (เพื่อให้แสดงใน ReportStock.php)
    $stmt_tr = $conn->prepare("INSERT INTO stock_transactions (product_id, type, amount, balance, username, reason, created_at) VALUES (?, 'reduce', ?, 0, ?, 'ลบสินค้าออกจากระบบ', NOW())");
    $qty = $product['quantity'];
    $stmt_tr->bind_param("iis", $id, $qty, $username);
    $stmt_tr->execute();

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