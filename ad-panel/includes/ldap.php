<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Minimal ldap_escape fallback for filter context if not available.
 */
if (!function_exists('ldap_escape')) {
    function ldap_escape(string $value, string $ignore = '', int $flags = 0): string {
        // Escape for filter by default
        $search = ['\\', '*', '(', ')', "\x00"];
        $replace = ['\\5c', '\\2a', '\\28', '\\29', '\\00'];
        return str_replace($search, $replace, $value);
    }
}

function ldap_config(): array {
    $config = load_config();
    return $config['ldap'];
}

function ldap_connection() {
    $cfg = ldap_config();
    $host = $cfg['host'] ?? 'ldap://localhost';
    $port = (int)($cfg['port'] ?? 389);

    $conn = @ldap_connect($host, $port);
    if (!$conn) {
        throw new RuntimeException('Не удалось подключиться к LDAP-серверу');
    }

    @ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, 3);
    @ldap_set_option($conn, LDAP_OPT_REFERRALS, 0);

    if (!empty($cfg['network_timeout'])) {
        @ldap_set_option($conn, LDAP_OPT_NETWORK_TIMEOUT, (int)$cfg['network_timeout']);
    }

    // StartTLS if requested and not already using ldaps://
    if (!empty($cfg['start_tls']) && stripos((string)$host, 'ldaps://') !== 0) {
        if (!@ldap_start_tls($conn)) {
            throw new RuntimeException('Не удалось установить StartTLS: ' . @ldap_error($conn));
        }
    }

    return $conn;
}

function ldap_bind_service($conn): void {
    $cfg = ldap_config();
    $dn = $cfg['service_dn'] ?? '';
    $pass = $cfg['service_password'] ?? '';
    if ($dn === '' || $pass === '') {
        throw new RuntimeException('Пустые параметры сервисной учётной записи для LDAP');
    }
    if (!@ldap_bind($conn, $dn, $pass)) {
        throw new RuntimeException('Ошибка bind LDAP: ' . @ldap_error($conn));
    }
}

function fetch_enabled_users(): array {
    $cfg = ldap_config();
    $baseDn = $cfg['base_dn'] ?? '';
    if ($baseDn === '') {
        throw new RuntimeException('Не задан base_dn в конфигурации LDAP');
    }

    $conn = ldap_connection();
    ldap_bind_service($conn);

    $filter = '(&(objectCategory=person)(objectClass=user)(userAccountControl:1.2.840.113556.1.4.803:=512))';
    $attrs = ['displayName', 'sAMAccountName'];

    $search = @ldap_search($conn, $baseDn, $filter, $attrs);
    if ($search === false) {
        $err = @ldap_error($conn);
        @ldap_unbind($conn);
        throw new RuntimeException('Ошибка поиска LDAP: ' . $err);
    }

    $entries = @ldap_get_entries($conn, $search);
    @ldap_unbind($conn);

    $users = [];
    if (is_array($entries) && isset($entries['count'])) {
        for ($i = 0; $i < $entries['count']; $i++) {
            $entry = $entries[$i];
            $display = $entry['displayname'][0] ?? '';
            $sam = $entry['samaccountname'][0] ?? '';
            if ($sam === '') { continue; }
            $users[] = [
                'displayName' => $display,
                'sAMAccountName' => $sam,
            ];
        }
    }

    usort($users, function ($a, $b) {
        return strcasecmp($a['displayName'] ?? '', $b['displayName'] ?? '');
    });

    return $users;
}

function find_user_dn_by_sam(string $samAccountName): ?string {
    $cfg = ldap_config();
    $baseDn = $cfg['base_dn'] ?? '';
    if ($baseDn === '') {
        throw new RuntimeException('Не задан base_dn в конфигурации LDAP');
    }

    $conn = ldap_connection();
    ldap_bind_service($conn);

    $filter = '(&(objectCategory=person)(objectClass=user)(sAMAccountName=' . ldap_escape($samAccountName) . '))';
    $search = @ldap_search($conn, $baseDn, $filter, ['dn']);
    if ($search === false) {
        $err = @ldap_error($conn);
        @ldap_unbind($conn);
        throw new RuntimeException('Ошибка поиска LDAP: ' . $err);
    }

    $entries = @ldap_get_entries($conn, $search);
    @ldap_unbind($conn);

    if (is_array($entries) && ($entries['count'] ?? 0) > 0) {
        return $entries[0]['dn'] ?? null;
    }
    return null;
}

function change_user_password(string $samAccountName, string $newPassword): array {
    // Returns [bool success, string message]
    $dn = find_user_dn_by_sam($samAccountName);
    if ($dn === null) {
        return [false, 'Пользователь не найден'];
    }

    $conn = ldap_connection();
    try {
        ldap_bind_service($conn);

        // AD requires quoting and UTF-16LE encoding for unicodePwd
        $quoted = '"' . $newPassword . '"';
        $pwd = mb_convert_encoding($quoted, 'UTF-16LE', 'UTF-8');

        $data = ['unicodePwd' => $pwd];
        $ok = @ldap_mod_replace($conn, $dn, $data);
        if (!$ok) {
            $err = @ldap_error($conn);
            return [false, 'Не удалось изменить пароль: ' . $err];
        }
        return [true, 'Пароль успешно изменён'];
    } finally {
        @ldap_unbind($conn);
    }
}
