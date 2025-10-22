<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
logout();
header('Location: /ad-panel/login.php');
exit;
