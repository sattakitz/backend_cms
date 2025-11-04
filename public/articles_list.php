<?php
require_once __DIR__ . '/../helpers.php';
require_login();
$stmt = $pdo->query('SELECT a.*, u.username FROM articles a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC');
$articles = $stmt->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Articles</title>
</head>

<body>
    <h1>Articles</h1>
    <p><a href="article_form.php">+ Add New</a> | <a href="dashboard.php">Back</a></p>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Title</th>
            <th>Category</th>
            <th>Tags</th>
            <th>Image</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($articles as $a): ?>
            <tr>
                <td><?php echo e($a['id']); ?></td>
                <td><?php echo e($a['title']); ?></td>
                <td><?php echo e($a['category']); ?></td>
                <td><?php echo e($a['tags']); ?></td>
                <td><?php if ($a['image']): ?><img src="<?php echo e($config['BASE_URL'] . 'uploads/' . basename($a['image'])); ?>" width="80"><?php endif; ?></td>
                <td>
                    <a href="article_form.php?id=<?php echo $a['id']; ?>">Edit</a> |
                    <a href="delete_article.php?id=<?php echo $a['id']; ?>" onclick="return confirm('Delete?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>