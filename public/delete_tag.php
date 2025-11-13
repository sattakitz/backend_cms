<?php
require_once __DIR__ . '/../helpers.php';
require_login();

if (!check_csrf($_GET['csrf'] ?? '')) {
    die('CSRF validation failed.');
}

$id = (int)($_GET['id'] ?? 0);
$pdo->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);

header('Location: tags');