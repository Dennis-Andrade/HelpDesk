<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$action = isset($action) ? (string)$action : '/contabilidad/facturacion/guardar';
?>
<link rel="stylesheet" href="/css/contabilidad.css">

<section class="card ent-container">
  <h1 class="ent-title">Datos de facturación</h1>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form" data-facturacion-form>
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Guardar</button>
      <a class="btn btn-cancel" href="/contabilidad/facturacion">Volver</a>
    </div>
  </form>
</section>
<script src="/js/contabilidad-facturacion.js" defer></script>
