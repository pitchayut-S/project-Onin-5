<?php
// ตรวจสอบว่ามีข้อมูลใน Session หรือไม่ (กัน Error)
$tb_fullname = $_SESSION['fullname'] ?? 'Guest User';
$tb_role = $_SESSION['role'] ?? 'Staff';

// --- Logic สร้าง Avatar ตัวอักษร ---
$tb_first_char = mb_substr($tb_fullname, 0, 1, "UTF-8"); 

// ชุดสี (ใช้ชุดเดียวกับหน้า Profile)
$tb_colors = ['#1565c0', '#2e7d32', '#c62828', '#f9a825', '#6a1b9a', '#00838f', '#ad1457'];
$tb_bg_color = $tb_colors[crc32($tb_fullname) % count($tb_colors)];

// ถ้าเป็น Admin ให้สีเข้ม
if (isset($_SESSION['role']) && strtolower($_SESSION['role']) == 'admin') {
    $tb_bg_color = '#2c3e50'; 
}
?>

<div class="top-navbar">
    <div class="nav-left">
        <!-- <i class="fa-solid fa-bars" id="sidebarToggle" style="cursor:pointer; font-size:20px; color:#555;"></i> -->
    </div>
    
    <div class="nav-right" style="display: flex; align-items: center; gap: 12px;">
        <div style="text-align: right; line-height: 1.3;">
            <div style="font-size: 14px; font-weight: 600; color: #333;">
                <?= htmlspecialchars($tb_fullname) ?>
            </div>
            <div style="font-size: 11px; color: #888; text-transform: uppercase; font-weight: 500; letter-spacing: 0.5px;">
                <?= htmlspecialchars($tb_role) ?>
            </div>
        </div>

        <div style="
            width: 40px; height: 40px; 
            background-color: <?= $tb_bg_color ?>; 
            color: white; border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            font-weight: bold; font-size: 18px; 
            border: 2px solid white; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        ">
            <?= $tb_first_char ?>
        </div>
    </div>
</div>

<style>
    /* CSS เฉพาะสำหรับ Topbar เพื่อความสวยงาม */
    /* .top-navbar {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 25px; padding: 10px 0;
    } */
    
    /* ซ่อนชื่อเมื่อจอเล็กมากๆ (มือถือแนวตั้ง) */
    @media (max-width: 480px) {
        .nav-right div:first-child { display: none; }
    }
</style>