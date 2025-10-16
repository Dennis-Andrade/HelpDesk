<section class="auth-card" aria-labelledby="auth-heading">
  <div class="auth-card__header">
    <img class="auth-card__logo" src="/img/logo-galaxy.svg" alt="HelpDesk" width="160" height="64">
    <h1 id="auth-heading">Bienvenido</h1>
    <p class="auth-card__lead">Ingresa tus credenciales para acceder al panel de gestión.</p>
  </div>
  <?php if (!empty($error)): ?>
    <div class="auth-card__alert" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post" class="auth-form" autocomplete="on">
    <div class="auth-field">
      <label for="auth-id">Usuario o Email</label>
      <input id="auth-id" type="text" name="id" required autofocus autocomplete="username">
    </div>
    <div class="auth-field">
      <label for="auth-password">Contraseña</label>
      <input id="auth-password" type="password" name="password" required autocomplete="current-password">
    </div>
    <button class="auth-submit" type="submit">Iniciar sesión</button>
  </form>
</section>
