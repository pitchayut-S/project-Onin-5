<?php
if (!isset($menu_config)) {
    require_once __DIR__ . '/menu-sidebar.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
require_once __DIR__ . '/components/alert.php';
?>

<script>
if (localStorage.getItem('sidebarCollapsed') === 'true') {
    document.body.classList.add('sidebar-collapsed');
}
</script>

<nav class="sidebar">
    <div class="sidebar-header">
        <span class="sidebar-title">Onin Shop Stock</span>
        <i class="fa-solid fa-angles-left" id="sidebarToggleBtn" title="ย่อ/ขยาย Sidebar"></i>
    </div>

    <ul class="menu-list">
        <?php foreach ($menu_config as $item): ?>
            <?php $is_active = $current_page === basename($item['link']); ?>
            <li>
                <a href="<?= htmlspecialchars($item['link']) ?>" class="<?= $is_active ? 'active' : '' ?>" data-tooltip="<?= htmlspecialchars($item['name']) ?>">
                    <i class="<?= htmlspecialchars($item['icon']) ?>"></i>
                    <span class="menu-text"><?= htmlspecialchars($item['name']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <ul class="sidebar-footer menu-list">
        
        <li>
            <?php 

                $is_users_active = ($current_page == 'Profile.php'); 
            ?>
            <a href="Profile.php" class="<?= $is_users_active ? 'active' : '' ?>" data-tooltip="จัดการผู้ใช้งาน">
                <i class="fa-solid fa-users-gear"></i>
                <span class="menu-text">จัดการผู้ใช้งาน</span>
            </a>
        </li>
        <li>
            <a href="logout.php" class="btn-logout" onclick="confirmLogout(); return false;" data-tooltip="ออกจากระบบ">
                <i class="fa-solid fa-power-off"></i>
                <span class="menu-text">ออกจากระบบ</span>
            </a>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const topbarToggle = document.getElementById('sidebarToggle');
    const sidebarToggle = document.getElementById('sidebarToggleBtn');

    function toggleSidebar() {
        document.body.classList.toggle('sidebar-collapsed');
        const isCollapsed = document.body.classList.contains('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    if (topbarToggle) topbarToggle.addEventListener('click', toggleSidebar);
    if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
});
</script>