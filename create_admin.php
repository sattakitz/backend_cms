<?php
// Run once to create admin user
require 'db.php';
if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $username = readline('Admin username: ');
    $password = readline('Admin password: ');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO users (username,password,role) VALUES (?,?,"admin")');
    $stmt->execute([$username, $hash]);
    echo "Admin created\n";
} else {
    echo "Run from CLI or visit create_admin.php?run=1";
}
