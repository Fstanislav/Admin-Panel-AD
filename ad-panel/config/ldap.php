<?php
// LDAP/APP configuration
// Fill these values for your environment.
return [
    'admin' => [
        // Change this login if needed
        'username' => 'admin',
        // bcrypt hash for default password 'admin' — REPLACE in production
        'password_hash' => password_hash('admin', PASSWORD_BCRYPT),
    ],
    'security' => [
        'session_name' => 'adcp_session',
        'csrf_token_key' => 'adcp_csrf',
        'allowed_origin' => '',
    ],
    'ldap' => [
        'host' => 'ldap://ad.example.local',
        'port' => 389,
        'base_dn' => 'DC=example,DC=local',
        'bind_dn' => 'CN=svc-ad,OU=Service Accounts,DC=example,DC=local',
        'bind_password' => 'CHANGE_ME',
        'user_attributes' => ['displayName', 'sAMAccountName', 'userAccountControl'],
        'use_tls' => false,
        'follow_referrals' => false
    ],
    'logging' => [
        'file' => __DIR__ . '/../logs/actions.log'
    ]
];
