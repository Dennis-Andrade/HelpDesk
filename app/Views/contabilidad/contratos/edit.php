<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$action = isset($action) ? (string)$action : '/contabilidad/contratos';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="card ent-container">
  <h1 class="ent-title">Editar contrato</h1>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form" enctype="multipart/form-data" data-contrato-form>
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Actualizar</button>
      <a class="btn btn-cancel" href="/contabilidad/contratos">Cancelar</a>
    </div>
  </form>
</section>
<script src="/js/contabilidad-contratos.js" defer></script>
