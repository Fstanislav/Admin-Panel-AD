<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

secure_session_start();
send_security_headers();

// If already authenticated, go to dashboard
if (is_authenticated()) {
    header('Location: /ad-panel/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $token = $_POST['csrf_token'] ?? null;

    if (!verify_csrf_token($token)) {
        $error = 'Неверный CSRF-токен';
        log_action('login_failed_csrf', ['username' => $username]);
    } else {
        brute_force_delay();
        $admin = get_admin_credentials();
        if (hash_equals($admin['username'] ?? 'admin', $username) && !empty($admin['password_hash']) && password_verify($password, $admin['password_hash'])) {
            login_admin($username);
            log_action('login_success', ['username' => $username]);
            header('Location: /ad-panel/dashboard.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
            log_action('login_failed', ['username' => $username]);
        }
    }
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AD Control Panel — Вход</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
</head>
<body class="bg">
  <div class="container center">
    <div class="card login-card">
      <div class="logo">AD Control Panel</div>
      <h1 class="title">Вход</h1>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
      <?php endif; ?>
      <form method="post" class="form">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="field">
          <label for="username">Логин</label>
          <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="field">
          <label for="password">Пароль</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button class="btn primary w-full" type="submit">Войти</button>
      </form>
    </div>
  </div>
</body>
</html>
