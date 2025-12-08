<?php
session_start();
if (!isset($_SESSION['userid'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h1>ยินดีต้อนรับ, <?php echo $_SESSION['fullname']; ?></h1>
<p>นี่คือหน้า Dashboard ของระบบ</p>

<a href="logout.php">ออกจากระบบ</a>

</body>
</html>
