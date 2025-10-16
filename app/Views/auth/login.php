<section class="login-stage" aria-labelledby="login-title">
  <div class="login-stage__container">
    <div class="login-stage__grid">
      <div class="login-tile login-tile--accent login-tile--accent-one" aria-hidden="true"></div>
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
            <i>Usuario o Email</i>
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
      <div class="login-tile login-tile--accent login-tile--accent-two" aria-hidden="true"></div>
      <div class="login-tile login-tile--accent login-tile--accent-three" aria-hidden="true"></div>
    </div>
  </div>
</section>
