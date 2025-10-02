<?php
$crumbs = $crumbs ?? [];
include __DIR__ . '/../../partials/breadcrumbs.php';

$entityId = (int)($item['id'] ?? $item['id_entidad'] ?? 0);
$action    = isset($action) ? (string)$action : '/comercial/entidades/' . $entityId;
$toastMessage = isset($toastMessage) && $toastMessage !== '' ? (string)$toastMessage : null;
?>
<link rel="stylesheet" href="/css/comercial_style/comercial-entidades.css">

<section class="card ent-container">
  <h1 class="ent-title">Editar Cooperativa</h1>
  <?php if ($toastMessage !== null): ?>
    <div class="ent-toast" role="status" aria-live="polite"><?= htmlspecialchars($toastMessage, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form ent-form">
    <input type="hidden" name="id" value="<?= $entityId ?>">
    <?php include __DIR__ . '/_form.php'; ?>
    <div class="form-actions ent-actions">
      <button class="btn btn-primary" type="submit">Actualizar</button>
      <a class="btn btn-cancel" href="/comercial/entidades">Cancelar</a>
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
