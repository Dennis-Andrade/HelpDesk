<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';
?>
<link rel="stylesheet" href="/css/comercial_style/comercial-entidades.css">

<section class="card ent-container">
  <h1 class="ent-title">Editar Cooperativa</h1>
  <form method="post" action="/comercial/entidades/editar" class="form ent-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="id" value="<?= (int)($item['id_entidad'] ?? 0) ?>">
    <?php include __DIR__.'/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Actualizar</button>
      <a class="btn btn-cancel" href="/comercial/entidades">Cancelar</a>
    </div>
  </form>
</section>
