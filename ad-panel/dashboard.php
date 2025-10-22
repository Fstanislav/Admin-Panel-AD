<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
startSecureSession();
requireAuth();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AD Control Panel — Дашборд</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ad-panel/assets/css/style.css">
</head>
<body class="min-h-screen bg-surface">
  <header class="topbar">
    <div class="container">
      <div class="brand">AD Control Panel</div>
      <nav><a href="/ad-panel/logout.php" class="btn btn-ghost">Выход</a></nav>
    </div>
  </header>
  <main class="container py-12">
    <div class="center">
      <a class="btn btn-primary" href="/ad-panel/users.php">📋 Список пользователей Active Directory</a>
    </div>
  </main>
  <script src="/ad-panel/assets/js/main.js"></script>
</body>
</html>
