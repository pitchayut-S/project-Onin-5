<?php
// รายการเมนูสำหรับ Sidebar (ใช้วนลูปแสดงทุกหน้า)
$menu_config = [
    [
        'name' => 'Dashboard',
        'icon' => 'fa-solid fa-chart-line',
        'link' => 'dashboard.php',
    ],
    [
        'name' => 'ข้อมูลสินค้า',
        'icon' => 'fa-solid fa-box-open',
        'link' => 'product_list.php',
    ],
    [
        'name' => 'ข้อมูลประเภทสินค้า',
        'icon' => 'fa-solid fa-clipboard-check',
        'link' => 'category_list.php',
    ],
    [
        'name' => 'สต๊อกสินค้า',
        'icon' => 'fa-solid fa-cart-shopping',
        'link' => 'product_Stock.php',
    ],
    [
        'name' => 'ขายหน้าร้าน',
        'icon' => 'fa-solid fa-cash-register',
        'link' => 'pos.php',
    ],
    [
        'name' => 'สินค้ายอดนิยม',
        'icon' => 'fa-solid fa-heart',
        'link' => 'ProductPoppular.php',
    ],
    [
        'name' => 'ประวัติปรับสต๊อก',
        'icon' => 'fa-solid fa-clock-rotate-left',
        'link' => 'ReportStock.php',
    ],
   
];