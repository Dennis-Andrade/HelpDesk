<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$action = isset($action) ? (string)$action : '/comercial/entidades';
?>
<link rel="stylesheet" href="/css/comercial_style/comercial-entidades.css">

<section class="card ent-container">
  <h1 class="ent-title">Nueva Entidad</h1>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form" enctype="multipart/form-data">
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-cancel" href="/comercial/entidades">Cancelar</a>
    </div>
  </form>
</section>
