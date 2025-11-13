<?php
require_once __DIR__ . '/../helpers.php';
require_login();

// Get counts for dashboard widgets
$articleCount = $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn();
$siteCount = $pdo->query('SELECT COUNT(*) FROM sites')->fetchColumn();

// Get data for the last 7 days for the chart
$days = [];
$articleCountsByDay = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('D, M j', strtotime($date)); // Format for display
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $articleCountsByDay[] = $stmt->fetchColumn();
}

$chartData = [
    'labels' => $days,
    'data' => $articleCountsByDay,
];

include __DIR__ . '/_head.php'; // Include the common header
?>

<div class="min-h-screen bg-gray-100">
    <?php include __DIR__ . '/_nav.php'; ?>

    <!-- Main Content -->
    <main>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Articles Card -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 truncate">Total Articles</p>
                                </div>
                                <span class="text-4xl font-bold text-indigo-600"><?php echo $articleCount; ?></span>
                            </div>
                        </div>
                    </div>
                    <!-- Total Sites Card -->
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500 truncate">Total Sites</p>
                                </div>
                                <span class="text-4xl font-bold text-green-600"><?php echo $siteCount; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900">Articles Created (Last 7 Days)</h3>
                        <canvas id="articlesChart" class="mt-4"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Chart.js implementation
        const chartData = <?php echo json_encode($chartData); ?>;
        const ctx = document.getElementById('articlesChart').getContext('2d');
        const articlesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'New Articles',
                    data: chartData.data,
                    backgroundColor: 'rgba(79, 70, 229, 0.8)', // Indigo
                    borderColor: 'rgba(79, 70, 229, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1 // Ensure y-axis only shows whole numbers
                        }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    });
</script>

<?php
include __DIR__ . '/_footer.php';
?>
