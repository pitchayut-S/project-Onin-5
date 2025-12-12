<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}
$save_success = false;
$no_change = false;

// --- Logic PHP: บันทึกการปรับปรุงยอด ---
// --- แก้ไข Logic PHP: ปรับปรุงยอดแบบ Transaction ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'adjust_stock') {
    $product_id = $_POST['product_id'];
    $actual_qty = intval($_POST['actual_qty']);
    $note = $_POST['note'];

    // ดึงค่าปัจจุบันมาเทียบใน Transaction เพื่อความชัวร์
    mysqli_begin_transaction($conn);
    try {
        $check_sql = "SELECT quantity FROM products WHERE id = $product_id FOR UPDATE";
        $check_result = mysqli_query($conn, $check_sql);
        $row = mysqli_fetch_assoc($check_result);
        $current_qty = $row['quantity'];

        $diff = $actual_qty - $current_qty;

        if ($diff != 0) {
            // 1. อัปเดตสต็อก
            $sql_update = "UPDATE products SET quantity = $actual_qty WHERE id = $product_id";
            if (!mysqli_query($conn, $sql_update)) throw new Exception("Error Update");

            // 2. บันทึกประวัติ
            $type = ($diff > 0) ? 'in' : 'out';
            $qty_log = abs($diff); 
            $system_note = "ปรับปรุงสต็อก (" . ($diff > 0 ? "+" : "") . "$diff) : $note";

            $sql_log = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, note) 
                        VALUES ('$product_id', '$type', '$qty_log', '$system_note')";
            if (!mysqli_query($conn, $sql_log)) throw new Exception("Error Log");

            $save_success = true; // ให้ JS แสดง Modal Success
            mysqli_commit($conn);
        } else {
            $no_change = true; // ให้ JS แสดง Modal No Change
            mysqli_commit($conn); // ไม่มีอะไรแก้ แต่ต้องจบ transaction
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('เกิดข้อผิดพลาด: " . $e->getMessage() . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจนับและปรับปรุงสต็อก - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
            <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
            <li><a href="product_list.php"><i class="fa-solid fa-box-open"></i> ข้อมูลสินค้า</a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">ข้อมูลประเภทสินค้า</span></a></li>
            <li><a href="stock_in.php"><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="stock_out.php"><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            
            <li><a href="stock_adjust.php" class="active"><i class="fa-solid fa-clipboard-check"></i> ตรวจนับ/ปรับปรุง</a></li>
            
            <li><a href="report_low_stock.php"><i class="fa-solid fa-triangle-exclamation"></i> รายงานสินค้าใกล้หมด</a></li>
            <li><a href="stock_history.php"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติสต็อก</a></li>
        </ul>
        <div class="sidebar-footer menu-list">
            <li><a href="#"><i class="fa-solid fa-user-gear"></i> <span class="menu-text">การจัดการบัญชี</span></a></li>
            <li><a href="index.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i> <span class="menu-text">ออกจากระบบ</span></a></li>
        </div>
    </nav>

    <div class="main-content">
        <div class="top-navbar">
            <div class="nav-left">
                <i class="fa-solid fa-bars"></i> 
            </div>
            <div class="nav-right">
                <?php $user_display = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                <div style="display:flex; align-items:center; gap:10px;">
                    <span style="font-weight:500; color:#333;"><?php echo $user_display; ?></span>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_display); ?>&background=0D8ABC&color=fff" alt="User Profile">
                </div>
            </div>
        </div>

        <div class="content-container">
            <h2 class="page-title">ตรวจนับและปรับปรุงสต็อก (Stock Adjustment)</h2>

            <form method="GET" class="search-box">
                <input type="text" name="search" class="search-input" placeholder="ค้นหารหัส หรือ ชื่อสินค้า..." value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
                <button type="submit" class="btn-search">ค้นหา</button>
            </form>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>รหัสสินค้า</th>
                            <th>ชื่อสินค้า</th>
                            <th>หมวดหมู่</th>
                            <th>คงเหลือในระบบ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $search_q = isset($_GET['search']) ? $_GET['search'] : '';
                        $sql = "SELECT * FROM products WHERE name LIKE '%$search_q%' OR product_code LIKE '%$search_q%'";
                        $result = mysqli_query($conn, $sql);

                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row['product_code'] . "</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td style='font-weight:bold;'>" . $row['quantity'] . " " . $row['unit'] . "</td>";
                                echo "<td>
                                        <button type='button' class='btn-action btn-adjust' 
                                            onclick=\"openAdjustModal('" . $row['id'] . "', '" . $row['name'] . "', '" . $row['product_code'] . "', " . $row['quantity'] . ")\">
                                            <i class='fa-solid fa-pen-nib'></i> ปรับยอด
                                        </button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align:center;'>ไม่พบข้อมูล</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="adjustModal" class="modal-overlay">
        <div class="login-box" style="width: 500px;">
            <div class="header-text">
                <h2 style="color:#7209B7;">ปรับปรุงยอดสต็อก</h2>
                <p id="modal_product_name" style="font-size:18px; color:#333; margin-top:10px;">-</p>
                <p id="modal_product_code" style="font-size:14px; color:#666;">-</p>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="adjust_stock">
                <input type="hidden" id="adjust_product_id" name="product_id">
                <input type="hidden" id="current_qty_hidden" name="current_qty">

                <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px;">
                    <div class="form-group">
                        <label>จำนวนในระบบ</label>
                        <input type="text" id="display_current_qty" readonly style="background-color:#eee; text-align:center; font-weight:bold;">
                    </div>

                    <div class="form-group">
                        <label style="color:#7209B7;">นับได้จริง (Actual)</label>
                        <input type="number" id="input_actual_qty" name="actual_qty" required min="0" 
                               style="text-align:center; font-size:18px; border:2px solid #7209B7;" 
                               oninput="calculateDiff()">
                    </div>
                </div>

                <div style="text-align:center; margin-bottom:20px; padding:10px; background-color:#f9f9f9; border-radius:8px;">
                    <span style="font-size:14px; color:#666;">ผลต่าง (Variance):</span>
                    <span id="diff_display" style="font-size:24px; font-weight:bold; margin-left:10px;">0</span>
                    <div id="diff_desc" style="font-size:12px; margin-top:5px;">-</div>
                </div>

                <div class="form-group">
                    <label>หมายเหตุ / สาเหตุการปรับ</label>
                    <input type="text" name="note" placeholder="เช่น นับสต็อกประจำเดือน, ของชำรุด, เจอของเกิน" required>
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn-cancel" onclick="closeAdjustModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" style="background-color:#7209B7;">บันทึกยอดจริง</button>
                </div>
            </form>
        </div>
    </div>

    <div id="successModal" class="modal-overlay">
        <div class="login-box success-modal-content">
            <i class="fa-solid fa-circle-check success-icon"></i>
            <h2 style="color:#333; margin-bottom:10px;">บันทึกเรียบร้อย!</h2>
            <p style="color:#666; margin-bottom:20px;">ปรับปรุงยอดสต็อกสำเร็จแล้ว</p>
            <button class="btn-save" onclick="window.location.href='stock_adjust.php'">ตกลง</button>
        </div>
    </div>

    <div id="noChangeModal" class="modal-overlay">
        <div class="login-box success-modal-content">
            <i class="fa-solid fa-circle-exclamation success-icon" style="color: #FFC107;"></i>
            <h2 style="color:#333; margin-bottom:10px;">ไม่มีการเปลี่ยนแปลง</h2>
            <p style="color:#666; margin-bottom:20px;">ยอดสินค้าที่นับได้ เท่ากับยอดในระบบ</p>
            <button class="btn-save" style="background-color: #6c757d;" onclick="window.location.href='stock_adjust.php'">ตกลง</button>
        </div>
    </div>
    

    <script>
        // เปิด Modal
        function openAdjustModal(id, name, code, currentQty) {
            document.getElementById('adjust_product_id').value = id;
            document.getElementById('modal_product_name').innerText = name;
            document.getElementById('modal_product_code').innerText = "รหัส: " + code;
            
            // Set ค่าจำนวนปัจจุบัน
            document.getElementById('current_qty_hidden').value = currentQty;
            document.getElementById('display_current_qty').value = currentQty;
            
            // Reset ค่า Input
            document.getElementById('input_actual_qty').value = '';
            document.getElementById('diff_display').innerText = '0';
            document.getElementById('diff_display').className = '';
            document.getElementById('diff_desc').innerText = 'กรุณากรอกจำนวนที่นับได้จริง';
            
            document.getElementById('adjustModal').style.display = 'flex';
        }

        function closeAdjustModal() {
            document.getElementById('adjustModal').style.display = 'none';
        }

        // ฟังก์ชันคำนวณผลต่างแบบ Real-time
        function calculateDiff() {
            var current = parseInt(document.getElementById('current_qty_hidden').value);
            var actual = document.getElementById('input_actual_qty').value;
            var display = document.getElementById('diff_display');
            var desc = document.getElementById('diff_desc');

            if (actual === "") {
                display.innerText = "0";
                desc.innerText = "-";
                return;
            }

            actual = parseInt(actual);
            var diff = actual - current;
            var diffText = diff > 0 ? "+" + diff : diff;

            display.innerText = diffText;

            if (diff > 0) {
                display.className = "diff-plus"; // สีเขียว
                desc.innerText = "สินค้าเกินมา " + Math.abs(diff) + " ชิ้น";
                desc.style.color = "#28a745";
            } else if (diff < 0) {
                display.className = "diff-minus"; // สีแดง
                desc.innerText = "สินค้าหายไป " + Math.abs(diff) + " ชิ้น";
                desc.style.color = "#dc3545";
            } else {
                display.className = "";
                desc.innerText = "ยอดตรงกัน";
                desc.style.color = "#333";
            }
             document.getElementById('summaryModal').style.display = 'flex';
        }


        function closeSummaryModal() {
            document.getElementById('summaryModal').style.display = 'none';
        }

        function submitFinalForm() {
            // สั่ง submit ฟอร์มจริงๆ
            document.getElementById('adjustForm').submit();
        }

        // --- 3. แสดง Success Modal เมื่อ PHP ทำงานเสร็จ ---
        <?php if ($save_success): ?>
            document.getElementById('successModal').style.display = 'flex';
        <?php endif; ?>

        // แสดง No Change Modal เมื่อ PHP บอกว่าไม่มีการเปลี่ยนแปลง
        <?php if ($no_change): ?>
            document.getElementById('noChangeModal').style.display = 'flex';
        <?php endif; ?>
    </script>

</body>
</html>