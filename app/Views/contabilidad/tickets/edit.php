<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$action = isset($action) ? (string)$action : '';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="card ent-container">
  <h1 class="ent-title">Editar ticket contable</h1>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form">
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">
        <span class="material-symbols-outlined" aria-hidden="true">save</span>
        Actualizar
      </button>
      <a class="btn btn-cancel" href="/contabilidad/tickets">Cancelar</a>
    </div>
  </form>
</section>
