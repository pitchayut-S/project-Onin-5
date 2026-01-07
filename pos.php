<?php
session_start();
require_once "db.php";

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// ดึงข้อมูลสินค้าที่มีของอยู่ (quantity > 0)
$sql = "SELECT * FROM products WHERE quantity > 0 ORDER BY category, name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ขายหน้าร้าน (POS)</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css"> <style>
        body { overflow-y: hidden; /* ป้องกัน Scrollbar ซ้อน */ }
        .pos-container { display: flex; height: calc(100vh - 60px); gap: 20px; padding: 20px; }
        
        /* ฝั่งซ้าย: รายการสินค้า */
        .product-list-section { flex: 7; overflow-y: auto; padding-right: 10px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .product-card {
            background: #fff; border-radius: 12px; padding: 15px; text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); cursor: pointer; transition: 0.2s;
            border: 2px solid transparent;
        }
        .product-card:hover { transform: translateY(-5px); border-color: #356CB5; }
        .product-card img { width: 100px; height: 100px; object-fit: contain; margin-bottom: 10px; }
        .product-price { color: #356CB5; font-weight: bold; font-size: 1.2rem; }
        .product-stock { font-size: 0.8rem; color: #888; }

        /* ฝั่งขวา: ตะกร้าสินค้า */
        .cart-section { 
            flex: 3; background: #fff; border-radius: 16px; 
            box-shadow: -5px 0 20px rgba(0,0,0,0.05); 
            display: flex; flex-direction: column; 
            padding: 20px;
        }
        .cart-items { flex: 1; overflow-y: auto; margin-bottom: 20px; }
        .cart-item { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 10px 0; border-bottom: 1px solid #eee; 
        }
        .cart-total { font-size: 1.5rem; font-weight: bold; text-align: right; color: #333; }
        .btn-checkout { 
            background: #28a745; color: white; width: 100%; padding: 15px; 
            font-size: 1.2rem; border-radius: 10px; border: none; font-weight: bold; cursor: pointer; 
        }
        .btn-checkout:hover { background: #218838; }
        .qty-control { display: flex; align-items: center; gap: 10px; }
        .btn-qty { 
            width: 25px; height: 25px; border-radius: 50%; border: 1px solid #ddd; 
            background: #f8f9fa; cursor: pointer; display: flex; align-items: center; justify-content: center;
        }
    </style>
</head>
<body>

    <?php include "sidebar.php"; ?>

    <div class="main-content" style="margin-left: 250px; padding: 0;">
        <div class="pos-container">
            
            <div class="product-list-section">
                <h2 style="margin-bottom: 20px;">รายการสินค้า</h2>
                <input type="text" id="searchBox" class="search-input" placeholder="ค้นหาสินค้า..." style="width: 100%; margin-bottom: 20px; padding: 12px;">
                
                <div class="product-grid" id="productGrid">
                    <?php while($row = $result->fetch_assoc()): ?>
                        <div class="product-card" 
                             onclick="addToCart(<?= $row['id'] ?>, '<?= addslashes($row['name']) ?>', <?= $row['selling_price'] ?>, <?= $row['quantity'] ?>)">
                            
                            <?php if($row['image']): ?>
                                <img src="uploads/<?= $row['image'] ?>">
                            <?php else: ?>
                                <div style="width:100px; height:100px; background:#eee; margin:0 auto; border-radius:8px;"></div>
                            <?php endif; ?>

                            <div style="font-weight:600; height: 40px; overflow: hidden;"><?= $row['name'] ?></div>
                            <div class="product-price"><?= number_format($row['selling_price'], 2) ?> ฿</div>
                            <div class="product-stock">คงเหลือ: <?= $row['quantity'] ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="cart-section">
                <h3><i class="fa-solid fa-cart-shopping"></i> รายการสั่งซื้อ</h3>
                <div class="cart-items" id="cartItems">
                    <div style="text-align:center; color:#999; margin-top:50px;">ยังไม่มีสินค้าในตะกร้า</div>
                </div>

                <div style="border-top: 2px dashed #ddd; padding-top: 15px;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span>รวมจำนวน:</span>
                        <span id="totalQty">0</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; font-size:1.5rem; font-weight:bold;">
                        <span>ยอดรวม:</span>
                        <span style="color:#356CB5;" id="totalPrice">0.00</span> ฿
                    </div>
                </div>
                
                <button class="btn-checkout" onclick="checkout()">
                    <i class="fa-solid fa-money-bill-wave"></i> ยืนยันการขาย
                </button>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // เก็บข้อมูลตะกร้าสินค้า
        let cart = [];

        // 1. ฟังก์ชันเพิ่มสินค้าลงตะกร้า
        function addToCart(id, name, price, maxStock) {
            // เช็คว่ามีสินค้านี้ในตะกร้าหรือยัง
            let existingItem = cart.find(item => item.id === id);

            if (existingItem) {
                if (existingItem.qty < maxStock) {
                    existingItem.qty++;
                } else {
                    Swal.fire('แจ้งเตือน', 'สินค้าในสต็อกมีไม่พอ', 'warning');
                }
            } else {
                cart.push({ id: id, name: name, price: price, qty: 1, max: maxStock });
            }
            renderCart();
        }

        // 2. ฟังก์ชันแสดงผลตะกร้า
        function renderCart() {
            let html = '';
            let total = 0;
            let qtyTotal = 0;

            if (cart.length === 0) {
                document.getElementById('cartItems').innerHTML = '<div style="text-align:center; color:#999; margin-top:50px;">เลือกสินค้าจากฝั่งซ้าย</div>';
                document.getElementById('totalPrice').innerText = '0.00';
                document.getElementById('totalQty').innerText = '0';
                return;
            }

            cart.forEach((item, index) => {
                let sum = item.price * item.qty;
                total += sum;
                qtyTotal += item.qty;

                html += `
                <div class="cart-item">
                    <div>
                        <div style="font-weight:600;">${item.name}</div>
                        <div style="font-size:0.85rem; color:#666;">${item.price.toFixed(2)} x ${item.qty} = ${sum.toFixed(2)}</div>
                    </div>
                    <div class="qty-control">
                        <div class="btn-qty" onclick="updateQty(${index}, -1)"><i class="fa-solid fa-minus"></i></div>
                        <span style="font-weight:bold; width:20px; text-align:center;">${item.qty}</span>
                        <div class="btn-qty" onclick="updateQty(${index}, 1)"><i class="fa-solid fa-plus"></i></div>
                        <div class="btn-qty" style="color:red; border-color:red; margin-left:5px;" onclick="removeItem(${index})"><i class="fa-solid fa-trash"></i></div>
                    </div>
                </div>`;
            });

            document.getElementById('cartItems').innerHTML = html;
            document.getElementById('totalPrice').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('totalQty').innerText = qtyTotal;
        }

        // 3. ปรับจำนวน
        function updateQty(index, change) {
            if (change === 1) {
                if (cart[index].qty < cart[index].max) {
                    cart[index].qty++;
                } else {
                    Swal.fire('แจ้งเตือน', 'สินค้าหมดสต็อกแล้ว', 'warning');
                }
            } else {
                if (cart[index].qty > 1) {
                    cart[index].qty--;
                }
            }
            renderCart();
        }

        // 4. ลบสินค้า
        function removeItem(index) {
            cart.splice(index, 1);
            renderCart();
        }

        // 5. ค้นหาสินค้า (Client Side Search)
        document.getElementById('searchBox').addEventListener('keyup', function() {
            let val = this.value.toLowerCase();
            let items = document.querySelectorAll('.product-card');
            items.forEach(item => {
                let text = item.innerText.toLowerCase();
                item.style.display = text.includes(val) ? 'block' : 'none';
            });
        });

        // 6. กดปุ่ม Checkout (ส่งข้อมูลไปหลังบ้าน)
        function checkout() {
            if (cart.length === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกสินค้าก่อน', 'warning');
                return;
            }

            Swal.fire({
                title: 'ยืนยันการขาย?',
                text: `ยอดรวมทั้งหมด ${document.getElementById('totalPrice').innerText} บาท`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'ยืนยัน',
                confirmButtonColor: '#28a745'
            }).then((result) => {
                if (result.isConfirmed) {
                    // ส่งข้อมูลผ่าน AJAX (Fetch API)
                    fetch('pos_save.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart: cart })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire('สำเร็จ', 'บันทึกการขายเรียบร้อย', 'success')
                            .then(() => location.reload()); // รีโหลดหน้าเพื่ออัปเดตสต็อกใหม่
                        } else {
                            Swal.fire('ผิดพลาด', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
                    });
                }
            });
        }
    </script>
</body>
</html>