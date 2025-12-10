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
        'link' => 'ProductStock.php',
    ],
    [
        'name' => 'สินค้ายอดนิยม',
        'icon' => 'fa-solid fa-heart',
        'link' => 'ProductPoppular.php',
    ],
    [
        'name' => 'รายงาน',
        'icon' => 'fa-solid fa-file-invoice',
        'link' => 'Report.php',
    ],
];
