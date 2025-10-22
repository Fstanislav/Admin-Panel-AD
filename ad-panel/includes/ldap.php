<?php
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

function escapeLdapFilter(string $value): string {
    $map = [
        '\\' => '\\5c',
        '*' => '\\2a',
        '(' => '\\28',
        ')' => '\\29',
        "\x00" => '\\00',
    ];
    return strtr($value, $map);
}

function ldapConnection() {
    $cfg = getConfig()['ldap'];
    $link = @ldap_connect((string)$cfg['host'], (int)$cfg['port']);
    if ($link === false) {
        throw new RuntimeException('Не удалось подключиться к LDAP');
    }
    ldap_set_option($link, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($link, LDAP_OPT_REFERRALS, !empty($cfg['follow_referrals']) ? 1 : 0);
    ldap_set_option($link, LDAP_OPT_NETWORK_TIMEOUT, 10);
    if (!empty($cfg['use_tls'])) {
        if (!@ldap_start_tls($link)) {
            throw new RuntimeException('Не удалось установить TLS соединение с LDAP');
        }
    }
    if (!@ldap_bind($link, (string)$cfg['bind_dn'], (string)$cfg['bind_password'])) {
        $err = ldap_error($link);
        throw new RuntimeException('Ошибка bind к LDAP: ' . $err);
    }
    return $link;
}

function ldapListEnabledUsers(): array {
    $cfg = getConfig()['ldap'];
    $link = ldapConnection();
    $filter = '(&(objectCategory=person)(objectClass=user)(userAccountControl:1.2.840.113556.1.4.803:=512))';
    $attrs = $cfg['user_attributes'] ?? ['displayName', 'sAMAccountName'];
    $search = @ldap_search($link, (string)$cfg['base_dn'], $filter, $attrs, 0, 2000, 15);
    if ($search === false) {
        $err = ldap_error($link);
        ldap_unbind($link);
        throw new RuntimeException('LDAP search error: ' . $err);
    }
    $entries = ldap_get_entries($link, $search);
    ldap_unbind($link);
    $users = [];
    if (!empty($entries['count'])) {
        for ($i = 0; $i < $entries['count']; $i++) {
            $e = $entries[$i];
            $users[] = [
                'displayName' => $e['displayname'][0] ?? '',
                'sAMAccountName' => $e['samaccountname'][0] ?? '',
            ];
        }
    }
    usort($users, function ($a, $b) {
        return strcmp(mb_strtolower($a['displayName']), mb_strtolower($b['displayName']));
    });
    return $users;
}

function ldapChangeUserPassword(string $username, string $newPassword): void {
    $username = sanitizeUsername($username);
    if ($username === '') {
        throw new InvalidArgumentException('Некорректное имя пользователя');
    }
    $cfg = getConfig()['ldap'];
    $link = ldapConnection();
    $filter = sprintf('(&(objectCategory=person)(objectClass=user)(sAMAccountName=%s))', escapeLdapFilter($username));
    $search = @ldap_search($link, (string)$cfg['base_dn'], $filter, ['dn']);
    if ($search === false) {
        $err = ldap_error($link);
        ldap_unbind($link);
        throw new RuntimeException('LDAP search error: ' . $err);
    }
    $entries = ldap_get_entries($link, $search);
    if (empty($entries['count'])) {
        ldap_unbind($link);
        throw new RuntimeException('Пользователь не найден');
    }
    $dn = $entries[0]['dn'];
    $pwdQuoted = '"' . $newPassword . '"';
    $pwd = mb_convert_encoding($pwdQuoted, 'UTF-16LE');
    $entry = ['unicodePwd' => $pwd];
    if (!@ldap_mod_replace($link, $dn, $entry)) {
        $err = ldap_error($link);
        ldap_unbind($link);
        throw new RuntimeException('Не удалось изменить пароль: ' . $err);
    }
    ldap_unbind($link);
}
