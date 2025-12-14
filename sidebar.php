<?php
if (!isset($menu_config)) {
    require_once __DIR__ . '/menu-sidebar.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/components/alert.php';
?>

<nav class="sidebar">
    <div class="sidebar-header">Onin Shop Stock</div>

    <ul class="menu-list">
        <?php foreach ($menu_config as $item): ?>
            <?php $is_active = $current_page === basename($item['link']); ?>
            <li>
                <a href="<?= htmlspecialchars($item['link']) ?>" class="<?= $is_active ? 'active' : '' ?>">
                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                    <span class="menu-text"><?= htmlspecialchars($item['name']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <ul class="sidebar-footer menu-list">
        
        <li>
            <?php 
                // เช็คว่าอยู่หน้า Profile.php หรือไม่ (แก้จาก users.php)
                $is_users_active = ($current_page == 'Profile.php'); 
            ?>
            <a href="Profile.php" class="<?= $is_users_active ? 'active' : '' ?>">
                <i class="fa-solid fa-users-gear"></i>
                <span class="menu-text">จัดการผู้ใช้งาน</span>
            </a>
        </li>
        <li>
            <a href="logout.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i>
                <span class="menu-text">ออกจากระบบ</span>
            </a>
        </li>
    </ul>
</nav>