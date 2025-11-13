<?php
session_start();
require_once __DIR__ . '/db.php';
$config = require __DIR__ . '/config.php';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
function csrf_token()
{
    return $_SESSION['csrf_token'];
}
function check_csrf($token)
{
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in()
{
    return !empty($_SESSION['user']);
}
function require_login()
{
    if (!is_logged_in()) {
        header('Location: login');
        exit;
    }
}
function current_user()
{
    return $_SESSION['user'] ?? null;
}
function e($s)
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function flash_set($k, $v)
{
    $_SESSION['flash'][$k] = $v;
}
function flash_get($k)
{
    $v = $_SESSION['flash'][$k] ?? null;
    unset($_SESSION['flash'][$k]);
    return $v;
}

function validate_and_move_upload($file, $destDir)
{
    if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null; // 2MB
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($file['name']));
    $target = $destDir . DIRECTORY_SEPARATOR . $name;
    if (move_uploaded_file($file['tmp_name'], $target)) return $name;
    return null;
}

function secure_session_regen()
{
    session_regenerate_id(true);
}
