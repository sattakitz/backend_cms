<?php
// Run once to create admin user
require __DIR__ . '/db.php';

// This script can be run from CLI or via the web (visit create_admin.php?run=1)
// When run from web, a simple form is shown and POST will create the admin.
if (php_sapi_name() === 'cli') {
    // CLI mode: use readline
    $username = readline('Admin username: ');
    $password = readline('Admin password: ');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username,password,role) VALUES (?,?,"admin")');
    $stmt->execute([$username, $hash]);
    echo "Admin created\n";
    exit;
}

// Web mode: when ?run=1 show a form and accept POST to create the admin
if (isset($_GET['run'])) {
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (username,password,role) VALUES (?,?,"admin")');
            $stmt->execute([$username, $hash]);
            echo 'Admin created. <a href="public/login.php">Go to login</a>';
            exit;
        }
    }
    // Simple HTML form (only for local/dev use)
    ?>
    <!doctype html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>Create Admin</title>
    </head>
    <body>
        <h2>Create Admin</h2>
        <?php if ($error): ?><p style="color:red"><?php echo htmlentities($error); ?></p><?php endif; ?>
        <form method="post">
            <label>Username: <input name="username" value="admin"></label><br>
            <label>Password: <input name="password" type="password" value="123"></label><br>
            <button>Create</button>
        </form>
        <p>Note: remove or protect this file after creating your initial admin user.</p>
    </body>
    </html>
    <?php
    exit;
}

echo "Run from CLI (php create_admin.php) or visit create_admin.php?run=1";
