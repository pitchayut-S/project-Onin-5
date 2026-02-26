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

$stmt = $conn->prepare("DELETE FROM product_category WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$_SESSION['swal'] = [
    'icon' => 'success',
    'title' => 'ลบสำเร็จ',
    'text' => 'ลบประเภทสินค้าเรียบร้อยแล้ว'
];

header("Location: category_list.php");
exit();
