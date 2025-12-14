<?php
session_start();
require_once "db.php";

// --- เพิ่มส่วนนี้เข้าไปครับ ---
// ตรวจสอบว่า Log in หรือยัง
if (!isset($_SESSION['username'])) { 
    header("Location: index.php"); 
    exit(); 
}

// ตรวจสอบว่าเป็น Admin หรือไม่? (ถ้าไม่ใช่ ดีดออกไปหน้าอื่น)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // ให้เด้งกลับไปหน้าขายของ หรือหน้าแจ้งเตือน
    echo "<script>alert('คุณไม่มีสิทธิ์เข้าถึงหน้านี้'); window.location='product_Stock.php';</script>";
    exit();
}
