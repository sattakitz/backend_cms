<?php
$current_page = basename($_SERVER['PHP_SELF']);

$nav_items = [
    'dashboard.php' => 'Dashboard',
    'articles_list.php' => 'Articles',
    'categories.php' => 'Categories',
    'tags.php' => 'Tags',
    'sites.php' => 'Sites',
];
?>

<!-- Navbar -->
<nav class="bg-white shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="dashboard" class="text-2xl font-bold text-gray-800">CMS</a>
                </div>
                <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
                    <?php foreach ($nav_items as $file => $name): ?>
                        <?php
                        // Special handling for article_form to highlight 'Articles'
                        $is_active = ($current_page === $file) || ($current_page === 'article_form.php' && $file === 'articles_list.php');
                        $active_class = $is_active ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                        ?>
                        <a href="<?php echo str_replace('.php', '', $file); ?>" class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium <?php echo $active_class; ?>">
                            <?php echo $name; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex items-center">
                <span class="text-gray-600 mr-4 text-sm">Welcome, <?php echo e(current_user()['username']); ?></span>
                <button id="logout-button" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-md text-sm font-medium">Logout</button>
            </div>
        </div>
    </div>
</nav>