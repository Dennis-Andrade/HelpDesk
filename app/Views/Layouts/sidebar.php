<aside class="sidebar">
  <img src="/img/logo.png" alt="Logo">
  <br></br>
  <div class="brand">
    <span>Helpdesk</span>
  </div>
  <nav>
    <div class="nav-group">Comercial</div>
    <a href="/comercial/dashboard" class="nav-item">Dashboard</a>
    <a href="/comercial/entidades/cards" class="nav-item">Entidades Financieras</a>
    <a href="/comercial/contactos" class="nav-item">Agenda de Contactos</a>
    <a href="/comercial/eventos" class="nav-item">Eventos</a>
    <a href="/comercial/incidencias" class="nav-item">Incidencias</a>

    <div class="nav-group">Contabilidad</div>
    <a href="/contabilidad/dashboard" class="nav-item">Dashboard</a>

    <div class="nav-group">Sistemas</div>
    <a href="/sistemas/dashboard" class="nav-item">Dashboard</a>

    <div class="nav-group">Cumplimiento</div>
    <a href="/cumplimiento/dashboard" class="nav-item">Dashboard</a>

    <div class="nav-group">Admin</div>
    <a href="/administrador/dashboard" class="nav-item">Dashboard</a>

    <div class="nav-group">Sesión</div>
    <?php if (!empty($_SESSION['auth'])): ?>
      <div class="nav-user">Hola, <?= htmlspecialchars($_SESSION['auth']['name']) ?></div>
      <a href="/logout" class="nav-item">Salir</a>
    <?php else: ?>
      <a href="/login" class="nav-item">Ingresar</a>
    <?php endif; ?>
  </nav>
</aside>
