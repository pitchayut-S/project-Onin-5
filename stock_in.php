<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// --- ส่วนบันทึกการรับเข้าสินค้า ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'stock_in') {
    $product_id = $_POST['product_id'];
    $qty_in = intval($_POST['quantity']); // จำนวนที่รับเข้า
    $note = $_POST['note']; // หมายเหตุ (เช่น รับจากใคร)
    
    if ($qty_in > 0) {
        // 1. อัปเดตจำนวนสินค้าในตาราง products (บวกเพิ่ม)
        $sql_update = "UPDATE products SET quantity = quantity + $qty_in WHERE id = $product_id";
        mysqli_query($conn, $sql_update);

        // 2. บันทึกลงตารางประวัติ (stock_transactions)
        $sql_log = "INSERT INTO stock_transactions (product_id, transaction_type, quantity, note) 
                    VALUES ('$product_id', 'in', '$qty_in', '$note')";
        mysqli_query($conn, $sql_log);

        echo "<script>alert('รับเข้าสินค้าจำนวน $qty_in ชิ้น เรียบร้อยแล้ว!'); window.location='stock_in.php';</script>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รับเข้าสินค้า - Onin Shop Stock</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

   <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
            <li><a href="dashboard.php"><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Dashboard</span></a></li>
            <li><a href="product_list.php"><i class="fa-solid fa-box-open"></i> <span class="menu-text">ข้อมูลสินค้า</span></a></li>
            <li><a href="#"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">ข้อมูลประเภทสินค้า</span></a></li>
            <li><a href="stock_in.php" class="active"><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="#"><i class="fa-solid fa-heart"></i> <span class="menu-text">สินค้ายอดนิยม</span></a></li>
            <li><a href="#"><i class="fa-solid fa-file-invoice"></i> <span class="menu-text">รายงาน</span></a></li>
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
            <h2 class="page-title">รับเข้าสินค้า (Stock In)</h2>

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
                            <th>คงเหลือปัจจุบัน</th>
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
                                echo "<td style='font-weight:bold; color:#356CB5;'>" . $row['quantity'] . " " . $row['unit'] . "</td>";
                                echo "<td>
                                        <button type='button' class='btn-add' style='padding:5px 15px; font-size:14px;' 
                                            onclick=\"openStockInModal('" . $row['id'] . "', '" . $row['name'] . "', '" . $row['product_code'] . "')\">
                                            <i class='fa-solid fa-plus-circle'></i> รับเข้า
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

    <div id="stockInModal" class="modal-overlay">
        <div class="login-box" style="width: 500px;">
            <div class="header-text">
                <h2 style="color:#356CB5;">บันทึกรับเข้าสินค้า</h2>
                <p id="modal_product_name" style="font-size:18px; color:#333; margin-top:10px;">-</p>
                <p id="modal_product_code" style="font-size:14px; color:#666;">-</p>
            </div>

            <form action="" method="POST">
                <input type="hidden" name="action" value="stock_in">
                <input type="hidden" id="stock_product_id" name="product_id">

                <div class="form-group">
                    <label>จำนวนที่รับเข้า</label>
                    <input type="number" name="quantity" placeholder="ระบุจำนวน" required min="1" style="font-size:20px; text-align:center;">
                </div>

                <div class="form-group">
                    <label>หมายเหตุ / รับจากใคร (Optional)</label>
                    <input type="text" name="note" placeholder="เช่น บิลเลขที่ xxx, แม็คโคร">
                </div>

                <div class="modal-footer" style="justify-content: center;">
                    <button type="button" class="btn-cancel" onclick="closeStockInModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" style="background-color:#28a745;">ยืนยันรับเข้า</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openStockInModal(id, name, code) {
            document.getElementById('stock_product_id').value = id;
            document.getElementById('modal_product_name').innerText = name;
            document.getElementById('modal_product_code').innerText = "รหัส: " + code;
            document.getElementById('stockInModal').style.display = 'flex';
        }

        function closeStockInModal() {
            document.getElementById('stockInModal').style.display = 'none';
        }
    </script>

</body>
</html>