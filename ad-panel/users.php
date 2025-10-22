<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ldap.php';
startSecureSession();
requireAuth();

$users = [];
$error = null;
try {
    $users = ldapListEnabledUsers();
} catch (Throwable $e) {
    $error = 'Ошибка загрузки пользователей: ' . e($e->getMessage());
}
$csrf = csrfToken();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AD Control Panel — Пользователи</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
  <style>
    /* Mobile cards for table */
    @media (max-width: 720px){
      .table thead{display:none}
      .table tr{display:block;border-bottom:1px solid rgba(255,255,255,.06)}
      .table td{display:flex;justify-content:space-between;gap:12px}
      .table td::before{content:attr(data-label);color:var(--muted)}
      .text-right{justify-content:flex-end}
    }
  </style>
</head>
<body class="min-h-screen bg-surface">
  <header class="topbar">
    <div class="container">
      <div class="brand">AD Control Panel</div>
      <nav><a href="/ad-panel/logout.php" class="btn btn-ghost">Выход</a></nav>
    </div>
  </header>
  <main class="container py-8">
    <h1 class="text-xl mb-4">Пользователи</h1>
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr><th>ФИО</th><th>Логин</th><th class="text-right">Действие</th></tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td data-label="ФИО"><?php echo e($u['displayName'] ?? ''); ?></td>
            <td data-label="Логин"><?php echo e($u['sAMAccountName'] ?? ''); ?></td>
            <td data-label="Действие" class="text-right">
              <button class="btn btn-secondary" data-edit data-username="<?php echo e($u['sAMAccountName'] ?? ''); ?>">Редактировать</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>

  <div id="modal" class="modal hidden" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content">
      <h2 id="modal-title" class="text-lg mb-2">Сменить пароль</h2>
      <form id="password-form" class="space-y-3">
        <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
        <input type="hidden" name="username" id="username">
        <label class="field">
          <span>Новый пароль</span>
          <input type="password" name="password" required>
        </label>
        <label class="field">
          <span>Подтверждение пароля</span>
          <input type="password" name="password2" required>
        </label>
        <div class="flex gap-2 justify-end">
          <button class="btn btn-ghost" type="button" data-close>Отмена</button>
          <button class="btn btn-primary" type="submit">Сохранить</button>
        </div>
        <div class="form-msg"></div>
      </form>
    </div>
  </div>

  <script src="/ad-panel/assets/js/main.js"></script>
  <script>
    window.__CSRF__ = '<?php echo e($csrf); ?>';
  </script>
</body>
</html>
