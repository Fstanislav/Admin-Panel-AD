<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ldap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$username = trim((string)($_POST['username'] ?? ''));
$newPassword = (string)($_POST['new_password'] ?? '');
$token = $_POST['csrf_token'] ?? null;

if (!verify_csrf_token($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен']);
    exit;
}

if ($username === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Не указан пользователь']);
    exit;
}

list($ok, $msg) = validate_password_strength($newPassword);
if (!$ok) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

try {
    [$success, $message] = change_user_password($username, $newPassword);
    log_action('password_change', ['target' => $username, 'success' => $success]);
    echo json_encode(['success' => $success, 'message' => $message]);
} catch (Throwable $e) {
    log_action('password_change_error', ['target' => $username, 'error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Внутренняя ошибка сервера']);
}
