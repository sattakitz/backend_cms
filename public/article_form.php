<?php
// แก้ไข: เพิ่ม require_once และ require_login() เพื่อความปลอดภัยและการเข้าถึง $pdo
require_once __DIR__ . '/../helpers.php';
require_login();

// เตรียมตัวแปรสำหรับการแก้ไขหรือเพิ่มใหม่
$id = (int)($_GET['id'] ?? 0);
$editing = false;
$article = ['title' => '', 'content' => '', 'category_id' => null, 'image' => null];
$sites = $pdo->query('SELECT id, name FROM sites ORDER BY name')->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$tags = $pdo->query('SELECT id, name FROM tags ORDER BY name')->fetchAll();

$selectedSites = [];
$selectedTags = [];

$config = require __DIR__ . '/../config.php'; // กำหนด $config สำหรับใช้แสดงรูปภาพ

if ($id) {
    $editing = true;
    // ดึงข้อมูลบทความ
    $stmt = $pdo->prepare('SELECT * FROM articles WHERE id = ?');
    $stmt->execute([$id]);
    $fetchedArticle = $stmt->fetch();
    if (!$fetchedArticle) {
        header('Location: articles_list');
        exit;
    }
    $article = $fetchedArticle;

    // ดึงเว็บไซต์ที่เลือกไว้
    $stmt = $pdo->prepare('SELECT site_id FROM article_site WHERE article_id = ?');
    $stmt->execute([$id]);
    $selectedSites = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // ดึงแท็กที่เลือกไว้
    $stmt = $pdo->prepare('SELECT tag_id FROM article_tag WHERE article_id = ?');
    $stmt->execute([$id]);
    $selectedTags = $stmt->fetchAll(PDO::FETCH_COLUMN);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // แก้ไข: เพิ่ม CSRF check
    if (!check_csrf($_POST['csrf'] ?? '')) {
        die('CSRF validation failed.');
    }

    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $sitesSelected = $_POST['sites'] ?? [];
    $tagsSelected = $_POST['tags'] ?? [];


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
        $stmt = $pdo->prepare('UPDATE articles SET title=?, content=?, image=?, category_id=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$title, $content, $imagePath, $categoryId, $id]);

        // update sites
        $pdo->prepare('DELETE FROM article_site WHERE article_id=?')->execute([$id]);
        foreach ($sitesSelected as $sid) {
            $pdo->prepare('INSERT INTO article_site (article_id, site_id) VALUES (?,?)')->execute([$id, $sid]);
        }

        // update tags
        $pdo->prepare('DELETE FROM article_tag WHERE article_id=?')->execute([$id]);
        foreach ($tagsSelected as $tid) {
            $pdo->prepare('INSERT INTO article_tag (article_id, tag_id) VALUES (?,?)')->execute([$id, $tid]);
        }

        header('Location: articles_list');
        exit;
    } else {
        $stmt = $pdo->prepare('INSERT INTO articles (title,content,image,category_id,created_by) VALUES (?,?,?,?,?)');
        $stmt->execute([$title, $content, $imagePath, $categoryId, current_user()['id']]);
        $articleId = $pdo->lastInsertId();

        foreach ($sitesSelected as $sid) {
            $pdo->prepare('INSERT INTO article_site (article_id, site_id) VALUES (?,?)')->execute([$articleId, $sid]);
        }
        foreach ($tagsSelected as $tid) {
            $pdo->prepare('INSERT INTO article_tag (article_id, tag_id) VALUES (?,?)')->execute([(int)$articleId, (int)$tid]);
        }

        header('Location: articles_list');
        exit;
    }
}

include __DIR__ . '/_head.php';
?>

<div class="min-h-screen bg-gray-100">
    <?php include __DIR__ . '/_nav.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6"><?php echo $editing ? 'Edit' : 'Add New'; ?> Article</h1>

                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <form method="post" enctype="multipart/form-data" class="p-8 space-y-6">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" id="title" name="title" value="<?php echo e($article['title']); ?>" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                                <select id="category" name="category_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($article['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                            <?php echo e($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="tags" class="block text-sm font-medium text-gray-700">Tags</label>
                                <select id="tags" name="tags[]" multiple class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm h-32">
                                    <?php foreach ($tags as $tag): ?>
                                        <option value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selectedTags) ? 'selected' : ''; ?>><?php echo e($tag['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700">Featured Image</label>
                            <input type="file" id="image" name="image" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <!-- Image Preview Container -->
                            <div id="image-preview-container" class="mt-4 <?php echo empty($article['image']) ? 'hidden' : ''; ?>">
                                <p id="image-preview-label" class="text-sm text-gray-500">Current Image:</p>
                                <img id="image-preview" src="<?php echo !empty($article['image']) ? e(str_replace('/public/', '/', $config['BASE_URL']) . 'uploads/' . basename($article['image'])) : ''; ?>" class="mt-2 h-80 w-auto object-cover rounded-md border border-gray-200">
                            </div>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                            <textarea id="content" name="content" class="mt-1"><?php echo e($article['content']); ?></textarea>
                        </div>

                        <fieldset>
                            <legend class="text-base font-medium text-gray-900">Publish to Sites</legend>
                            <div class="mt-4 space-y-4">
                                <?php foreach ($sites as $s): ?>
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="site-<?php echo $s['id']; ?>" name="sites[]" type="checkbox" value="<?php echo $s['id']; ?>" <?php echo in_array($s['id'], $selectedSites) ? 'checked' : ''; ?> class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="site-<?php echo $s['id']; ?>" class="font-medium text-gray-700"><?php echo e($s['name']); ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </fieldset>

                        <div class="flex justify-end">
                            <a href="articles_list" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</a>
                            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <?php echo $editing ? 'Update Article' : 'Create Article'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    // Ensure TinyMCE is loaded before initializing
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Select2 for the tags dropdown
        $('#tags').select2({
            placeholder: "Select tags",
            allowClear: true
        });

        if (typeof tinymce !== 'undefined') {
            tinymce.init({
                selector: '#content',
                height: 500,
                plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
            });
        } else {
            console.error("TinyMCE script not loaded.");
        }

        // Image preview script
        const imageInput = document.getElementById('image');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const imagePreview = document.getElementById('image-preview');
        const imagePreviewLabel = document.getElementById('image-preview-label');

        imageInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreviewLabel.textContent = 'New Image Preview:';
                    imagePreviewContainer.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            }
        });
    });
</script>

<?php
include __DIR__ . '/_footer.php';
?>