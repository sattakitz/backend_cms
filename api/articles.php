<?php
// Public API: GET ?site_id=ID or GET ?article_id=ID
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php'; // For e()

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/../config.php';

/**
 * Sends a JSON response with a specific HTTP status code.
 *
 * @param mixed $data
 * @param int $statusCode
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

/**
 * Checks if the API token is valid for the given site.
 *
 * @param PDO $pdo
 * @param int $siteId
 * @return bool
 */
function is_token_valid(PDO $pdo, int $siteId): bool {
    $token = null;
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            $token = $matches[1];
        }
    }

    if (!$token) {
        return false;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM site_tokens WHERE site_id = ? AND token = ?');
    $stmt->execute([$siteId, $token]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Fetches and formats a single article by its ID.
 *
 * @param PDO $pdo
 * @param int $articleId
 * @param array $config
 * @return array|null
 */
function get_article_by_id(PDO $pdo, int $articleId, array $config): ?array {
    $sql = "SELECT a.*, c.name as category_name, u.username as author,
                   GROUP_CONCAT(DISTINCT t.name SEPARATOR ', ') as tags_list
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            LEFT JOIN users u ON a.created_by = u.id
            LEFT JOIN article_tag at ON a.id = at.article_id
            LEFT JOIN tags t ON at.tag_id = t.id
            WHERE a.id = ?
            GROUP BY a.id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$articleId]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($article) {
        if ($article['image']) {
            $article['image'] = $config['BASE_URL'] . 'uploads/' . basename($article['image']);
        }
        // Convert tags string to array
        $article['tags'] = $article['tags_list'] ? explode(', ', $article['tags_list']) : [];
        unset($article['tags_list']); // Clean up
    }

    return $article;
}

$siteId = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;

if ($articleId) {
    $article = get_article_by_id($pdo, $articleId, $config);
    if ($article) {
        json_response($article);
    } else {
        json_response(['error' => 'Article not found.'], 404);
    }
}

if (!$siteId) {
    json_response(['error' => 'Parameter `site_id` is required.'], 400);
}

// If token protection is enabled, validate the token
if ($config['API_TOKEN_PROTECT'] && !is_token_valid($pdo, $siteId)) {
    json_response(['error' => 'Unauthorized. Invalid or missing API token.'], 401);
}

$sql = "SELECT a.id FROM articles a JOIN article_site s ON a.id = s.article_id WHERE s.site_id = ? ORDER BY a.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$siteId]);
$articleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

$articles = [];
foreach ($articleIds as $id) {
    $articles[] = get_article_by_id($pdo, $id, $config);
}

json_response($articles);
