<?php
session_start();
require_once "db.php";

if (!isset($_POST['id'])) {
    die("ไม่พบข้อมูลสินค้า");
}

$id = intval($_POST['id']);
$action = $_POST['action_type'];
$qty_change = intval($_POST['change_qty']);
$note = $_POST['note'] ?? "";

$sql = "SELECT quantity FROM products WHERE id = $id";
$result = $conn->query($sql);
$product = $result->fetch_assoc();

$old_qty = $product['quantity'];

if ($action == "increase") {
    $new_qty = $old_qty + $qty_change;
} else {
    $new_qty = max(0, $old_qty - $qty_change);
}

// อัปเดตสต๊อกสินค้า
$update = $conn->query("UPDATE products SET quantity = $new_qty WHERE id = $id");

// บันทึกประวัติ
$conn->query("
INSERT INTO stock_history (product_id, old_qty, change_qty, new_qty, action_type, note)
VALUES ($id, $old_qty, $qty_change, $new_qty, '$action', '$note')
");

echo "<script>alert('บันทึกการปรับสต๊อกสำเร็จ'); window.location='product_Stock.php';</script>";
exit();
?>
