<?php
// LDAP/APP configuration
// Fill these values for your environment.
return [
    'admin' => [
        // Web-panel local admin login (not AD)
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
        // Address of your DC or load balancer. Use ldap:// or ldaps://
        'host' => 'ldap://192.168.0.3',
        'port' => 389,

        // (Simplified) Provide AD admin login and password only.
        // Prefer UPN format for username: administrator@example.local
        // DOMAIN\\user is also supported. If neither contains a domain, and 'domain'
        // is set below, UPN will be constructed automatically.
        'admin_username' => 'stassaphir',
        'admin_password' => '111',

        // Optional: your AD domain. Used to auto-derive Base DN when not provided,
        // or to build UPN if username lacks domain.
        // If username lacks domain, you can set domain here, e.g. example.local
        'domain' => '',

        // Optional override: Base DN. If empty, will be derived from 'domain'.
        'base_dn' => '',

        'user_attributes' => ['displayName', 'sAMAccountName', 'userAccountControl'],
        'use_tls' => false,
        'follow_referrals' => false,
    ],
    'logging' => [
        'file' => __DIR__ . '/../logs/actions.log',
    ],
];
