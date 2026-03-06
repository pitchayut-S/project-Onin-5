<?php
ob_start();
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
    SELECT t.*, p.product_code, p.name as product_name, p.image, p.unit, c.category_name
    FROM stock_transactions t
    LEFT JOIN products p ON t.product_id = p.id
    LEFT JOIN product_category c ON p.category = c.id
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
    <title>รายงานประวัติสต็อก - Onin Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel='icon' type='image/png' href='favicon.png'>

    <style>
        .content-container {
            padding: 30px;
            background-color: #f3f4f6;
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        /* Search Box */
        .search-box {
            background: #fff;
            padding: 18px 20px;
            border-radius: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .search-box input,
        .search-box select {
            border: none;
            background: #eef2f6;
            padding: 12px 14px;
            border-radius: 10px;
            font-family: 'Prompt';
            font-size: 14px;
            outline: none;
        }

        .search-input-main {
            flex: 1;
            min-width: 200px;
        }

        .search-select {
            width: 150px;
        }

        .search-date {
            width: 140px;
        }

        .date-label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 600;
            margin: 0 5px;
        }

        .btn-search {
            background: #356CB5;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            height: 42px;
            transition: 0.2s;
        }

        .btn-search:hover {
            background: #285291;
        }

        .btn-reset {
            background: #ecf0f1;
            color: #7f8c8d;
            padding: 10px 15px;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            height: 42px;
            box-sizing: border-box;
            transition: 0.2s;
        }

        .btn-reset:hover {
            background: #dce1e2;
            color: #333;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #eee;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.1s;
        }

        tr:hover td {
            background-color: #f1f7ff;
        }

        /* Highlight Row when hover */

        /* User Badge */
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .user-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.1);
        }

        .badge-type {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .type-add {
            background: #e8f8f5;
            color: #27ae60;
            border: 1px solid #a9dfbf;
        }

        .type-reduce {
            background: #fdedec;
            color: #c0392b;
            border: 1px solid #f5b7b1;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .p-img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .p-name {
            font-weight: 600;
            color: #333;
        }

        .p-code {
            font-size: 12px;
            color: #999;
        }

        .date-text {
            color: #555;
            font-weight: 500;
        }

        .time-text {
            color: #999;
            font-size: 12px;
        }

        /* --- Detail Modal Styles --- */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            width: 90%;
            max-width: 600px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 25px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
        }

        .close:hover {
            color: #333;
        }

        .modal-body {
            padding: 25px;
        }

        /* Detail Grid in Modal */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-size: 13px;
            color: #888;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 15px;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .stock-change-box {
            background: #fcfcfc;
            border: 1px dashed #ddd;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }

        .stock-step {
            text-align: center;
        }

        .stock-step h4 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }

        .stock-step span {
            font-size: 12px;
            color: #888;
        }

        .stock-arrow {
            color: #ccc;
            font-size: 18px;
        }

        .big-img {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid #eee;
        }
    </style>
</head>

<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content">
        <?php include "topbar.php"; ?>

        <div class="content-container">

            <div class="page-title">ประวัติการปรับสต็อก</div>

            <form class="search-box" method="get">
                <input type="text" name="search" class="search-input-main"
                    placeholder="ค้นหาชื่อสินค้า, รหัส, User หรือ เหตุผล..."
                    value="<?= htmlspecialchars($search) ?>">

                <select name="type" class="search-select">
                    <option value="all" <?= $type_filter == 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
                    <option value="add" <?= $type_filter == 'add' ? 'selected' : '' ?>>+ เพิ่ม (เข้า)</option>
                    <option value="reduce" <?= $type_filter == 'reduce' ? 'selected' : '' ?>>- ลด (ออก)</option>
                </select>

                <span class="date-label">จาก</span>
                <input type="date" name="start" class="search-date" value="<?= $date_start ?>">
                <span class="date-label">ถึง</span>
                <input type="date" name="end" class="search-date" value="<?= $date_end ?>">

                <button type="submit" class="btn-search">ค้นหา</button>
                <?php if ($search != "" || $type_filter != "all" || $date_start != ""): ?>
                    <a href="ReportStock.php" class="btn-reset">ล้าง</a>
                <?php endif; ?>
            </form>

            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <th width="15%">วัน/เวลา</th>
                            <th width="15%">ผู้ทำรายการ</th>
                            <th width="35%">สินค้า</th>
                            <th width="10%">สถานะ</th>
                            <th width="10%">จำนวน</th>
                            <th width="5%">หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                $isAdd = ($row['type'] == 'add');
                                $timestamp = strtotime($row['created_at']);
                                $date = date("d/m/Y", $timestamp);
                                $time = date("H:i", $timestamp);

                                // User Avatar Logic
                                $u_name = $row['username'] ?? 'System';
                                $color_palettes = [['#e3f2fd', '#1565c0'], ['#e8f5e9', '#2e7d32'], ['#f3e5f5', '#7b1fa2'], ['#fff3e0', '#ef6c00'], ['#e0f7fa', '#00838f'], ['#fce4ec', '#c2185b']];
                                $theme = (strtolower($u_name) == 'admin') ? ['#ffebee', '#c62828'] : $color_palettes[crc32($u_name) % count($color_palettes)];

                                // Prepare JSON data for Modal
                                $jsonData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr onclick="showDetail(<?= $jsonData ?>)">
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
                                            <?php if ($row['image']): ?>
                                                <img src="uploads/<?= $row['image'] ?>" class="p-img">
                                            <?php else: ?>
                                                <div class="p-img" style="background:#eee; display:flex; align-items:center; justify-content:center; color:#ccc;"><i class="fa-solid fa-box"></i></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="p-name"><?= $row['product_name'] ?? '<span style="color:red;">(สินค้าถูกลบ)</span>' ?></div>
                                                <div class="p-code"><?= $row['product_code'] ?? '-' ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($isAdd): ?>
                                            <span class="badge-type type-add"><i class="fa-solid fa-arrow-up"></i> เพิ่ม</span>
                                        <?php else: ?>
                                            <span class="badge-type type-reduce"><i class="fa-solid fa-arrow-down"></i> ลด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight:bold; font-size:16px; color: #333;">
                                        <?= number_format($row['amount']) ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <button style="background:none; border:none; color:#356CB5; cursor:pointer;">
                                            <i class="fa-solid fa-circle-info" style="font-size:20px;"></i>
                                        </button>
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

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-invoice"></i> รายละเอียดการทำรายการ</h3>
                <span class="close" onclick="closeDetail()">&times;</span>
            </div>
            <div class="modal-body">

                <div style="display:flex; gap:20px; align-items:center; margin-bottom:25px; padding-bottom:20px; border-bottom:1px solid #eee;">
                    <img id="d_image" src="" class="big-img" onerror="this.style.display='none'">
                    <div id="d_no_image" class="big-img" style="display:none; align-items:center; justify-content:center; background:#eee; color:#aaa; font-size:30px;">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <div>
                        <div class="detail-label">สินค้า</div>
                        <div id="d_name" style="font-size:18px; font-weight:700; color:#333;"></div>
                        <div style="font-size:14px; color:#666;">
                            รหัส: <span id="d_code"></span> | หมวด: <span id="d_cat"></span>
                        </div>
                    </div>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">ผู้ทำรายการ (User)</div>
                        <div class="detail-value"><i class="fa-solid fa-user-circle"></i> <span id="d_user"></span></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">วันเวลาที่ทำรายการ</div>
                        <div class="detail-value"><i class="fa-regular fa-clock"></i> <span id="d_time"></span></div>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="detail-label">เหตุผล / สาเหตุ</div>
                        <div class="detail-value" style="background:#f9f9f9; padding:10px; border-radius:8px;">
                            <span id="d_reason"></span>
                        </div>
                    </div>
                    <div class="detail-item" id="d_supplier_box" style="grid-column: span 2; display:none;">
                        <div class="detail-label">รับจาก / แหล่งที่มา</div>
                        <div class="detail-value"><i class="fa-solid fa-truck-field"></i> <span id="d_supplier"></span></div>
                    </div>
                </div>

                <div class="detail-label">สรุปยอดคงเหลือ</div>
                <div class="stock-change-box">
                    <div class="stock-step">
                        <span id="d_prev_label">ก่อนปรับ</span>
                        <h4 id="d_prev_val">0</h4>
                    </div>
                    <div class="stock-arrow">
                        <i id="d_arrow_icon" class="fa-solid fa-arrow-right"></i>
                    </div>
                    <div class="stock-step">
                        <span id="d_change_label">ยอดปรับ</span>
                        <h4 id="d_change_val" style="font-weight:800;">0</h4>
                    </div>
                    <div class="stock-arrow">
                        <i class="fa-solid fa-equals"></i>
                    </div>
                    <div class="stock-step">
                        <span>คงเหลือ (Balance)</span>
                        <h4 id="d_balance_val" style="color:#356CB5;">0</h4>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function showDetail(data) {
            // 1. Set Product Info
            if (data.image) {
                document.getElementById('d_image').src = 'uploads/' + data.image;
                document.getElementById('d_image').style.display = 'block';
                document.getElementById('d_no_image').style.display = 'none';
            } else {
                document.getElementById('d_image').style.display = 'none';
                document.getElementById('d_no_image').style.display = 'flex';
            }

            document.getElementById('d_name').innerText = data.product_name ? data.product_name : '(สินค้าถูกลบ)';
            document.getElementById('d_code').innerText = data.product_code ? data.product_code : '-';
            document.getElementById('d_cat').innerText = data.category_name ? data.category_name : '-';

            // 2. Set User & Time
            document.getElementById('d_user').innerText = data.username ? data.username : 'System';

            // Format Date
            const dateObj = new Date(data.created_at);
            const dateStr = dateObj.toLocaleDateString('th-TH', {
                year: 'numeric',
                month: 'long',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
            document.getElementById('d_time').innerText = dateStr;

            // 3. Reason & Supplier
            document.getElementById('d_reason').innerText = data.reason;
            if (data.supplier) {
                document.getElementById('d_supplier').innerText = data.supplier;
                document.getElementById('d_supplier_box').style.display = 'block';
            } else {
                document.getElementById('d_supplier_box').style.display = 'none';
            }

            // 4. Calculate Stock Movement
            const amount = parseInt(data.amount);
            const balance = parseInt(data.balance); // ยอดคงเหลือหลังทำรายการ
            let prevBalance = 0;

            const changeLabel = document.getElementById('d_change_label');
            const changeVal = document.getElementById('d_change_val');
            const arrowIcon = document.getElementById('d_arrow_icon');

            if (data.type === 'add') {
                // สูตร: คงเหลือ - ยอดเพิ่ม = ยอดก่อนหน้า
                prevBalance = balance - amount;
                changeLabel.innerText = "รับเข้า (+)";
                changeVal.innerText = "+" + amount;
                changeVal.style.color = "#27ae60";
                arrowIcon.className = "fa-solid fa-arrow-right";
            } else {
                // สูตร: คงเหลือ + ยอดลด = ยอดก่อนหน้า
                prevBalance = balance + amount;
                changeLabel.innerText = "จ่ายออก (-)";
                changeVal.innerText = "-" + amount;
                changeVal.style.color = "#c0392b";
                arrowIcon.className = "fa-solid fa-arrow-right";
            }

            document.getElementById('d_prev_val').innerText = prevBalance;
            document.getElementById('d_balance_val').innerText = balance;

            // Show Modal
            document.getElementById('detailModal').style.display = 'block';
        }

        function closeDetail() {
            document.getElementById('detailModal').style.display = 'none';
        }

        // Close when click outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('detailModal')) {
                closeDetail();
            }
        }
    </script>

</body>

</html>