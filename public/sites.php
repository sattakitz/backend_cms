<?php
require_once __DIR__ . '/../helpers.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $domain = $_POST['domain'];
    $pdo->prepare('INSERT INTO sites (name,domain) VALUES (?,?)')->execute([$name, $domain]);
    header('Location: sites.php');
    exit;
}
$sites = $pdo->query('SELECT * FROM sites ORDER BY name')->fetchAll();
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Sites</title>
</head>

<body>
    <h1>Manage Sites</h1>
    <form method="post">
        <label>Name: <input name="name" required></label>
        <label>Domain (optional): <input name="domain"></label>
        <button>Add Site</button>
    </form>
    <table border="1" cellpadding="6">
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Domain</th>
        </tr>
        <?php foreach ($sites as $s): ?>
            <tr>
                <td><?php echo e($s['id']); ?></td>
                <td><?php echo e($s['name']); ?></td>
                <td><?php echo e($s['domain']); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><a href="dashboard.php">Back</a></p>
</body>

</html>