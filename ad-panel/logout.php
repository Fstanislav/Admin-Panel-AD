<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

secure_session_start();
logout_admin();
log_action('logout', []);
header('Location: /ad-panel/login.php');
exit;
