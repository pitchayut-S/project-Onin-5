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
// เราเลือกข้อมูลดิบออกมาก่อน แล้วค่อยมาจัดกลุ่มใน PHP
$sql = "
    SELECT t.*, p.product_code, p.name as product_name, p.image
    FROM stock_transactions t
    LEFT JOIN products p ON t.product_id = p.id
    WHERE 1
";

if ($search !== "") {
    $like = "%" . $conn->real_escape_string($search) . "%";
    // ค้นหาครอบคลุมทั้ง รหัสบิล(ref_code), ชื่อสินค้า, รหัสสินค้า, User, เหตุผล
    $sql .= " AND (t.ref_code LIKE '$like' OR p.name LIKE '$like' OR p.product_code LIKE '$like' OR t.username LIKE '$like' OR t.reason LIKE '$like')";
}

if ($type_filter !== "all") {
    // แปลงค่า filter ให้ตรงกับฐานข้อมูล (add/reduce)
    $sql .= " AND t.type = '$type_filter'";
}

if ($date_start !== "" && $date_end !== "") {
    $sql .= " AND DATE(t.created_at) BETWEEN '$date_start' AND '$date_end'";
}

$sql .= " ORDER BY t.created_at DESC LIMIT 500"; // ดึงมาแสดงผลล่าสุด 500 รายการ
$result = $conn->query($sql);

// ---------------------------------------------------------
// PHP DATA GROUPING (หัวใจสำคัญของการรวมบิล)
// ---------------------------------------------------------
$history_groups = [];
while ($row = $result->fetch_assoc()) {
    // สร้าง Key สำหรับจัดกลุ่ม
    // ถ้ามี ref_code ให้ใช้ ref_code เป็นตัวเกาะกลุ่ม
    // ถ้าไม่มี (ข้อมูลเก่า) ให้ใช้ ID เป็นตัวเกาะกลุ่ม (แยกบรรทัดใครบรรทัดมัน)
    $group_key = !empty($row['ref_code']) ? $row['ref_code'] : 'NOREF_' . $row['id'];

    // ถ้ายังไม่มีกลุ่มนี้ ให้สร้างหัวขบวน (Header)
    if (!isset($history_groups[$group_key])) {
        $history_groups[$group_key] = [
            'ref_code' => $row['ref_code'],
            'created_at' => $row['created_at'],
            'username' => $row['username'],
            'type' => $row['type'], // add หรือ reduce
            'reason' => $row['reason'], // เหตุผลหลัก
            'supplier' => $row['supplier'] ?? '',
            'items' => [] // เตรียมที่ว่างใส่รายการลูก
        ];
    }

    // ยัดรายการสินค้า (Detail) ใส่เข้าไปในกลุ่ม
    $history_groups[$group_key]['items'][] = [
        'product_name' => $row['product_name'],
        'product_code' => $row['product_code'],
        'image' => $row['image'],
        'amount' => $row['amount'],
        'reason_detail' => $row['reason'] // เหตุผลย่อย (เผื่อต่างกัน)
    ];
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานประวัติสต๊อก (รวมบิล) - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .content-container { padding: 30px; font-family: 'Prompt', sans-serif; }
        .page-title { font-size: 28px; font-weight: 700; margin-bottom: 20px; color:#2c3e50; }

        /* Search Box Styles (เหมือนเดิม) */
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
        }
        .btn-reset:hover { background: #dce1e2; color: #333; }

        /* --- New Table Styles for Master-Detail --- */
        table {
            width: 100%; border-collapse: separate; border-spacing: 0;
            background: #fff; border-radius: 14px; overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        th { background: #f8f9fa; padding: 15px; text-align: left; color: #2c3e50; font-weight: 600; border-bottom: 2px solid #eee; }
        
        /* Main Row Styles */
        .main-row td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; font-size: 14px; background: #fff; }
        .main-row:hover td { background-color: #f9fbfd; }

        /* Detail Row Styles (Hidden by default) */
        .detail-row { display: none; }
        .detail-cell { background-color: #f8f9fa; padding: 0 !important; border-bottom: 1px solid #eee; }
        
        /* Sub Table inside Detail Row */
        .sub-table { width: 95%; margin: 15px auto; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: none; }
        .sub-table th { background: #eceff1; color: #546e7a; font-size: 13px; padding: 10px; }
        .sub-table td { background: #fff; font-size: 13px; padding: 10px; border-bottom: 1px solid #f0f0f0; }
        .sub-table tr:last-child td { border-bottom: none; }

        /* Badge Styles */
        .user-badge {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 6px 12px; border-radius: 30px;
            font-size: 13px; font-weight: 600;
        }
        .badge-type { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .type-add { background: #e8f8f5; color: #27ae60; border: 1px solid #a9dfbf; }
        .type-reduce { background: #fdedec; color: #c0392b; border: 1px solid #f5b7b1; }
        .ref-badge { background: #e3f2fd; color: #1565c0; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; font-weight: bold; }

        /* Toggle Button */
        .btn-toggle {
            background: none; border: none; cursor: pointer; color: #356CB5;
            width: 30px; height: 30px; border-radius: 50%; transition: 0.2s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-toggle:hover { background: #e3f2fd; }
        
        .p-img-sm { width: 30px; height: 30px; border-radius: 4px; object-fit: cover; vertical-align: middle; margin-right: 8px; border: 1px solid #ddd; }
    </style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "topbar.php"; ?>

    <div class="content-container">

        <div class="page-title">ประวัติการปรับสต๊อก (รวมบิล)</div>

        <form class="search-box" method="get">
            <input type="text" name="search" class="search-input-main" 
                   placeholder="ค้นหาเลขที่บิล, ชื่อสินค้า, User..." 
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
                        <th width="5%"></th> <th width="15%">วัน/เวลา</th>
                        <th width="20%">เลขที่เอกสาร / อ้างอิง</th>
                        <th width="15%">ผู้ทำรายการ</th>
                        <th width="10%">ประเภท</th>
                        <th width="10%">รายการ</th>
                        <th width="25%">หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($history_groups)): ?>
                    <?php 
                    $row_index = 0;
                    foreach($history_groups as $key => $group): 
                        $row_index++;
                        $timestamp = strtotime($group['created_at']);
                        $date = date("d/m/Y", $timestamp);
                        $time = date("H:i", $timestamp);
                        $item_count = count($group['items']);
                        
                        // Theme สี User
                        $u_name = $group['username'] ?? 'System';
                        $color_palettes = [['#e3f2fd','#1565c0'], ['#e8f5e9','#2e7d32'], ['#f3e5f5','#7b1fa2'], ['#fff3e0','#ef6c00'], ['#fce4ec','#c2185b']];
                        $theme = (strtolower($u_name)=='admin') ? ['#ffebee','#c62828'] : $color_palettes[crc32($u_name) % count($color_palettes)];
                    ?>
                    
                    <tr class="main-row">
                        <td style="text-align:center;">
                            <?php if ($item_count > 0): ?>
                                <button type="button" class="btn-toggle" onclick="toggleRow('detail_<?= $row_index ?>', this)">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight:600; color:#333;"><?= $date ?></div>
                            <div style="font-size:12px; color:#999;"><?= $time ?> น.</div>
                        </td>
                        <td>
                            <?php if(!empty($group['ref_code'])): ?>
                                <span class="ref-badge"><i class="fa-solid fa-receipt"></i> <?= $group['ref_code'] ?></span>
                            <?php else: ?>
                                <span style="color:#aaa; font-style:italic;">- ไม่มีรหัส -</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="user-badge" style="background: <?= $theme[0] ?>; color: <?= $theme[1] ?>;">
                                <i class="fa-solid fa-user"></i> <?= htmlspecialchars($u_name) ?>
                            </div>
                        </td>
                        <td>
                            <?php if($group['type'] == 'add'): ?>
                                <span class="badge-type type-add"><i class="fa-solid fa-arrow-up"></i> เข้า</span>
                            <?php else: ?>
                                <span class="badge-type type-reduce"><i class="fa-solid fa-arrow-down"></i> ออก</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight:bold; color:#555;"><?= $item_count ?> รายการ</span>
                        </td>
                        <td>
                            <div style="font-size:14px;"><?= $group['reason'] ?></div>
                            <?php if(!empty($group['supplier'])): ?>
                                <div style="font-size:12px; color:#e67e22; margin-top:2px;">
                                    <i class="fa-solid fa-truck"></i> <?= $group['supplier'] ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr id="detail_<?= $row_index ?>" class="detail-row">
                        <td colspan="7" class="detail-cell">
                            <table class="sub-table">
                                <thead>
                                    <tr>
                                        <th width="15%">รหัสสินค้า</th>
                                        <th width="40%">ชื่อสินค้า</th>
                                        <th width="15%" style="text-align:center;">จำนวน</th>
                                        <th width="30%">รายละเอียดเพิ่มเติม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($group['items'] as $item): ?>
                                    <tr>
                                        <td><?= $item['product_code'] ?></td>
                                        <td>
                                            <?php if($item['image']): ?>
                                                <img src="uploads/<?= $item['image'] ?>" class="p-img-sm">
                                            <?php else: ?>
                                                <i class="fa-solid fa-box" style="margin-right:8px; color:#ccc;"></i>
                                            <?php endif; ?>
                                            <?= $item['product_name'] ?>
                                        </td>
                                        <td style="text-align:center; font-weight:bold;">
                                            <?php if($group['type'] == 'add'): ?>
                                                <span style="color:#27ae60;">+<?= number_format($item['amount']) ?></span>
                                            <?php else: ?>
                                                <span style="color:#c0392b;">-<?= number_format($item['amount']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color:#666; font-size:12px;"><?= $item['reason_detail'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:50px; color:#999;">
                            <i class="fa-regular fa-folder-open" style="font-size:40px; margin-bottom:15px; display:block;"></i>
                            ไม่พบประวัติการทำรายการ
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
    function toggleRow(rowId, btn) {
        var row = document.getElementById(rowId);
        var icon = btn.querySelector('i');
        
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row'; // แสดง
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
            btn.style.backgroundColor = '#e3f2fd'; // ไฮไลท์ปุ่ม
        } else {
            row.style.display = 'none'; // ซ่อน
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
            btn.style.backgroundColor = 'transparent'; // คืนค่าปุ่ม
        }
    }
</script>

</body>
</html>