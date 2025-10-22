<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();

$errors = [];
if (isPost()) {
    if (!csrfCheck($_POST['csrf'] ?? '')) {
        $errors[] = 'Неверный CSRF-токен';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        if ($username === '' || $password === '') {
            $errors[] = 'Введите логин и пароль';
        } else {
            if (authAttempt($username, $password)) {
                logAction('login', 'success', $username, getClientIp());
                header('Location: /ad-panel/dashboard.php');
                exit;
            } else {
                usleep(random_int(800000, 2000000));
                $errors[] = 'Неверный логин или пароль';
                logAction('login', 'failure', $username, getClientIp());
            }
        }
    }
}
$csrf = csrfToken();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AD Control Panel — Вход</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-surface">
  <main class="card w-full max-w-md p-8">
    <div class="flex items-center gap-3 mb-6">
      <div class="logo"></div>
      <h1 class="text-xl">AD Control Panel</h1>
    </div>
    <?php if ($errors): ?>
      <div class="alert alert-error"><?php echo e(implode('<br>', $errors)); ?></div>
    <?php endif; ?>
    <form method="post" class="space-y-4">
      <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
      <label class="field">
        <span>Логин</span>
        <input type="text" name="username" autocomplete="username" required>
      </label>
      <label class="field">
        <span>Пароль</span>
        <input type="password" name="password" autocomplete="current-password" required>
      </label>
      <button class="btn btn-primary w-full" type="submit">Войти</button>
    </form>
  </main>
  <script src="/ad-panel/assets/js/main.js"></script>
</body>
</html>
