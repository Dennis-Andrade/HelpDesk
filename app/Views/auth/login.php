<section class="login-stage" aria-labelledby="login-title">
  <div class="login-stage__container">
    <div class="login-stage__tile-grid" aria-hidden="true">
      <?php
      $accentClasses = [
        'login-tile--accent-one',
        'login-tile--accent-two',
        'login-tile--accent-three',
        'login-tile--accent-four',
      ];
      for ($i = 0; $i < 400; $i++):
        $accentClass = $accentClasses[$i % count($accentClasses)];
      ?>
        <span class="login-tile login-tile--accent <?= $accentClass ?>"></span>
      <?php endfor; ?>
    </div>
    <div class="login-stage__content">
      <div class="login-tile login-tile--form">
        <img class="login-logo" src="/img/logoblanco.png" alt="HelpDesk" width="168" height="64">
        <h1 id="login-title">Bienvenido nuevamente</h1>
        <p class="login-lead">Accede al panel ingresando tus credenciales.</p>
        <?php if (!empty($error)): ?>
          <div class="login-alert" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="post" class="login-form" autocomplete="on">
          <div class="inputBox">
            <input id="login-id" type="text" name="id" placeholder=" " required autofocus autocomplete="username">
            <i>Usuario</i>
          </div>
          <div class="inputBox">
            <input id="login-password" type="password" name="password" placeholder=" " required autocomplete="current-password">
            <i>Contraseña</i>
          </div>
          <div class="inputBox inputBox--submit">
            <input type="submit" value="Iniciar sesión">
          </div>
        </form>
      </div>
    </div>
  </div>
</section>
