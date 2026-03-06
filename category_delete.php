<?php
ob_start();
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: category_list.php");
    exit();
}

$id = intval($_GET['id']);

// ตรวจสอบว่ายังมีสินค้าอยู่ในประเภทนี้หรือไม่
$check_stmt = $conn->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
$check_stmt->bind_param("i", $id);
$check_stmt->execute();
$check_stmt->bind_result($product_count);
$check_stmt->fetch();
$check_stmt->close();

if ($product_count > 0) {
    $_SESSION['swal'] = [
        'icon' => 'error',
        'title' => 'ไม่สามารถลบได้',
        'text' => 'เนื่องจากยังมีสินค้าอยู่ในประเภทนี้ กรุณาย้ายหรือลบสินค้าก่อน',
        'timer' => 1500
    ];
} else {
    // ไม่มีสินค้าแล้ว ทำการลบประเภทสินค้า
    $stmt = $conn->prepare("DELETE FROM product_category WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'ลบสำเร็จ',
            'text' => 'ลบประเภทสินค้าเรียบร้อยแล้ว'
        ];
    } else {
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'ข้อผิดพลาด',
            'text' => 'ไม่สามารถลบประเภทสินค้าได้',
            'timer' => 1500
        ];
    }
    $stmt->close();
}

header("Location: category_list.php");
exit();
