<?php /** @var string $title */ ?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= isset($title)?htmlspecialchars($title).' | ':'' ?>Helpdesk</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="/css/app.css">
</head>
<body class="auth-body">
  <main class="auth-shell" role="main">
    <?php include $___viewFile; ?>
  </main>
</body>
</html>
