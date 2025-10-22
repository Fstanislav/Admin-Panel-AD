<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/ldap.php';

$users = [];
$error = '';
try {
    $users = fetch_enabled_users();
} catch (Throwable $e) {
    $error = $e->getMessage();
    log_action('ldap_fetch_users_error', ['error' => $error]);
}

$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AD Control Panel — Пользователи</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">AD Control Panel</div>
    <nav>
      <a class="link" href="/ad-panel/dashboard.php">Дашборд</a>
      <a class="link" href="/ad-panel/logout.php">Выход</a>
    </nav>
  </header>

  <main class="container">
    <h1 class="title">Список пользователей</h1>

    <?php if ($error): ?>
      <div class="alert alert-error">Ошибка: <?= e($error) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>ФИО</th>
            <th>Логин</th>
            <th>Действие</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= e($u['displayName'] ?? '') ?></td>
            <td><?= e($u['sAMAccountName'] ?? '') ?></td>
            <td>
              <button class="btn small" data-edit-user="<?= e($u['sAMAccountName'] ?? '') ?>">Редактировать</button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </main>

  <div id="modal" class="modal hidden" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="modal-backdrop" data-close></div>
    <div class="modal-content" role="document">
      <div class="modal-header">
        <div class="modal-title">Смена пароля</div>
        <button class="icon-btn" data-close aria-label="Закрыть">✕</button>
      </div>
      <div class="modal-body">
        <form id="passwordForm">
          <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
          <input type="hidden" name="username" id="usernameField" value="">
          <div class="field">
            <label for="new_password">Новый пароль</label>
            <input type="password" id="new_password" name="new_password" required>
          </div>
          <div class="field">
            <label for="confirm_password">Подтверждение пароля</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
          </div>
        </form>
        <div class="alert hidden" id="modalAlert"></div>
      </div>
      <div class="modal-footer">
        <button class="btn" data-close>Отмена</button>
        <button class="btn primary" id="saveBtn">Сохранить</button>
      </div>
    </div>
  </div>

  <script>window.AD_PANEL = { csrf: '<?= e($csrf) ?>' };</script>
  <script src="/ad-panel/assets/js/main.js"></script>
</body>
</html>
