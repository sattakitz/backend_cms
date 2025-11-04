<?php
// Public API: GET ?site_id=ID or GET ?article_id=ID
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json; charset=utf-8');
$siteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
if ($articleId) {
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id=?');
    $stmt->execute([$articleId]);
    $a = $stmt->fetch();
    if ($a) {
        // attach full image url
        if ($a['image']) $a['image'] = (require __DIR__ . '/../config.php')['BASE_URL'] . 'uploads/' . $a['image'];
        echo json_encode($a);
    } else echo json_encode([]);
    exit;
}
if (!$siteId) {
    echo json_encode([]);
    exit;
}
$sql = "SELECT a.* FROM articles a JOIN article_site s ON a.id = s.article_id WHERE s.site_id = ? ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$siteId]);
$rows = $stmt->fetchAll();
$config = require __DIR__ . '/../config.php';
foreach ($rows as &$r) {
    if ($r['image']) $r['image'] = $config['BASE_URL'] . 'uploads/' . $r['image'];
}
echo json_encode($rows);
