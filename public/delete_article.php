<?php
require_once __DIR__ . '/../helpers.php';
require_login();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    // remove image file
    $stmt = $pdo->prepare('SELECT image FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $a = $stmt->fetch();
    if ($a && $a['image']) {
        @unlink(__DIR__ . '/../uploads/' . $a['image']);
    }
    $pdo->prepare('DELETE FROM articles WHERE id = ?')->execute([$id]);
}
header('Location: articles_list');
