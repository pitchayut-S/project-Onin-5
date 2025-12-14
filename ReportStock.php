<?php
session_start();
require_once "db.php";

// เช็คสิทธิ์ Login
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ---------------------------------------------------------
// รับค่า Filter
// ---------------------------------------------------------
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$type_filter = isset($_GET['type']) ? $_GET['type'] : "all";
$date_start = isset($_GET['start']) ? $_GET['start'] : "";
$date_end = isset($_GET['end']) ? $_GET['end'] : "";

// ---------------------------------------------------------
// SQL QUERY
// ---------------------------------------------------------
$sql = "
    SELECT t.*, p.product_code, p.name as product_name, p.image
    FROM stock_transactions t
    LEFT JOIN products p ON t.product_id = p.id
    WHERE 1
";

if ($search !== "") {
    $like = "%" . $conn->real_escape_string($search) . "%";
    $sql .= " AND (p.name LIKE '$like' OR p.product_code LIKE '$like' OR t.username LIKE '$like' OR t.reason LIKE '$like')";
}

if ($type_filter !== "all") {
    $sql .= " AND t.type = '$type_filter'";
}

if ($date_start !== "" && $date_end !== "") {
    $sql .= " AND DATE(t.created_at) BETWEEN '$date_start' AND '$date_end'";
}

$sql .= " ORDER BY t.created_at DESC LIMIT 300";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานประวัติสต๊อก - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; font-family: 'Prompt', sans-serif; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; color:#2c3e50; }

        /* Search Box */
        .search-box {
            background: #fff; padding: 18px 20px; border-radius: 14px;
            display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
            margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .search-box input, .search-box select {
            border: none; background: #eef2f6; 
            padding: 12px 14px; border-radius: 10px; font-family: 'Prompt';
            font-size: 14px; outline: none;
        }
        .search-input-main { flex: 1; min-width: 200px; }
        .search-select { width: 150px; }
        .search-date { width: 140px; }
        .date-label { font-size: 13px; color: #7f8c8d; font-weight: 600; margin: 0 5px; }

        .btn-search { 
            background: #356CB5; color: white; padding: 10px 20px; border-radius: 10px; 
            border: none; cursor: pointer; font-weight: 600; height: 42px; 
            transition: 0.2s;
        }
        .btn-search:hover { background: #285291; }

        .btn-reset {
            background: #ecf0f1; color: #7f8c8d; padding: 10px 15px; border-radius: 10px;
            text-decoration: none; display: flex; align-items: center; height: 42px; box-sizing: border-box;
            transition: 0.2s;
        }
        .btn-reset:hover { background: #dce1e2; color: #333; }

        /* Table */
        table {
            width: 100%; border-collapse: separate; border-spacing: 0;
            background: #fff; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        th { background: #f8f9fa; padding: 15px; text-align: left; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #eee; }
        td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; }
        
        /* User Badge (ปรับปรุงใหม่) */
        .user-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 30px;
            font-size: 13px; font-weight: 600;
            border: 1px solid transparent;
        }
        .user-icon { 
            width: 24px; height: 24px; 
            border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            font-size: 11px; background: rgba(0,0,0,0.1); 
        }

        .badge-type { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .type-add { background: #e8f8f5; color: #27ae60; border: 1px solid #a9dfbf; }
        .type-reduce { background: #fdedec; color: #c0392b; border: 1px solid #f5b7b1; }

        .product-info { display: flex; align-items: center; gap: 10px; }
        .p-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid #eee; }
        .p-name { font-weight: 600; color: #333; }
        .p-code { font-size: 12px; color: #999; }

        .date-text { color: #555; font-weight: 500; }
        .time-text { color: #999; font-size: 12px; }
        .reason-text { font-size: 14px; color: #333; font-weight: 500; }
        .supplier-text { font-size: 12px; color: #e67e22; margin-top: 3px; display: flex; align-items: center; gap: 4px;}
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">

        <div class="page-title">ประวัติการปรับสต๊อก</div>

        <form class="search-box" method="get">
            <input type="text" name="search" class="search-input-main" 
                   placeholder="ค้นหาชื่อสินค้า, รหัส, User หรือ เหตุผล..." 
                   value="<?= htmlspecialchars($search) ?>">
            
            <select name="type" class="search-select">
                <option value="all" <?= $type_filter=='all'?'selected':'' ?>>ทั้งหมด</option>
                <option value="add" <?= $type_filter=='add'?'selected':'' ?>>+ เพิ่ม (เข้า)</option>
                <option value="reduce" <?= $type_filter=='reduce'?'selected':'' ?>>- ลด (ออก)</option>
            </select>

            <span class="date-label">จาก</span>
            <input type="date" name="start" class="search-date" value="<?= $date_start ?>">
            <span class="date-label">ถึง</span>
            <input type="date" name="end" class="search-date" value="<?= $date_end ?>">

            <button type="submit" class="btn-search">ค้นหา</button>
            <?php if($search!="" || $type_filter!="all" || $date_start!=""): ?>
                <a href="ReportStock.php" class="btn-reset">ล้าง</a>
            <?php endif; ?>
        </form>

        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th width="15%">วัน/เวลา</th>
                        <th width="15%">ผู้ทำรายการ</th>
                        <th width="30%">สินค้า</th>
                        <th width="10%">ประเภท</th>
                        <th width="10%">จำนวน</th>
                        <th width="20%">เหตุผล / รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): 
                        $isAdd = ($row['type'] == 'add');
                        $timestamp = strtotime($row['created_at']);
                        $date = date("d/m/Y", $timestamp);
                        $time = date("H:i", $timestamp);

                        // --- ระบบสุ่มสีตามชื่อ User ---
                        $u_name = $row['username'] ?? 'System';
                        $first_char = mb_substr(strtoupper($u_name), 0, 1);
                        
                        // ชุดสี (Background, Text Color)
                        $color_palettes = [
                            ['#e3f2fd', '#1565c0'], // ฟ้า
                            ['#e8f5e9', '#2e7d32'], // เขียว
                            ['#f3e5f5', '#7b1fa2'], // ม่วง
                            ['#fff3e0', '#ef6c00'], // ส้ม
                            ['#e0f7fa', '#00838f'], // ฟ้าอมเขียว
                            ['#fce4ec', '#c2185b'], // ชมพู
                        ];

                        // ถ้าเป็น Admin ให้ใช้สีแดง/ชมพูเสมอ
                        if (strtolower($u_name) == 'admin' || strtolower($u_name) == 'administrator') {
                            $theme = ['#ffebee', '#c62828']; // แดง
                        } else {
                            // ถ้าไม่ใช่ Admin ให้สุ่มตามชื่อ (จะได้สีเดิมตลอดสำหรับคนเดิม)
                            $index = crc32($u_name) % count($color_palettes);
                            $theme = $color_palettes[abs($index)];
                        }
                    ?>
                    <tr>
                        <td>
                            <div class="date-text"><?= $date ?></div>
                            <div class="time-text"><?= $time ?> น.</div>
                        </td>
                        <td>
                            <div class="user-badge" style="background: <?= $theme[0] ?>; color: <?= $theme[1] ?>;">
                                <div class="user-icon"><i class="fa-solid fa-user"></i></div>
                                <?= htmlspecialchars($u_name) ?>
                            </div>
                        </td>
                        <td>
                            <div class="product-info">
                                <?php if($row['image']): ?>
                                    <img src="uploads/<?= $row['image'] ?>" class="p-img">
                                <?php else: ?>
                                    <div class="p-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="fa-solid fa-box"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="p-name"><?= $row['product_name'] ?></div>
                                    <div class="p-code"><?= $row['product_code'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if($isAdd): ?>
                                <span class="badge-type type-add"><i class="fa-solid fa-arrow-up"></i> เพิ่ม</span>
                            <?php else: ?>
                                <span class="badge-type type-reduce"><i class="fa-solid fa-arrow-down"></i> ลด</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:bold; font-size:16px; color: #333;">
                            <?= number_format($row['amount']) ?>
                        </td>
                        <td>
                            <div class="reason-text"><?= $row['reason'] ?></div>
                            <?php if(!empty($row['supplier'])): ?>
                                <div class="supplier-text">
                                    <i class="fa-solid fa-truck"></i> จาก: <?= $row['supplier'] ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:#999;">
                            <i class="fa-regular fa-folder-open" style="font-size:30px; margin-bottom:10px;"></i><br>
                            ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>