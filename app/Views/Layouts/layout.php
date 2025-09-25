<?php /** @var string $title */ ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= isset($title)?htmlspecialchars($title).' | ':'' ?>Helpdesk</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
  <link rel="stylesheet" href="/css/app.css">
  <link rel="stylesheet" href="/css/comercial_style/comercial-entidades.css">
  <link rel="stylesheet" href="/css/comercial_style/entidades-cards.css">
</head>
<body>
  <?php include __DIR__ . '/sidebar.php'; ?>
  <main class="content">
    <?php include $___viewFile; ?>
  </main>
</body>
<script src="/js/entidades.js" defer></script>
</html>
