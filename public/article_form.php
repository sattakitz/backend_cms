<?php
// แก้ไข: เพิ่ม require_once และ require_login() เพื่อความปลอดภัยและการเข้าถึง $pdo
require_once __DIR__ . '/../helpers.php';
require_login();

// เตรียมตัวแปรสำหรับการแก้ไขหรือเพิ่มใหม่
$id = (int)($_GET['id'] ?? 0);
$editing = false;
$article = ['title' => '', 'content' => '', 'category' => '', 'tags' => '', 'image' => null];
$sites = $pdo->query('SELECT id, name FROM sites ORDER BY name')->fetchAll();
$selectedSites = [];
$config = require __DIR__ . '/../config.php'; // กำหนด $config สำหรับใช้แสดงรูปภาพ

if ($id) {
    $editing = true;
    // ดึงข้อมูลบทความ
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $fetchedArticle = $stmt->fetch();
    if (!$fetchedArticle) {
        header('Location: articles_list.php');
        exit;
    }
    $article = $fetchedArticle;

    // ดึงเว็บไซต์ที่เลือกไว้
    $stmt = $pdo->prepare('SELECT site_id FROM article_site WHERE article_id = ?');
    $stmt->execute([$id]);
    $selectedSites = $stmt->fetchAll(PDO::FETCH_COLUMN);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // แก้ไข: เพิ่ม CSRF check
    if (!check_csrf($_POST['csrf'] ?? '')) {
        die('CSRF validation failed.');
    }

    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $sitesSelected = $_POST['sites'] ?? [];


    // handle image upload - ใช้ helper function ที่ปลอดภัยกว่า
    $imagePath = $article['image'] ?? null;
    $uploadDir = __DIR__ . '/../uploads';

    $uploadResult = validate_and_move_upload($_FILES['image'] ?? null, $uploadDir);

    if ($uploadResult) {
        // ลบรูปเก่าหากมีและอัปโหลดรูปใหม่สำเร็จ
        if ($imagePath) {
            @unlink($uploadDir . DIRECTORY_SEPARATOR . $imagePath);
        }
        $imagePath = $uploadResult;
    }


    if ($editing) {
        $stmt = $pdo->prepare('UPDATE articles SET title=?, content=?, image=?, category=?, tags=?, updated_at=NOW() WHERE id=?');
        // หมายเหตุ: โค้ดนี้สมมติว่าตาราง articles มีคอลัมน์ category และ tags เป็นข้อความตามที่ฟอร์มใช้
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
        // หมายเหตุ: โค้ดนี้สมมติว่าตาราง articles มีคอลัมน์ category และ tags เป็นข้อความตามที่ฟอร์มใช้
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
        
        <textarea id="content" name="content" style="display:none;"><?php echo e($article['content']); ?></textarea>
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