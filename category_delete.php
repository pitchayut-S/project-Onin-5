<?php
session_start();
require_once "db.php";

/* ======================
   AUTH CHECK
====================== */
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* ======================
   DELETE CATEGORY
====================== */
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 1. ตรวจสอบว่ามีสินค้าใดใช้ประเภทนี้อยู่หรือไม่
    // (สมมติว่าตารางสินค้าชื่อ 'products' และคอลัมน์ประเภทสินค้าชื่อ 'category_id')
    // ** คุณอาจจะต้องปรับ logic ตรงนี้ถ้าโครงสร้างตาราง products ไม่ได้เก็บเป็น id **

    // ถ้าตาราง products เก็บชื่อ category ตรงๆ
    $check_sql = "SELECT COUNT(*) AS count FROM products WHERE category = ?";
    $check_stmt = $conn->prepare($check_sql);
    
    // ดึงชื่อ Category ที่กำลังจะลบออกมา (เพื่อใช้ใน WHERE ของตาราง products)
    $category_name_sql = "SELECT category_name FROM product_category WHERE id = ?";
    $name_stmt = $conn->prepare($category_name_sql);
    $name_stmt->bind_param("i", $id);
    $name_stmt->execute();
    $name_result = $name_stmt->get_result();
    $category_to_delete = $name_result->fetch_assoc()['category_name'];
    $name_stmt->close();
    
    $check_stmt->bind_param("s", $category_to_delete);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    $check_stmt->close();


    if ($count > 0) {
        // มีสินค้าใช้ประเภทนี้อยู่ - ห้ามลบ
        $_SESSION['swal'] = [
            'icon' => 'error',
            'title' => 'ลบไม่สำเร็จ',
            'text' => "ไม่สามารถลบได้ เนื่องจากมีสินค้า ({$count} รายการ) ใช้ประเภทนี้อยู่"
        ];
    } else {
        // 2. ลบได้
        $delete_sql = "DELETE FROM product_category WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $id);

        if ($delete_stmt->execute()) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'ลบสำเร็จ',
                'text' => 'ลบประเภทสินค้าเรียบร้อยแล้ว'
            ];
        } else {
            $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'ผิดพลาด',
                'text' => 'ไม่สามารถลบข้อมูลได้'
            ];
        }
        $delete_stmt->close();
    }

}

header("Location: category_list.php");
exit();
?>