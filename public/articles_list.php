<?php
require_once __DIR__ . '/../helpers.php';
require_login();

// Pagination settings
$articlesPerPage = 10; // Number of articles to display per page
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Get total number of articles
$totalArticlesStmt = $pdo->query("SELECT COUNT(*) FROM articles");
$totalArticles = $totalArticlesStmt->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalArticles / $articlesPerPage);

// Ensure current page doesn't exceed total pages
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

// Calculate the offset for the SQL query
$offset = ($currentPage - 1) * $articlesPerPage;

$sql = "SELECT a.*, u.username, c.name as category_name, GROUP_CONCAT(t.name SEPARATOR ', ') as tags_list
        FROM articles a 
        LEFT JOIN users u ON a.created_by = u.id 
        LEFT JOIN categories c ON a.category_id = c.id
        LEFT JOIN article_tag at ON a.id = at.article_id
        LEFT JOIN tags t ON at.tag_id = t.id
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':limit', $articlesPerPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/_head.php';
?>

<div class="min-h-screen bg-gray-100">
    <?php include __DIR__ . '/_nav.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">Manage Articles</h1>
                    <a href="article_form" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg shadow-md hover:shadow-lg transition-transform transform hover:-translate-y-px">
                        + Add New Article
                    </a>
                </div>

                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tags</th>
                                    <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($articles as $a): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?php echo e($a['title']); ?></div></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php if ($a['image']): ?><img src="<?php echo e(str_replace('/public/', '/', $config['BASE_URL']) . 'uploads/' . basename($a['image'])); ?>" class="h-12 w-12 object-cover rounded"><?php endif; ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800"><?php echo e($a['category_name']); ?></span></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo e($a['tags_list']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="article_form?id=<?php echo $a['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                            <a href="delete_article?id=<?php echo $a['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this article?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6 mt-8 rounded-lg shadow-lg">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <a href="?page=<?php echo $currentPage - 1; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?php echo ($currentPage <= 1) ? 'pointer-events-none opacity-50' : ''; ?>">
                                Previous
                            </a>
                            <a href="?page=<?php echo $currentPage + 1; ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 <?php echo ($currentPage >= $totalPages) ? 'pointer-events-none opacity-50' : ''; ?>">
                                Next
                            </a>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium"><?php echo $offset + 1; ?></span>
                                    to
                                    <span class="font-medium"><?php echo min($offset + $articlesPerPage, $totalArticles); ?></span>
                                    of
                                    <span class="font-medium"><?php echo $totalArticles; ?></span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 <?php echo ($i == $currentPage) ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' : ''; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php
include __DIR__ . '/_footer.php';
?>