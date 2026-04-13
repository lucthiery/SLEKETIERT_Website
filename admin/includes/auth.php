<?php
require_once __DIR__ . '/db.php';

function require_login(): array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['user_id'])) {
        header('Location: /admin/');
        exit;
    }
    $db = get_db();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        session_destroy();
        header('Location: /admin/');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_login();
    if (!$user['is_admin']) {
        http_response_code(403);
        include __DIR__ . '/../403.php';
        exit;
    }
    return $user;
}
