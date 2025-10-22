<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ldap.php';
requireAuth();

$input = file_get_contents('php://input');
$data = json_decode($input, true) ?: [];
$username = sanitizeUsername((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');
$errors = validatePassword($password);
if ($username === '' || $errors) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $errors ? implode('; ', $errors) : 'Некорректные данные'], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    ldapChangeUserPassword($username, $password);
    logAction('password_change', 'success', $username, getClientIp());
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    logAction('password_change', 'failure', $username, getClientIp(), ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Не удалось изменить пароль (возможно, недостаточно прав или учётная запись заблокирована)'], JSON_UNESCAPED_UNICODE);
}
