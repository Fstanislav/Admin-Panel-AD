<?php
// AD Control Panel configuration
// Fill in your environment-specific values before deploying.

return [
    'admin' => [
        // Default admin login. Change the username if desired.
        'username' => 'admin',
        // Bcrypt hash for the default password 'admin'. CHANGE IMMEDIATELY in production.
        // You can generate a new hash using PHP password_hash() or Python bcrypt.
        'password_hash' => '$2b$12$eEDdiznocQKrPgc/yeatVOnLLW3EIbqAinxsiVqjS55pjcF5xiiQy',
    ],

    'ldap' => [
        // Use ldaps:// for secure connection when changing passwords
        // Examples: 'ldaps://ad.example.com' or 'ldap://ad.example.com'
        'host' => 'ldaps://ad.example.com',
        'port' => 636, // 389 for ldap, 636 for ldaps
        'base_dn' => 'DC=example,DC=com',

        // Service account credentials with rights to reset passwords
        'service_dn' => 'CN=svc_adpanel,OU=Service Accounts,DC=example,DC=com',
        'service_password' => 'CHANGE_ME',

        // If connecting with ldap://, you can attempt StartTLS (must be supported by server)
        'start_tls' => false,

        // Timeouts (seconds)
        'bind_timeout' => 10,
        'network_timeout' => 10,
    ],

    'security' => [
        'session_name' => 'adpanel_session',
        'csrf_key' => 'adpanel_csrf_token',
    ],

    'logging' => [
        // Make sure web server can write to this file
        'file' => __DIR__ . '/../logs/actions.log',
    ],
];
