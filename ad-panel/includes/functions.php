<?php
declare(strict_types=1);

// Common helpers: session, security headers, CSRF, logging, validators, auth helpers

function load_config(): array {
    static $config = null;
    if ($config === null) {
        $path = __DIR__ . '/../config/ldap.php';
        if (!is_file($path)) {
            throw new RuntimeException('Файл конфигурации не найден: ' . $path);
        }
        $config = require $path;
    }
    return $config;
}

function secure_session_start(): void {
    $config = load_config();
    $sessionName = $config['security']['session_name'] ?? 'adpanel_session';
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name($sessionName);
        session_start();
    }
}

function send_security_headers(): void {
    // Basic hardening headers
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // CSP allows self + Google Fonts
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; script-src 'self'; base-uri 'self'; frame-ancestors 'none'");
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function is_ajax(): bool {
    return (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
}

function brute_force_delay(): void {
    // 1-3 seconds random delay
    usleep(random_int(1_000_000, 3_000_000));
}

function validate_password_strength(string $password): array {
    if (strlen($password) < 8) {
        return [false, 'Пароль должен быть не менее 8 символов'];
    }
    if (!preg_match('/[A-Za-zА-Яа-я]/u', $password)) {
        return [false, 'Пароль должен содержать хотя бы одну букву'];
    }
    if (!preg_match('/\d/', $password)) {
        return [false, 'Пароль должен содержать хотя бы одну цифру'];
    }
    return [true, ''];
}

function log_action(string $action, array $context = []): void {
    $config = load_config();
    $file = $config['logging']['file'] ?? (__DIR__ . '/../logs/actions.log');
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $now = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    $user = $_SESSION['username'] ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $entry = [
        'time' => $now . 'Z',
        'user' => $user,
        'ip' => $ip,
        'action' => $action,
        'context' => $context,
    ];
    @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function is_authenticated(): bool {
    return !empty($_SESSION['is_authenticated']);
}

function require_auth(): void {
    if (!is_authenticated()) {
        header('Location: /ad-panel/login.php');
        exit;
    }
}

function login_admin(string $username): void {
    $_SESSION['is_authenticated'] = true;
    $_SESSION['username'] = $username;
    session_regenerate_id(true);
}

function logout_admin(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function get_admin_credentials(): array {
    $config = load_config();
    return $config['admin'] ?? ['username' => 'admin', 'password_hash' => ''];
}
