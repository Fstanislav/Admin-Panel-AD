<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/ldap.php';

$config = load_config();
$ldap = $config['ldap'] ?? [];
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AD Control Panel — Настройки</title>
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
      <a class="link" href="/ad-panel/users.php">Список пользователей</a>
      <a class="link" href="/ad-panel/logout.php">Выход</a>
    </nav>
  </header>

  <main class="container">
    <h1 class="title">Настройки LDAP</h1>

    <div class="card" style="padding: 20px; max-width: 720px;">
      <form id="settingsForm" class="form">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
        <div class="grid cols-2">
          <div class="field">
            <label for="host">Host (ldap:// или ldaps://)</label>
            <input id="host" name="host" required value="<?= e((string)($ldap['host'] ?? '')) ?>">
          </div>
          <div class="field">
            <label for="port">Порт</label>
            <input id="port" name="port" type="number" required value="<?= e((string)($ldap['port'] ?? '636')) ?>">
          </div>
        </div>
        <div class="field">
          <label for="base_dn">Base DN</label>
          <input id="base_dn" name="base_dn" required value="<?= e((string)($ldap['base_dn'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="service_dn">Service DN</label>
          <input id="service_dn" name="service_dn" required value="<?= e((string)($ldap['service_dn'] ?? '')) ?>">
        </div>
        <div class="field">
          <label for="service_password">Service Password</label>
          <input id="service_password" name="service_password" type="password" value="" placeholder="Не изменять — оставьте пустым">
        </div>
        <div class="grid cols-2">
          <div class="field">
            <label><input type="checkbox" id="start_tls" name="start_tls" <?= !empty($ldap['start_tls']) ? 'checked' : '' ?>> Использовать StartTLS (для ldap://)</label>
          </div>
          <div class="field">
            <label for="network_timeout">Сетевой таймаут, сек</label>
            <input id="network_timeout" name="network_timeout" type="number" value="<?= e((string)($ldap['network_timeout'] ?? '10')) ?>">
          </div>
        </div>
        <div class="row" style="display:flex; gap:10px; align-items:center;">
          <button type="button" class="btn" id="testBtn">Проверить подключение</button>
          <div id="statusBadge" class="badge">Статус: неизвестно</div>
          <div class="flex-spacer"></div>
          <button type="submit" class="btn primary">Сохранить</button>
        </div>
        <div id="settingsAlert" class="alert hidden" style="margin-top:12px;"></div>
      </form>
    </div>
  </main>

  <script>window.AD_PANEL = { csrf: '<?= e($csrf) ?>' };</script>
  <script src="/ad-panel/assets/js/main.js"></script>
</body>
</html>
