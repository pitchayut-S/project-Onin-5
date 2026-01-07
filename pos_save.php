<?php
// เริ่มต้น Buffer
ob_start();

session_start();
header('Content-Type: application/json');

// --- 1. ฟังก์ชันพิเศษ: ดักจับ Fatal Error (กันหน้าขาว) ---
function fatalErrorHandler() {
    $error = error_get_last();
    // ถ้ามี Error ร้ายแรงเกิดขึ้น ให้ส่ง JSON กลับไปบอก
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean(); // ล้างค่าเดิมทิ้ง
        echo json_encode(['status' => 'error', 'message' => 'Fatal Error: ' . $error['message'] . ' on line ' . $error['line']]);
    }
}
register_shutdown_function('fatalErrorHandler');
// -----------------------------------------------------

// ปิดการโชว์ Error ปกติ (เพราะเราจะคุมเอง)
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once "db.php";

$response = [];

try {
    // 2. เช็ค Login
    if (!isset($_SESSION['username'])) {
        throw new Exception("กรุณาเข้าสู่ระบบก่อนทำรายการ");
    }
    $current_user = $_SESSION['username'];

    // 3. รับข้อมูล JSON
    $input_data = file_get_contents('php://input');
    $input = json_decode($input_data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("รับข้อมูลผิดพลาด (Invalid JSON)");
    }

    $cart = $input['cart'] ?? [];
    if (empty($cart)) {
        throw new Exception("ไม่มีสินค้าในตะกร้า");
    }

    // 4. เริ่ม Transaction
    mysqli_begin_transaction($conn);

    $order_ref = "POS-" . date("ymd-His") . "-" . rand(100, 999);

    foreach ($cart as $item) {
        $product_id = intval($item['id']);
        $qty = intval($item['qty']);

        if ($qty <= 0) continue;

        // --- เช็คสต็อก ---
        $sql_check = "SELECT quantity, name FROM products WHERE id = ? FOR UPDATE";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $product_id);
        $stmt_check->execute();
        $res = $stmt_check->get_result();
        $row = $res->fetch_assoc();
        $stmt_check->close();

        if (!$row) throw new Exception("ไม่พบสินค้า ID: $product_id");
        if ($row['quantity'] < $qty) throw new Exception("สินค้า '{$row['name']}' ของไม่พอ (เหลือ {$row['quantity']})");

        // --- ตัดสต็อก ---
        $sql_update = "UPDATE products SET quantity = quantity - ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $qty, $product_id);
        $result_update = $stmt_update->execute();
        $stmt_update->close();

        if (!$result_update) throw new Exception("ตัดสต็อกไม่สำเร็จ");

        // --- บันทึกประวัติ (เพิ่ม supplier เป็น NULL เพื่อความชัวร์) ---
        // ตรวจสอบชื่อตารางให้ดี: stock_transactions หรือ stock_log
        $reason = "ขายหน้าร้าน ";
        
        // SQL นี้รองรับตารางตามรูปภาพที่คุณส่งมา
        $sql_log = "INSERT INTO stock_transactions (ref_code, product_id, type, amount, reason, username) VALUES (?, ?, 'reduce', ?, ?, ?)";
        
        $stmt_log = $conn->prepare($sql_log);
        if (!$stmt_log) {
             throw new Exception("SQL Error: " . $conn->error);
        }

        // i=int, i=int, s=string, s=string
        $stmt_log->bind_param("siiss", $order_ref, $product_id, $qty, $reason, $current_user);
        $result_log = $stmt_log->execute();
        
        if (!$result_log) {
            throw new Exception("บันทึกประวัติไม่ได้: " . $stmt_log->error);
        }
        $stmt_log->close();
    }

    // ยืนยัน Transaction
    mysqli_commit($conn);
    $response = ['status' => 'success'];

} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    $response = ['status' => 'error', 'message' => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
exit();
?>