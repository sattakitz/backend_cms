<?php
require_once __DIR__ . '/../helpers.php';
require_login();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
</head>

<body>
    <h1>Dashboard</h1>
    <p>ยินดีต้อนรับ, <?php echo e(current_user()['username']); ?> | <a href="logout.php">Logout</a></p>
    <ul>
        <li><a href="articles_list.php">จัดการบทความ</a></li>
        <li><a href="article_form.php">เพิ่มบทความ</a></li>
        <li><a href="sites.php">จัดการเว็บไซต์ปลายทาง</a></li>
    </ul>
</body>

</html>