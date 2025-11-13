<?php
require_once __DIR__ . '/../helpers.php';
require_login();

function create_slug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9_\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_csrf($_POST['csrf'] ?? '')) {
        die('CSRF validation failed.');
    }

    $name = trim($_POST['name'] ?? '');
    if (!empty($name)) {
        $slug = create_slug($name);
        // Check if slug exists
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time(); // Append timestamp to make it unique
        }
        $pdo->prepare('INSERT INTO categories (name, slug) VALUES (?,?)')->execute([$name, $slug]);
        header('Location: categories');
        exit;
    }
}

// Pagination settings
$itemsPerPage = 10;
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Get total number of items
$totalItemsStmt = $pdo->query("SELECT COUNT(*) FROM categories");
$totalItems = $totalItemsStmt->fetchColumn();

// Calculate total pages
$totalPages = ceil($totalItems / $itemsPerPage);

if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $itemsPerPage;

$stmt = $pdo->prepare('SELECT * FROM categories ORDER BY name ASC LIMIT :limit OFFSET :offset');
$stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();

include __DIR__ . '/_head.php';
?>

<div class="min-h-screen bg-gray-100">
    <?php include __DIR__ . '/_nav.php'; ?>

    <main>
        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Categories</h1>

                <div class="bg-white shadow-lg rounded-lg overflow-hidden mb-8">
                    <form method="post" class="p-6 space-y-4">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <h2 class="text-xl font-semibold text-gray-800">Add New Category</h2>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Category Name</label>
                            <input type="text" id="name" name="name" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Add Category
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo e($c['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="delete_category?id=<?php echo $c['id']; ?>&csrf=<?php echo e(csrf_token()); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
                                    <span class="font-medium"><?php echo min($offset + $itemsPerPage, $totalItems); ?></span>
                                    of
                                    <span class="font-medium"><?php echo $totalItems; ?></span>
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