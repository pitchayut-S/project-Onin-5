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
   ADD CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        $stmt = $conn->prepare("INSERT INTO product_category (category_name, prefix) VALUES (?, ?)");
        $stmt->bind_param("ss", $category_name, $prefix);
        
        if ($stmt->execute()) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'สำเร็จ',
                'text' => 'เพิ่มประเภทสินค้าเรียบร้อยแล้ว'
            ];
        } else {
             $_SESSION['swal'] = [
                'icon' => 'error',
                'title' => 'ผิดพลาด',
                'text' => 'ไม่สามารถเพิ่มข้อมูลได้ (Prefix อาจซ้ำ)'
            ];
        }
    }
    header("Location: category_list.php");
    exit();
}

/* ======================
   EDIT CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_category'])) {
    $id = intval($_POST['id']);
    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        $stmt = $conn->prepare("UPDATE product_category SET category_name = ?, prefix = ? WHERE id = ?");
        $stmt->bind_param("ssi", $category_name, $prefix, $id);
        
        if ($stmt->execute()) {
            $_SESSION['swal'] = [
                'icon' => 'success',
                'title' => 'สำเร็จ',
                'text' => 'แก้ไขข้อมูลเรียบร้อยแล้ว'
            ];
        }
    }
    header("Location: category_list.php");
    exit();
}

/* ======================
   SEARCH
====================== */
$search_text = $_GET['search'] ?? "";
$sql = "SELECT * FROM product_category";
$stmt = null;
$result = null;

if ($search_text !== "") {
    $sql .= " WHERE category_name LIKE ? OR prefix LIKE ?";
    $sql .= " ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
    $like = "%{$search_text}%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql .= " ORDER BY id DESC";
    $result = $conn->query($sql);
}

$categories = $result; 
if ($stmt) { $stmt->close(); }
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลประเภทสินค้า - Onin Shop Stock</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="sidebar">
        <div class="sidebar-header">Onin Shop Stock</div>
        <ul class="menu-list">
           <li><a href="dashboard.php" ><i class="fa-solid fa-chart-line"></i> <span class="menu-text">Dashboard</span></a></li>
            <li><a href="product_list.php"><i class="fa-solid fa-box-open"></i> <span class="menu-text">ข้อมูลสินค้า</span></a></li>
            <li><a href="category_list.php" class="active"><i class="fa-solid fa-clipboard-check"></i> <span class="menu-text">ข้อมูลประเภทสินค้า</span></a></li>
            <li><a href="stock_in.php" ><i class="fa-solid fa-dolly"></i> รับเข้าสินค้า</a></li>
            <li><a href="stock_out.php" ><i class="fa-solid fa-boxes-packing"></i> เบิกออก/ตัดสต็อก</a></li>
            <li><a href="stock_adjust.php" ><i class="fa-solid fa-clipboard-check"></i> ตรวจนับ/ปรับปรุง</a></li>
            <li><a href="#"><i class="fa-solid fa-heart"></i> <span class="menu-text">สินค้ายอดนิยม</span></a></li>
            <li><a href="report_low_stock.php"><i class="fa-solid fa-triangle-exclamation"></i> <span class="menu-text">รายงานสินค้าใกล้หมด</span></a></li>
            <li><a href="stock_history.php"><i class="fa-solid fa-clock-rotate-left"></i> ประวัติสต็อก</a></li>
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
            <h2 class="page-title">ข้อมูลประเภทสินค้า</h2>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <form method="GET" class="search-box" style="margin-bottom: 0; width: 60%;">
                    <input type="text" name="search" class="search-input" placeholder="ค้นหาชื่อประเภท หรือ Prefix..." value="<?= htmlspecialchars($search_text) ?>">
                    <button type="submit" class="btn-search">ค้นหา</button>
                    <?php if ($search_text !== ""): ?>
                        <a href="category_list.php" style="margin-left: 10px; color: #666; text-decoration: none;">ล้างค่า</a>
                    <?php endif; ?>
                </form>

                <button class="btn-add" onclick="openAddModal()">
                    <i class="fa-solid fa-plus"></i> เพิ่มประเภทสินค้า
                </button>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th width="10%">ลำดับ</th>
                            <th width="20%">Prefix (รหัสย่อ)</th>
                            <th width="50%">ชื่อประเภทสินค้า</th>
                            <th width="20%">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories->num_rows > 0): ?>
                            <?php $i=1; while($row = $categories->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><span style="background:#eef2f6; padding:4px 8px; border-radius:5px; font-weight:500;"><?= htmlspecialchars($row['prefix']) ?></span></td>
                                <td><?= htmlspecialchars($row['category_name']) ?></td>
                                <td>
                                    <button class="btn-action btn-edit" 
                                        onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['prefix']) ?>', '<?= htmlspecialchars($row['category_name']) ?>')">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <button class="btn-action btn-delete" 
                                        onclick="confirmDelete(<?= $row['id'] ?>)">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">ไม่พบข้อมูล</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="addModal" class="modal-overlay">
        <div class="login-box" style="width: 500px;">
            <div class="header-text">
                <h2>เพิ่มประเภทสินค้า</h2>
            </div>
            <form method="post">
                <input type="hidden" name="add_category" value="1">
                
                <div class="form-group">
                    <label>Prefix (อักษรย่อภาษาอังกฤษ)</label>
                    <input type="text" name="prefix" placeholder="เช่น SN, DR, HM" required style="text-transform:uppercase;">
                    <small style="color:#888;">ใช้สำหรับสร้างรหัสสินค้าอัตโนมัติ (เช่น SN001)</small>
                </div>

                <div class="form-group">
                    <label>ชื่อประเภทสินค้า</label>
                    <input type="text" name="category_name" placeholder="เช่น ขนม, เครื่องดื่ม" required>
                </div>

                <div class="modal-footer" style="justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editModal" class="modal-overlay">
        <div class="login-box" style="width: 500px;">
            <div class="header-text">
                <h2>แก้ไขประเภทสินค้า</h2>
            </div>
            <form method="post">
                <input type="hidden" name="edit_category" value="1">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group">
                    <label>Prefix (อักษรย่อ)</label>
                    <input type="text" name="prefix" id="edit-prefix" required style="text-transform:uppercase;">
                </div>

                <div class="form-group">
                    <label>ชื่อประเภทสินค้า</label>
                    <input type="text" name="category_name" id="edit-name" required>
                </div>

                <div class="modal-footer" style="justify-content: flex-end; gap: 10px; margin-top: 20px;">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>

    <div id="logoutModal" class="modal-overlay">
        <div class="login-box logout-modal-content">
            <i class="fa-solid fa-right-from-bracket logout-icon"></i>
            <h2 class="logout-title">ยืนยันการออกจากระบบ</h2>
            <p class="logout-desc">คุณต้องการออกจากระบบใช่หรือไม่?</p>
            <div style="display: flex; justify-content: center; gap: 15px;">
                <button class="btn-cancel" onclick="closeLogoutModal()">ยกเลิก</button>
                <a href="logout.php" class="btn-confirm-logout">ออกจากระบบ</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }
        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(id, prefix, name) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-prefix').value = prefix;
            document.getElementById('edit-name').value = name;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Logout Functions
        function confirmLogout() {
            document.getElementById('logoutModal').style.display = 'flex';
        }
        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        // Delete Function (SweetAlert)
        function confirmDelete(id) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'ข้อมูลนี้จะไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'category_delete.php?id=' + id;
                }
            });
        }
    </script>

    <?php if (isset($_SESSION['swal'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['swal']['icon'] ?>',
            title: '<?= $_SESSION['swal']['title'] ?>',
            text: '<?= $_SESSION['swal']['text'] ?>',
            showConfirmButton: false,
            timer: 1500
        });
    </script>
    <?php unset($_SESSION['swal']); endif; ?>

</body>
</html>