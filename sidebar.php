<?php
if (!isset($menu_config)) {
    require_once __DIR__ . '/menu-sidebar.php';
}

$current_page = basename(path: $_SERVER['PHP_SELF']);
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
            <a href="#">
                <i class="fa-solid fa-user-gear"></i>
                <span class="menu-text">ตั้งค่าผู้ใช้</span>
            </a>
        </li>
        <li>
            <a href="index.php" class="btn-logout" onclick="confirmLogout(); return false;">
                <i class="fa-solid fa-power-off"></i>
                <span class="menu-text">ออกจากระบบ</span>
            </a>
        </li>
    </ul>
</nav>
