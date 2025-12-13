<?php
session_start();
require_once "db.php";

/* ======================
   AUTH CHECK
====================== */
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

/* ======================
   ADD CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_category'])) {

    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        $stmt = $conn->prepare(
            "INSERT INTO product_category (category_name, prefix) VALUES (?, ?)"
        );
        $stmt->bind_param("ss", $category_name, $prefix);
        $stmt->execute();

        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'สำเร็จ',
            'text' => 'เพิ่มประเภทสินค้าเรียบร้อยแล้ว'
        ];
    }

    header("Location: category_list.php");
    exit();
}

/* ======================
   EDIT CATEGORY
====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_category'])) {

    $id = intval($_POST['id']);
    $category_name = trim($_POST['category_name']);
    $prefix = strtoupper(trim($_POST['prefix']));

    if ($category_name !== "" && $prefix !== "") {
        $stmt = $conn->prepare(
            "UPDATE product_category 
             SET category_name = ?, prefix = ?
             WHERE id = ?"
        );
        $stmt->bind_param("ssi", $category_name, $prefix, $id);
        $stmt->execute();

        $_SESSION['swal'] = [
            'icon' => 'success',
            'title' => 'สำเร็จ',
            'text' => 'แก้ไขข้อมูลเรียบร้อยแล้ว'
        ];
    }

    header("Location: category_list.php");
    exit();
}

/* ======================
   SEARCH
====================== */
$search_text = $_GET['search'] ?? "";
$sql = "SELECT * FROM product_category";
if ($search_text !== "") {
    $like = "%{$search_text}%";
    $sql .= " WHERE category_name LIKE '$like' OR prefix LIKE '$like'";
}
$sql .= " ORDER BY id DESC";
$categories = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>ประเภทสินค้า</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="style.css">

<style>
.content-container { padding:30px; font-family:"Prompt"; }
.page-title { font-size:28px; font-weight:700; margin-bottom:20px; }

.search-box {
    background:#fff;
    padding:18px 20px;
    border-radius:14px;
    display:flex;
    gap:10px;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.05);
    margin-bottom:20px;
}
.search-box input {
    flex:1;
    border:none;
    background:#eef2f6;
    padding:12px 14px;
    border-radius:10px;
}
.btn-search {
    background:#356CB5;
    color:#fff;
    padding:10px 18px;
    border-radius:10px;
    border:none;
    font-weight:600;
}
.btn-reset {
    background:#e7ebf0;
    padding:10px 16px;
    border-radius:10px;
    text-decoration:none;
    color:#333;
}

table {
    width:100%;
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 4px 15px rgba(0,0,0,0.04);
}
th, td {
    padding:14px 12px;
    border-bottom:1px solid #eee;
}
th {
    background:#f3f6fb;
    font-weight:600;
}

.btn-add {
    background:#28a745;
    color:#fff;
    padding:10px 18px;
    border-radius:10px;
    border:none;
}
.btn-edit {
    background:#f1c40f;
    color:#fff;
    padding:6px 12px;
    border-radius:8px;
    border:none;
}
.btn-delete {
    background:#e74c3c;
    color:#fff;
    padding:6px 12px;
    border-radius:8px;
    text-decoration:none;
}

button {
    border: none !important;
    outline: none !important;
}

button:focus,
button:active,
button:focus-visible {
    outline: none !important;
    box-shadow: none !important;
}

/* สำหรับ Chrome / Safari */
button {
    -webkit-appearance: none;
    appearance: none;
}


</style>
</head>

<body>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <div class="top-navbar">
        <div class="nav-left"><i class="fa-solid fa-bars"></i></div>
        <div class="nav-right"><img src="img/profile.png"></div>
    </div>

<div class="content-container">

<div class="page-title">ข้อมูลประเภทสินค้า</div>

<div style="text-align:right; margin-bottom:20px;">
<button class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
+ เพิ่มประเภทสินค้า
</button>
</div>

<form class="search-box" method="get">
<input type="text" name="search"
 placeholder="ค้นหาชื่อประเภทสินค้า / Prefix"
 value="<?= htmlspecialchars($search_text) ?>">
<button class="btn-search">ค้นหา</button>
<?php if ($search_text !== ""): ?>
<a href="category_list.php" class="btn-reset">ล้าง</a>
<?php endif; ?>
</form>

<table>
<thead>
<tr>
<th>#</th>
<th>Prefix</th>
<th>ชื่อประเภทสินค้า</th>
<th>จัดการ</th>
</tr>
</thead>
<tbody>

<?php $i=1; while($row = $categories->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $row['prefix'] ?></td>
<td><?= $row['category_name'] ?></td>
<td>
<button class="btn-edit"
 data-bs-toggle="modal"
 data-bs-target="#editModal"
 data-id="<?= $row['id'] ?>"
 data-prefix="<?= $row['prefix'] ?>"
 data-name="<?= $row['category_name'] ?>">
แก้ไข
</button>

<button class="btn-delete"
 onclick="confirmDelete(<?= $row['id'] ?>)">
ลบ
</button>

</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addModal">
<div class="modal-dialog modal-dialog-centered">
<form method="post" class="modal-content">
<input type="hidden" name="add_category">

<div class="modal-header">
<h5>เพิ่มประเภทสินค้า</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input class="form-control mb-2" name="prefix" placeholder="Prefix" required>
<input class="form-control" name="category_name" placeholder="ชื่อประเภทสินค้า" required>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
<button class="btn btn-success">บันทึก</button>
</div>
</form>
</div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal">
<div class="modal-dialog modal-dialog-centered">
<form method="post" class="modal-content">
<input type="hidden" name="edit_category">
<input type="hidden" name="id" id="edit-id">

<div class="modal-header">
<h5>แก้ไขประเภทสินค้า</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<input class="form-control mb-2" id="edit-prefix" name="prefix" required>
<input class="form-control" id="edit-name" name="category_name" required>
</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
<button class="btn btn-primary">บันทึก</button>
</div>
</form>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: 'ข้อมูลนี้จะไม่สามารถกู้คืนได้',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ลบข้อมูล',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'category_delete.php?id=' + id;
        }
    });
}
</script>


<script>
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value = b.dataset.id;
    document.getElementById('edit-prefix').value = b.dataset.prefix;
    document.getElementById('edit-name').value = b.dataset.name;
});
</script>

<?php if (isset($_SESSION['swal'])): ?>
<script>
Swal.fire({
    icon: '<?= $_SESSION['swal']['icon'] ?>',
    title: '<?= $_SESSION['swal']['title'] ?>',
    text: '<?= $_SESSION['swal']['text'] ?>',
    showConfirmButton: false,
    timer: 1200,
    timerProgressBar: true
});
</script>
<?php unset($_SESSION['swal']); endif; ?>


</body>
</html>
