<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function authAttempt(string $username, string $password): bool {
    startSecureSession();
    $cfg = getConfig();
    $admin = $cfg['admin'] ?? [];
    if (($admin['username'] ?? '') !== $username) {
        return false;
    }
    if (!password_verify($password, (string)($admin['password_hash'] ?? ''))) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['auth'] = [
        'user' => (string)$admin['username'],
        'time' => time(),
    ];
    return true;
}

function isAuthenticated(): bool {
    startSecureSession();
    return !empty($_SESSION['auth']);
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: /ad-panel/login.php');
        exit;
    }
}

function logout(): void {
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], isHttps(), true);
    }
    session_destroy();
}
