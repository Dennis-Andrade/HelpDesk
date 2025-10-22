<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$entityId = isset($item['id']) ? (int)$item['id'] : 0;
$action   = isset($action) ? (string)$action : '/contabilidad/entidades/' . $entityId;
$toastMessage = isset($toastMessage) && $toastMessage !== '' ? (string)$toastMessage : null;
?>
<link rel="stylesheet" href="/css/comercial_style/comercial-entidades.css">

<section class="card ent-container">
  <h1 class="ent-title">Editar Entidad</h1>
  <?php if ($toastMessage !== null): ?>
    <div class="ent-toast" role="status" aria-live="polite"><?= htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $entityId ?>">
    <?php include __DIR__ . '/../../comercial/entidades/_form.php'; ?>

    <?php
      $factTotal = isset($item['facturacion_total']) ? (float)$item['facturacion_total'] : null;
      $sicLic = isset($item['sic_licencias']) ? (int)$item['sic_licencias'] : null;
      $fechaRegistro = isset($item['fecha_registro']) ? (string)$item['fecha_registro'] : null;
      $showSummary = ($factTotal !== null) || ($sicLic !== null) || ($fechaRegistro !== null);
      if ($factTotal !== null) {
          $factLabel = '$' . number_format($factTotal, 2, '.', ',');
      } else {
          $factLabel = '—';
      }
      if ($sicLic !== null) {
          $sicLabel = $sicLic > 0 ? $sicLic . ' ' . ($sicLic === 1 ? 'licencia' : 'licencias') : 'Sin licencias registradas';
      } else {
          $sicLabel = '—';
      }
      if ($fechaRegistro !== null && $fechaRegistro !== '') {
          $timestamp = strtotime($fechaRegistro);
          $fechaLabel = $timestamp !== false ? date('d/m/Y', $timestamp) : $fechaRegistro;
      } else {
          $fechaLabel = 'Sin registro';
      }
    ?>
    <?php if ($showSummary): ?>
      <div class="ent-summary" aria-live="polite">
        <h2 class="ent-summary__title">Resumen comercial</h2>
        <dl class="ent-summary__list">
          <div class="ent-summary__item">
            <dt>Facturación total</dt>
            <dd><?= htmlspecialchars($factLabel, ENT_QUOTES, 'UTF-8') ?></dd>
          </div>
          <div class="ent-summary__item">
            <dt>Licencias SIC</dt>
            <dd><?= htmlspecialchars($sicLabel, ENT_QUOTES, 'UTF-8') ?></dd>
          </div>
          <div class="ent-summary__item">
            <dt>Registrada el</dt>
            <dd><?= htmlspecialchars($fechaLabel, ENT_QUOTES, 'UTF-8') ?></dd>
          </div>
        </dl>
      </div>
    <?php endif; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Actualizar</button>
      <a class="btn btn-cancel" href="/contabilidad/entidades">Cancelar</a>
    </div>
  </form>
</section>

<?php if ($toastMessage !== null): ?>
<script>
(function(){
  var toast = document.querySelector('.ent-toast');
  if(!toast){return;}
  setTimeout(function(){
    toast.style.transition = 'opacity .4s ease';
    toast.style.opacity = '0';
    setTimeout(function(){
      if(toast.parentNode){ toast.parentNode.removeChild(toast); }
    }, 400);
  }, 8000);
})();
</script>
<?php endif; ?>
