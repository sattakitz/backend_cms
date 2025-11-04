<?php


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $sitesSelected = $_POST['sites'] ?? [];


    // handle image upload
    $imagePath = $article['image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        $uploaddir = __DIR__ . '/../uploads/';
        if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
        $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES['image']['name']));
        $target = $uploaddir . $fname;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = $fname;
        }
    }


    if ($editing) {
        $stmt = $pdo->prepare('UPDATE articles SET title=?, content=?, image=?, category=?, tags=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$title, $content, $imagePath, $category, $tags, $id]);
        // update sites
        $pdo->prepare('DELETE FROM article_site WHERE article_id=?')->execute([$id]);
        foreach ($sitesSelected as $sid) {
            $pdo->prepare('INSERT INTO article_site (article_id, site_id) VALUES (?,?)')->execute([$id, $sid]);
        }
        header('Location: articles_list.php');
        exit;
    } else {
        $stmt = $pdo->prepare('INSERT INTO articles (title,content,image,category,tags,created_by) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$title, $content, $imagePath, $category, $tags, current_user()['id']]);
        $articleId = $pdo->lastInsertId();
        foreach ($sitesSelected as $sid) {
            $pdo->prepare('INSERT INTO article_site (article_id, site_id) VALUES (?,?)')->execute([$articleId, $sid]);
        }
        header('Location: articles_list.php');
        exit;
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title><?php echo $editing ? 'Edit' : 'Add'; ?> Article</title>
</head>

<body>
    <h1><?php echo $editing ? 'Edit' : 'Add'; ?> Article</h1>
    <form method="post" enctype="multipart/form-data">
        <label>Title: <input name="title" value="<?php echo e($article['title']); ?>" required></label><br>
        <label>Category: <input name="category" value="<?php echo e($article['category']); ?>"></label><br>
        <label>Tags (comma): <input name="tags" value="<?php echo e($article['tags']); ?>"></label><br>
        <label>Image: <input type="file" name="image"></label>
        <?php if (!empty($article['image'])): ?>
            <div>Current: <img src="<?php echo e($config['BASE_URL'] . 'uploads/' . basename($article['image'])); ?>" width="120"></div>
        <?php endif; ?>
        <br>
        <label>Content:<br>
            <textarea name="content" rows="12" cols="80"><?php echo e($article['content']); ?></textarea></label><br>

        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <select name="category"> ... </select>
        <select name="tags[]" multiple> ... </select>
        <textarea id="content" name="content">...</textarea>
        <script>
            if (typeof tinymce !== 'undefined') {
                tinymce.init({
                    selector: '#content',
                    height: 400
                });
            }
        </script>


        <fieldset>
            <legend>Publish to sites</legend>
            <?php foreach ($sites as $s): ?>
                <label><input type="checkbox" name="sites[]" value="<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $selectedSites) ? 'checked' : ''; ?>> <?php echo e($s['name']); ?></label><br>
            <?php endforeach; ?>
        </fieldset>


        <button><?php echo $editing ? 'Update' : 'Create'; ?></button>
    </form>
    <p><a href="articles_list.php">Back to list</a></p>
</body>

</html>