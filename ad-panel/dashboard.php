<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AD Control Panel — Дашборд</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">AD Control Panel</div>
    <nav>
      <a class="link" href="/ad-panel/users.php">Список пользователей</a>
      <a class="link" href="/ad-panel/settings.php">Настройки</a>
      <a class="link" href="/ad-panel/logout.php">Выход</a>
    </nav>
  </header>

  <main class="container center">
    <div class="card hero">
      <div class="hero-title">Управление пользователями Active Directory</div>
      <div class="hero-subtitle">Просмотр и смена паролей через безопасный интерфейс</div>
      <a class="btn primary hero-btn" href="/ad-panel/users.php">📋 Список пользователей</a>
    </div>
  </main>
</body>
</html>
