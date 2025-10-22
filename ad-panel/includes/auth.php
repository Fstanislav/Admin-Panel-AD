<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

secure_session_start();
send_security_headers();
require_auth();
