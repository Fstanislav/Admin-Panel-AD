<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
    exit;
}

$token = $_POST['csrf_token'] ?? null;
if (!verify_csrf_token($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверный CSRF-токен']);
    exit;
}

$host = trim((string)($_POST['host'] ?? ''));
$port = (int)($_POST['port'] ?? 0);
$base_dn = trim((string)($_POST['base_dn'] ?? ''));
$service_dn = trim((string)($_POST['service_dn'] ?? ''));
$service_password = (string)($_POST['service_password'] ?? '');
$start_tls = isset($_POST['start_tls']) && ($_POST['start_tls'] === '1' || $_POST['start_tls'] === 'true' || $_POST['start_tls'] === 'on');
$network_timeout = (int)($_POST['network_timeout'] ?? 10);

if ($host === '' || $port <= 0 || $base_dn === '' || $service_dn === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Заполните все обязательные поля']);
    exit;
}

// Attempt connection and bind
try {
    if (!function_exists('ldap_connect')) {
        throw new RuntimeException('PHP-расширение LDAP не установлено');
    }
    $conn = @ldap_connect($host, $port);
    if (!$conn) {
        throw new RuntimeException('Не удалось подключиться к LDAP-серверу');
    }
    @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);
    if ($network_timeout > 0) {
        @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, $network_timeout);
    }
    if ($start_tls && stripos($host, 'ldaps://') !== 0) {
        if (!@ldap_start_tls($conn)) {
            throw new RuntimeException('Не удалось установить StartTLS: ' . @ldap_error($conn));
        }
    }
    if (!@ldap_bind($conn, $service_dn, $service_password)) {
        throw new RuntimeException('Ошибка bind LDAP: ' . @ldap_error($conn));
    }
    @ldap_unbind($conn);
    log_action('settings_test_success', ['host' => $host, 'base_dn' => $base_dn, 'service_dn' => $service_dn]);
    echo json_encode(['success' => true, 'message' => 'Подключение успешно']);
} catch (Throwable $e) {
    log_action('settings_test_error', ['error' => $e->getMessage()]);
    http_response_code(200);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
