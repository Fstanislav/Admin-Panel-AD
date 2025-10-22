<?php
declare(strict_types=1);

function getConfig(): array {
    static $cfg = null;
    if ($cfg === null) {
        $cfg = require __DIR__ . '/../config/ldap.php';
    }
    return $cfg;
}

function isHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') return true;
    return false;
}

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $cfg = getConfig();
        $sessionName = $cfg['security']['session_name'] ?? 'adcp_session';
        session_name($sessionName);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function isPost(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

// CSRF helpers removed per simplified mode request

function getClientIp(): string {
    $candidates = [
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'REMOTE_ADDR',
    ];
    foreach ($candidates as $k) {
        if (!empty($_SERVER[$k])) {
            $val = (string)$_SERVER[$k];
            // In case of XFF: "ip, ip, ip"
            if ($k === 'HTTP_X_FORWARDED_FOR' && strpos($val, ',') !== false) {
                $parts = array_map('trim', explode(',', $val));
                return (string)($parts[0] ?? $val);
            }
            return $val;
        }
    }
    return 'unknown';
}

function validatePassword(string $password): array {
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'Длина пароля должна быть не менее 8 символов';
    if (!preg_match('/[A-Za-zА-Яа-я]/u', $password)) $errors[] = 'Пароль должен содержать буквы';
    if (!preg_match('/\d/', $password)) $errors[] = 'Пароль должен содержать цифры';
    return $errors;
}

function sanitizeUsername(string $username): string {
    return preg_replace('/[^A-Za-z0-9._-]/', '', $username);
}

function logAction(string $action, string $status, string $username, string $ip, array $context = []): void {
    $cfg = getConfig();
    $file = $cfg['logging']['file'] ?? __DIR__ . '/../logs/actions.log';
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0770, true);
    }
    $line = sprintf("%s\t%s\t%s\t%s\t%s", date('c'), $action, $status, $username, $ip);
    if ($context) {
        $line .= "\t" . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $line .= "\n";
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}
