<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../db.php'; // เพิ่มการเรียกใช้ db.php เพื่อให้มี $pdo

if (is_logged_in()) header('Location: dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // ดึงข้อมูลผู้ใช้
    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    // *** สำคัญ: ใช้ password_verify เพื่อตรวจสอบรหัสผ่านที่ถูกแฮช ***
    if ($user && password_verify($password, $user['password'])) {
        // login สำเร็จ
        secure_session_regen(); // สร้าง Session ID ใหม่
        unset($user['password']);
        $_SESSION['user'] = $user;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <?php if ($error): ?><p style="color:red"><?php echo e($error); ?></p><?php endif; ?>
    <form method="post">
        <label>Username: <input name="username"></label><br>
        <label>Password: <input name="password" type="password"></label><br>
        <button>Login</button>
    </form>
</body>

</html>