<section class="card">
  <h1>Ingresar</h1>
  <?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post" class="form">
    <label>Usuario o Email
      <input type="text" name="id" required autofocus>
    </label>
    <label>Contraseña
      <input type="password" name="password" required>
    </label>
    <button class="btn btn-primary" type="submit">Entrar</button>
  </form>
</section>
