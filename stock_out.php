<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// --- Logic PHP: บันทึกการเบิกออก ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'stock_out') {
    $product_id = $_POST['product_id'];
    $qty_out = intval($_POST['quantity']);
    $note = $_POST['note']; // สาเหตุการเบิก

    // 1. เช็คก่อนว่ามีของพอให้ตัดไหม?
    $check_sql = "SELECT quantity FROM products WHERE id = $product_id";
    $check_result = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_result);
    $current_qty = $row['quantity'];

    if ($qty_out > 0) {
        if ($current_qty >= $qty_out) {
            // ของพอ -> ตัดสต็อก (-)
            $sql_update = "UPDATE products SET quantity = quantity - $qty_out WHERE id = $product_id";
            mysqli_query($conn, $sql_update);

            // บันทึกประวัติ (Transaction type = 'out')
            $sql_log = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, note) 
                        VALUES ('$product_id', 'out', '$qty_out', '$note')";
            mysqli_query($conn, $sql_log);

            echo "<script>alert('เบิกสินค้าเรียบร้อยแล้ว!'); window.location='stock_out.php';</script>";
        } else {
            // ของไม่พอ
            echo "<script>alert('ทำรายการไม่สำเร็จ! สินค้าในสต็อกมีไม่พอ (คงเหลือ $current_qty)'); window.location='stock_out.php';</script>";
        }
    } else {
        echo "<script>alert('กรุณาระบุจำนวนที่ถูกต้อง');</script>";
    }
}
?>
<style>
        /* --- ใช้ CSS ชุดเดิมจาก Dashboard --- */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #E5E5E5; }
        
        /* Sidebar */
        .sidebar { width: 250px; background-color: #356CB5; color: white; display: flex; flex-direction: column; position: fixed; height: 100%; left: 0; top: 0; z-index: 100; }
        .sidebar-header { padding: 20px; font-size: 20px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .menu-list { list-style: none; flex-grow: 1; padding-top: 10px; }
        .menu-list li a { display: flex; align-items: center; padding: 15px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 16px; transition: 0.3s; }
        .menu-list li a:hover, .menu-list li a.active { background-color: rgba(255,255,255,0.2); color: white; border-left: 4px solid white; }
        .menu-list li a i { width: 30px; font-size: 18px; }
        .sidebar-footer { margin-top: auto; width: 100%; flex-grow: 0 !important;}
        .sidebar-footer li { list-style: none; }
        .sidebar-footer li a { display: flex; align-items: center; padding: 15px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 16px; transition: 0.3s; }
        .sidebar-footer li a:hover { background-color: rgba(255,255,255,0.2); color: white; }
        .btn-logout { background-color: #D90429; color: white !important; }
        .btn-logout:hover { background-color: #b0021f; }

        /* Main Content */
        .main-content { margin-left: 250px; width: calc(100% - 250px); display: flex; flex-direction: column; }
        .top-navbar { height: 60px; background-color: white; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .nav-left i { font-size: 24px; cursor: pointer; color: #333; }
        .nav-right img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; }
        .content-container { padding: 30px; }
        .page-title { font-size: 28px; font-weight: bold; margin-bottom: 20px; color: #333; }

        /* --- ส่วนที่เพิ่มใหม่สำหรับหน้านี้ --- */
    </style>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เบิกออก/ตัดสต็อก - Onin Shop Stock</title>
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
            <li><a href="stock_in.php"><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            
            <li><a href="stock_out.php" class="active"><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            
        </ul>
        <div class="sidebar-footer">
            <li><a href="#" onclick="confirmLogout(); return false;" class="btn-logout"><i class="fa-solid fa-power-off"></i> ออกจากระบบ</a></li>
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
            <h2 class="page-title">เบิกออก/ตัดสต็อก (Stock Out)</h2>

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
                            <th>คงเหลือ</th>
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
                                // ถ้าของหมด ให้แสดงตัวหนังสือแดง และปุ่มกดไม่ได้
                                $is_out_of_stock = ($row['quantity'] <= 0);
                                $qty_style = $is_out_of_stock ? "color:red; font-weight:bold;" : "color:#356CB5; font-weight:bold;";
                                
                                echo "<tr>";
                                echo "<td>" . $row['product_code'] . "</td>";
                                echo "<td>" . $row['name'] . "</td>";
                                echo "<td>" . $row['category'] . "</td>";
                                echo "<td style='$qty_style'>" . $row['quantity'] . " " . $row['unit'] . "</td>";
                                echo "<td>";
                                
                                if (!$is_out_of_stock) {
                                    // ปุ่มสีส้ม (btn-out)
                                    echo "<button type='button' class='btn-action btn-out' 
                                            onclick=\"openStockOutModal('" . $row['id'] . "', '" . $row['name'] . "', '" . $row['product_code'] . "', " . $row['quantity'] . ")\">
                                            <i class='fa-solid fa-minus'></i> เบิกออก
                                          </button>";
                                } else {
                                    echo "<span style='color:#999; font-size:12px;'>สินค้าหมด</span>";
                                }
                                
                                echo "</td>";
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

    <div id="stockOutModal" class="modal-overlay">
        <div class="login-box" style="width: 500px;">
            <div class="header-text">
                <h2 style="color:#F48C06;">บันทึกการเบิกสินค้า</h2>
                <p id="modal_product_name" style="font-size:18px; color:#333; margin-top:10px;">-</p>
                <div style="display:flex; justify-content:center; gap:15px; margin-top:5px; color:#666; font-size:14px;">
                    <span id="modal_product_code">-</span>
                    <span id="modal_current_qty" style="color:#356CB5; font-weight:bold;">-</span>
                </div>
            </div>

            <form action="" method="POST" onsubmit="return validateStock()">
                <input type="hidden" name="action" value="stock_out">
                <input type="hidden" id="stock_product_id" name="product_id">
                <input type="hidden" id="max_qty" value="0"> <div class="form-group">
                    <label>จำนวนที่เบิกออก</label>
                    <input type="number" id="input_qty" name="quantity" required min="1" placeholder="ระบุจำนวน" style="font-size:20px; text-align:center;">
                </div>

                <div class="form-group">
                    <label>สาเหตุการเบิก / หมายเหตุ</label>
                    <select name="note" style="width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin-bottom:10px;">
                        <option value="ขายหน้าร้าน">ขายหน้าร้าน</option>
                        <option value="สินค้าเสียหาย/ชำรุด">สินค้าเสียหาย/ชำรุด</option>
                        <option value="เบิกใช้ภายในร้าน">เบิกใช้ภายในร้าน</option>
                        <option value="ของแถม">ของแถม</option>
                        <option value="อื่นๆ">อื่นๆ</option>
                    </select>
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn-cancel" onclick="closeStockOutModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" style="background-color:#F48C06;">ยืนยันตัดสต็อก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // เปิด Modal
        function openStockOutModal(id, name, code, currentQty) {
            document.getElementById('stock_product_id').value = id;
            document.getElementById('modal_product_name').innerText = name;
            document.getElementById('modal_product_code').innerText = "รหัส: " + code;
            document.getElementById('modal_current_qty').innerText = "คงเหลือปัจจุบัน: " + currentQty;
            
            // เก็บค่า Max ไว้เช็คกันคนกรอกเกิน
            document.getElementById('max_qty').value = currentQty;
            
            document.getElementById('stockOutModal').style.display = 'flex';
        }

        function closeStockOutModal() {
            document.getElementById('stockOutModal').style.display = 'none';
        }
        
        // เช็คก่อนส่งฟอร์ม ว่ากรอกเกินที่มีไหม (กันเหนียวฝั่ง JS)
        function validateStock() {
            var inputQty = parseInt(document.getElementById('input_qty').value);
            var maxQty = parseInt(document.getElementById('max_qty').value);
            
            if (inputQty > maxQty) {
                alert("ไม่สามารถเบิกสินค้าได้!\nจำนวนที่เบิก (" + inputQty + ") มากกว่าสินค้าคงเหลือ (" + maxQty + ")");
                return false; // ห้ามส่งฟอร์ม
            }
            return true; // ผ่าน
        }
        
        // ... (Script Logout) ...
    </script>

</body>
</html>